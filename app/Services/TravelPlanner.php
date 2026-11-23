<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Timeline;
use App\Models\Location;
use App\Models\Trail;
use App\Services\Routing\Leg;
use App\Services\Routing\Point;
use App\Services\Routing\TravelTimes;
use Carbon\CarbonImmutable;

/**
 * The drives within a day: from home to each stop in turn and back home again.
 *
 * settle() runs while an itinerary is being built: it times every drive with the router and makes room for it,
 * pushing stops later when a drive needs more time than the gap before them, and dropping the stops the builder
 * says it can do without when the day would otherwise end after the latest return home.
 *
 * annotate() runs after someone edits a day: it times the drives the same way and flags any gap that's too short,
 * but leaves their times alone.
 */
class TravelPlanner
{
    /** Stops are moved in steps of this many minutes. */
    private const ROUND_TO_MINUTES = 5;
    private const MAX_DROPS = 4;
    /** Nobody is asked to leave home before this. */
    private const EARLIEST_START = '06:00';
    private const EARLIEST_HOME_DINNER = '17:30';

    public function __construct(private TravelTimes $travelTimes)
    {
    }

    /**
     * Make room for every drive. A day that ends after $aimHomeBy gets a note saying when they'll be back; one that
     * ends after $homeBy loses its latest droppable stops, one at a time, as long as that gets them home sooner.
     *
     * @param callable(Activity): bool $canDrop
     * @return Activity[] the stops that were dropped
     */
    public function settle(Itinerary $itinerary, Day $day, string $aimHomeBy, string $homeBy, callable $canDrop): array
    {
        // Each attempt starts from the builder's times, so dropping a stop also undoes the shifts it caused.
        $original = $day->getActivities()->map(fn(Activity $a) => [$a, $a->start_time, $a->end_time])->all();
        $dropped = [];
        $arrival = $this->attempt($itinerary, $day, $original, $dropped);

        while (Timeline::toMinutes($arrival) > Timeline::toMinutes($homeBy) && count($dropped) < self::MAX_DROPS) {
            $victim = $day->getActivities()->reverse()->first(fn(Activity $a) => $this->pointFor($a, $itinerary) && $canDrop($a));
            if (!$victim) {
                break;
            }
            $sooner = $this->attempt($itinerary, $day, $original, [...$dropped, $victim]);
            if (Timeline::toMinutes($sooner) >= Timeline::toMinutes($arrival)) {
                $arrival = $this->attempt($itinerary, $day, $original, $dropped);
                break;
            }
            $dropped[] = $victim;
            $arrival = $sooner;
        }

        if (Timeline::toMinutes($arrival) > Timeline::toMinutes($aimHomeBy)) {
            $day->return_notes = [...($day->return_notes ?? []), ['type' => 'late', 'closure' => false,
                'text' => 'You\'ll get back around ' . date('g:i A', strtotime('2001-01-01 ' . $arrival)) . '.']];
        }
        return $dropped;
    }

    /** Times every drive without moving anything; flags stops there isn't time to drive to. Saves the day. */
    public function annotate(Itinerary $itinerary, Day $day): void
    {
        $this->walk($itinerary, $day, false);
        foreach ($day->getActivities() as $activity) {
            if ($activity->isDirty(['travel_minutes', 'travel_miles', 'travel_source', 'travel_notes'])) {
                $activity->saveQuietly();
            }
        }
        $day->save();
    }

    /** Put every stop back at its original time, leave out $dropped, and walk the day. */
    private function attempt(Itinerary $itinerary, Day $day, array $original, array $dropped): string
    {
        foreach ($original as [$activity, $start, $end]) {
            if (in_array($activity, $dropped, true)) {
                $day->removeActivity($activity);
            } else {
                $day->moveActivity($activity, $start, $end);
            }
        }
        return $this->walk($itinerary, $day, true);
    }

    /**
     * Go through the day in order, timing each drive. Returns the time you'd get home.
     */
    private function walk(Itinerary $itinerary, Day $day, bool $makeRoom): string
    {
        $home = $this->home($itinerary);
        $at = $home;
        $free = null;
        $total = 0;
        $located = 0;
        $first = null;

        foreach ($day->getActivities() as $activity) {
            $point = $this->pointFor($activity, $itinerary);
            if (!$point) {
                $this->clearTravel($activity);
                continue;
            }

            $leaving = $free ?? $activity->start_time;
            $leg = $this->travelTimes->leg($at, $point, $this->departure($day, $leaving));
            $notes = $leg->notes;

            if ($free !== null) {
                $earliest = Timeline::toMinutes($free) + $leg->minutes;
                $late = $earliest - Timeline::toMinutes($activity->start_time);
                // Leaving home earlier beats starting later: move the day's first stop at home (breakfast) back.
                if ($late > 0 && $makeRoom && $located === 1 && $first && $at->key() === $home->key() && !$this->isFixedTime($first)) {
                    $earlier = min($late, Timeline::toMinutes($first->start_time) - Timeline::toMinutes(self::EARLIEST_START));
                    $earlier = (int) (ceil(max(0, $earlier) / self::ROUND_TO_MINUTES) * self::ROUND_TO_MINUTES);
                    if ($earlier > 0 && Timeline::toMinutes($first->start_time) - $earlier >= Timeline::toMinutes(self::EARLIEST_START)) {
                        $day->moveActivity($first, Timeline::addMinutes($first->start_time, -$earlier), Timeline::addMinutes($first->end_time, -$earlier));
                        $free = $first->end_time;
                        $late -= $earlier;
                    }
                }
                // A home dinner left late by a stop that was dropped comes forward (never before EARLIEST_HOME_DINNER).
                if ($late < 0 && $makeRoom && $this->isHomeDinner($activity, $point, $home)) {
                    $target = max($earliest, Timeline::toMinutes(self::EARLIEST_HOME_DINNER));
                    $target = (int) (ceil($target / self::ROUND_TO_MINUTES) * self::ROUND_TO_MINUTES);
                    $pull = Timeline::toMinutes($activity->start_time) - $target;
                    if ($pull > 0) {
                        $day->moveActivity($activity, Timeline::addMinutes($activity->start_time, -$pull), Timeline::addMinutes($activity->end_time, -$pull));
                    }
                }
                if ($late > 0) {
                    if ($makeRoom && !$this->isFixedTime($activity)) {
                        $shift = (int) (ceil($late / self::ROUND_TO_MINUTES) * self::ROUND_TO_MINUTES);
                        $day->moveActivity($activity,
                            Timeline::addMinutes($activity->start_time, $shift), Timeline::addMinutes($activity->end_time, $shift));
                    } else {
                        $notes[] = ['type' => 'tight', 'closure' => false, 'text' => sprintf(
                            'The drive here takes about %s, so you may arrive %s late.',
                            self::duration($leg->minutes), self::duration($late))];
                    }
                }
            }

            $this->setTravel($activity, $leg, $notes);
            $total += $leg->minutes;
            $at = $point;
            $free = self::later($free, $activity->end_time);
            $first ??= $activity;
            $located++;
        }

        $leaving = $free ?? '21:00';
        $back = $this->travelTimes->leg($at, $home, $this->departure($day, $leaving));
        $day->return_minutes = $back->minutes;
        $day->return_miles = $back->miles;
        $day->return_notes = $back->notes ?: null;
        $day->drive_minutes = $total + $back->minutes;

        return Timeline::addMinutes($leaving, $back->minutes);
    }

    /**
     * Where an activity happens: home for meals there and for checking in and out of the traveller's own lodging,
     * the resource's location otherwise. Notes and packed lunches aren't anywhere in particular.
     */
    public function pointFor(Activity $activity, Itinerary $itinerary): ?Point
    {
        $type = ActivityType::nameFor($activity->activity_type_id);
        if ($activity->isTask()) {
            $atHome = in_array($type, [ActivityType::CHECKING_IN, ActivityType::CHECKING_OUT], true)
                || ($type === ActivityType::COOKING && $activity->durationMinutes() > 0);
            return $atHome ? $this->home($itinerary) : null;
        }

        $resource = $activity->resource;
        if (!$resource) {
            return null;
        }
        if ($resource instanceof Trail || $resource instanceof Location) {
            if ($point = Point::make($resource->latitude, $resource->longitude)) {
                return $point;
            }
        }
        if (method_exists($resource, 'location') && ($location = $resource->location)) {
            if ($point = Point::make($location->latitude, $location->longitude)) {
                return $point;
            }
        }
        // Somewhere in its area, as a last resort.
        $area = $resource->area ?? null;
        return $area ? Point::make($area->latitude, $area->longitude) : null;
    }

    public function home(Itinerary $itinerary): Point
    {
        $home = $itinerary->homePoint();
        return new Point($home['lat'], $home['lng']);
    }

    private function isHomeDinner(Activity $activity, Point $point, Point $home): bool
    {
        return $activity->isTask() && $activity->name === 'Dinner' && $point->key() === $home->key();
    }

    private function isFixedTime(Activity $activity): bool
    {
        return in_array($activity->resource_table, ['events', 'accommodations'], true)
            || in_array(ActivityType::nameFor($activity->activity_type_id), [ActivityType::CHECKING_IN, ActivityType::CHECKING_OUT], true);
    }

    private function departure(Day $day, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($day->date . ' ' . Timeline::toTime(Timeline::toMinutes($time)), config('routing.timezone'));
    }

    private function setTravel(Activity $activity, Leg $leg, array $notes): void
    {
        $activity->travel_minutes = $leg->minutes;
        $activity->travel_miles = $leg->miles;
        $activity->travel_source = $leg->minutes > 0 ? $leg->source : null;
        $activity->travel_notes = $notes ?: null;
    }

    private function clearTravel(Activity $activity): void
    {
        $activity->travel_minutes = null;
        $activity->travel_miles = null;
        $activity->travel_source = null;
        $activity->travel_notes = null;
    }

    private static function later(?string $a, string $b): string
    {
        return $a !== null && Timeline::toMinutes($a) >= Timeline::toMinutes($b) ? $a : $b;
    }

    private static function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return trim(($h ? "{$h} h " : '') . ($m || !$h ? "{$m} min" : ''));
    }
}
