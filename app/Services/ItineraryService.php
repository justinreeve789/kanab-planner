<?php
/**
 * Builds itineraries.
 *
 * generate() runs the whole pipeline: read preferences → pick an area for each day → order the days → fill each day
 * on a Timeline (so nothing overlaps and drive time is respected) → save. regenerateDay() refills one saved day.
 *
 * If there's a low adventurousness (<= 0.5), stick with the trails that everyone comes to do, such as Angels Landing,
 * the Narrows, Scout Overlook, etc.
 */

namespace App\Services;

use App\Models\Accommodation;
use App\Models\ActivityType;
use App\Models\Area;
use App\Models\AreaDistance;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Models\Itinerary\Timeline;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\Routing\Point;
use App\Services\Routing\TravelTimes;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItineraryService
{
    public const MINIMUM_TRAIL_DAYS_RATIO_TO_TOTAL_POSSIBLE = 0.8;

    /** Used when there's no area_distances row for a pair of areas. */
    public const DEFAULT_DRIVING_MPH = 45;

    public const DAY_START = '06:00';
    public const DAY_END = '22:00';
    public const BREAKFAST_AT_HOME = ['07:00', 30];
    public const LATEST_RETURN_HOME = '21:30';
    /** Past this, TravelPlanner::settle() drops stops rather than just noting a late return. */
    public const MUST_BE_HOME_BY = '22:00';
    /** Areas this close to the base (straight line) aren't limited to one visit. Kanab to Zion is about 32. */
    public const CORE_AREA_MILES = 35;
    /** How far a lodging pin may be from the middle of the chosen base area. */
    public const MAX_LODGING_MILES_FROM_BASE = 50;

    /** [earliest start, latest start] for each kind of thing, before drive time is applied. */
    public const TRAIL_WINDOWS = [
        Period::MORNING => ['08:00', '10:30'],
        Period::AFTERNOON => ['13:00', '15:00'],
        Period::EVENING => ['16:30', '19:00'],
    ];
    public const LUNCH_WINDOW = ['11:30', '13:30'];
    public const DINNER_WINDOW = ['17:00', '20:00'];
    public const EVENT_WINDOWS = [
        Period::MORNING => ['06:00', '11:30'],
        Period::AFTERNOON => ['11:30', '16:00'],
        Period::EVENING => ['16:00', '22:00'],
    ];

    /** Minutes for meals cooked at home, and the base hours for restaurant meals (scaled by busyness). */
    public const HOME_MEAL_MINUTES = ['breakfast' => 30, 'lunch' => 45, 'dinner' => 60];
    public const RESTAURANT_MEAL_HOURS = ['breakfast' => 0.75, 'lunch' => 1.0, 'dinner' => 1.5];

    public const EVENING_TRAIL_MAX_HOURS = 2.0;
    public const MAX_EVENT_MINUTES = 180;
    public const SIGHTSEEING_MINUTES = 90;

    /** Preferred activity types that mean "schedule trails" / "schedule events". */
    public const TRAIL_ACTIVITY_TYPES = ['hiking', 'bicycling', 'offroading', 'rock-climbing'];
    public const EVENT_ACTIVITY_TYPES = [
        'concert', 'educational-fun', 'festivals-and-celebrations', 'museum', 'night-life', 'rodeo', 'stargazing',
        'tour', 'games-and-entertainment', 'endurance-race',
    ];

    public $locationService;
    public $phraseService;
    public $trailService;
    private TravelTimes $travelTimes;
    private TravelPlanner $travelPlanner;
    private ?Itinerary $itinerary;

    /** @var array<int, true> day_nums chosen as trail days for this trip. */
    private array $trailDays = [];

    public function __construct(?Itinerary $itinerary = null)
    {
        $this->setItinerary($itinerary ?? new Itinerary());
        $this->locationService = new LocationService();
        $this->phraseService = new PhraseService();
        $this->trailService = new TrailService();
        $this->travelTimes = app(TravelTimes::class);
        $this->travelPlanner = app(TravelPlanner::class);
    }

    public function getItinerary(): ?Itinerary
    {
        return $this->itinerary;
    }

    public function setItinerary(?Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

    /**
     * Build and save a complete itinerary from a request's preferences.
     */
    public function generate(Request $request): Itinerary
    {
        ActivityType::flushIdCache();
        $this->setupItinerary($request);
        $itinerary = $this->getItinerary();

        $this->assignDaysForAreas($itinerary->getUseFirstDay(), $itinerary->getUseLastDay());
        $this->shuffleDays();
        $this->placePriorityEventDays();

        $itinerary->setTotalPossibleTrailDays($this->calculateTotalPossibleTrailDays());
        $itinerary->minimum_trail_days = max(1, (int) floor(self::MINIMUM_TRAIL_DAYS_RATIO_TO_TOTAL_POSSIBLE * $itinerary->getTotalPossibleTrailDays()));
        $this->chooseTrailDays();

        foreach ($itinerary->getDays() as $day) {
            $this->fillDay($day);
        }
        $itinerary->setTotalTravelDistance($this->estimateTotalTravelDistance());

        $this->saveItinerary();
        return $itinerary;
    }

    /**
     * Throw away a saved day's activities and fill it again, avoiding everything scheduled on the other days.
     */
    public function regenerateDay(Itinerary $itinerary, Day $target): Day
    {
        ActivityType::flushIdCache();
        $itinerary->load(['days.periods.activities']);
        $this->setItinerary($itinerary);
        $days = $itinerary->getDays();
        $itinerary->setDays($days);

        foreach ($days as $day) {
            if ($day->id === $target->id) {
                continue;
            }
            foreach ($day->getActivities() as $activity) {
                $this->rememberResource($activity->resource_table, $activity->resource_id);
            }
        }

        $day = $days[$target->day_num];
        DB::transaction(function() use ($day) {
            foreach ($day->getPeriods() as $period) {
                $period->activities()->delete();
                $period->setRelation('activities', new \Illuminate\Database\Eloquent\Collection());
            }
            $day->stats = null;
            $day->setPreamble(null);

            // Same odds of a hike as the rest of the trip.
            $fullDays = max(1, $this->getItinerary()->getDurationDays() - 2);
            $odds = $this->calculateTotalPossibleTrailDays() / $fullDays;
            $this->trailDays = ($this->wantsTrails() && mt_rand() / mt_getrandmax() < $odds) ? [$day->day_num => true] : [];
            $this->fillDay($day);
            $this->saveDay($day);
        });

        return $day->fresh(['periods.activities']);
    }

    /**
     * Persist the itinerary, its days, periods and activities.
     */
    public function saveItinerary(): void
    {
        DB::transaction(function() {
            $itinerary = $this->getItinerary();
            $itinerary->save();
            foreach ($itinerary->getDays() as $day) {
                $day->itinerary_id = $itinerary->id;
                $this->saveDay($day);
            }
        });
    }

    private function saveDay(Day $day): void
    {
        $day->save();
        foreach ($day->getPeriods() as $period) {
            $period->day_id = $day->id;
            $period->save();
            foreach ($period->getActivities() as $activity) {
                $activity->period_id = $period->id;
                $activity->activity_type_id = $activity->activity_type_id ?? ActivityType::idFor('unknown');
                $activity->save();
            }
        }
    }

    public function getDeviations(): array
    {
        return [
            'adventurousness' => $this->getItinerary()->getAdventurousness(),
            'solitariness' => $this->getItinerary()->getSolitariness(),
            'strenuousness' => $this->getItinerary()->getStrenuousness(),
        ];
    }

    /**
     * A "trail day" is a day in which at least one trail is added. There could be multiple shorter hikes on the same day.
     * 0.1 = ~25% of the days spent hiking.
     * 0.5 = ~50% of the days spent hiking.
     * 1.0 = ~100% of the days spent hiking.
     */
    public function calculateTotalPossibleTrailDays(): int
    {
        $adventurousness = $this->getItinerary()->getAdventurousness();
        $days = ($this->getItinerary()->getDurationDays() - 2);

        // Define the target proportions for the specified adventurousness levels.
        $proportionLow = 1/4;   // For adventurousness ~0.1
        $proportionMedium = 1/2;  // For adventurousness ~0.5
        $proportionHigh = 1.0;  // For adventurousness = 1.0

        // Use a logarithmic function to map adventurousness to a scaling factor.
        $logScale = log10(1 + ($adventurousness * 9));

        // Interpolate between the base proportions using the logarithmic scale.
        if ($adventurousness <= 0.5) {
            $interpolatedProportion = $proportionLow + ($proportionMedium - $proportionLow) * ($logScale / log10(5.5));
        } else {
            $interpolatedProportion = $proportionMedium + ($proportionHigh - $proportionMedium) * (($logScale - log10(5.5)) / (log10(10) - log10(5.5)));
        }

        $calculatedTrailDays = round($days * $interpolatedProportion);

        // Ensure the calculated trail days do not exceed the total days or go below zero.
        return max(1, min($days, $calculatedTrailDays));
    }

    /**
     * Pick which full days get a hike: every day with a priority trail, then random days until
     * total_possible_trail_days is reached. Nothing is picked if the user's activities exclude trails entirely.
     */
    public function chooseTrailDays(): void
    {
        $itinerary = $this->getItinerary();
        $fullDays = array_filter($itinerary->getDays(), fn(Day $day) => !$this->isArrivalDay($day) && !$this->isDepartureDay($day));
        $this->trailDays = [];

        $priorityAreas = Trail::whereIn('id', $itinerary->getTrailPriorities())->pluck('area_id')->all();
        foreach ($fullDays as $day) {
            if (in_array($day->area_id, $priorityAreas, true)) {
                $this->trailDays[$day->day_num] = true;
            }
        }

        if (!$this->wantsTrails()) {
            return;
        }
        $remaining = array_keys(array_diff_key($fullDays, $this->trailDays));
        shuffle($remaining);
        while (count($this->trailDays) < $itinerary->getTotalPossibleTrailDays() && $remaining) {
            $this->trailDays[array_pop($remaining)] = true;
        }
    }

    /**
     * Fill a day on a timeline. Arrival and departure days get check-in/out and a meal; full days get, in order:
     * breakfast, priority events and trails, other events, trails, priority places, lunch and dinner. Everything is
     * placed in a free slot, after the drive from base and early enough to drive back. Then every drive in the day
     * is timed, stop to stop, and the day adjusted to fit them (TravelPlanner::settle()).
     */
    public function fillDay(Day $day): void
    {
        if ($this->isArrivalDay($day)) {
            $this->fillArrivalDay($day);
        } elseif ($this->isDepartureDay($day)) {
            $this->fillDepartureDay($day);
        } else {
            $this->fillFullDay($day);
        }
        $this->travelPlanner->settle($this->getItinerary(), $day, self::LATEST_RETURN_HOME, self::MUST_BE_HOME_BY,
            fn(Activity $a) => $this->canDrop($a));
        $this->addPreamble($day);
        $day->calculateASS(true);
    }

    private function fillArrivalDay(Day $day): void
    {
        $day->drive_minutes = 0;
        $itinerary = $this->getItinerary();
        $accommodation = $itinerary->listedAccommodation();
        $checkIn = $itinerary->getCheckIn() ?? $accommodation?->check_in ?? Itinerary::DEFAULT_CHECKIN_TIME;
        $timeline = Timeline::fromDay($day, self::DAY_START, self::DAY_END);
        $this->addLodgingStop($day, $timeline, $accommodation, ActivityType::CHECKING_IN, $checkIn, 30);
        if (!$this->addRestaurantMeal($day, $timeline, 'dinner', ['17:30', '19:30'], self::DAY_END)) {
            $this->getItinerary()->addTask(ActivityType::COOKING, 'Dinner', $this->phraseService->getPhraseForArrivingLateFood(),
                '18:00', '19:00', $day->day_num);
        }
    }

    private function fillDepartureDay(Day $day): void
    {
        $day->drive_minutes = 0;
        $itinerary = $this->getItinerary();
        $accommodation = $itinerary->listedAccommodation();
        $checkOut = $itinerary->getCheckOut() ?? $accommodation?->check_out ?? Itinerary::DEFAULT_CHECKOUT_TIME;
        $timeline = Timeline::fromDay($day, self::DAY_START, self::DAY_END);
        $breakfastStart = Timeline::addMinutes($checkOut, -90);
        $this->addHomeMeal($day, $timeline, 'breakfast', $breakfastStart);
        $this->addLodgingStop($day, $timeline, $accommodation, ActivityType::CHECKING_OUT, $checkOut, 15);
    }

    /**
     * Check in or out: at a listed accommodation when there is one, otherwise at the traveller's own lodging
     * ("Check in at Zion Canyon Lodge", or "your lodging" when they only dropped a pin or chose an area).
     */
    private function addLodgingStop(Day $day, Timeline $timeline, ?Accommodation $accommodation, string $type, string $start, int $minutes): void
    {
        $end = Timeline::addMinutes($start, $minutes);
        if ($accommodation) {
            $this->addTimed($day, $timeline, $accommodation, $type, $start, $end);
            return;
        }
        $itinerary = $this->getItinerary();
        $place = filled($itinerary->lodging_name) ? $itinerary->lodging_name : 'your lodging';
        $name = ($type === ActivityType::CHECKING_IN ? 'Check in at ' : 'Check out of ') . $place;
        $itinerary->addTask($type, $name, null, $start, $end, $day->day_num);
        $timeline->reserve($start, $end);
    }

    private function fillFullDay(Day $day): void
    {
        $itinerary = $this->getItinerary();
        // A long priority trail in Zion starts at 08:00 to beat the crowds, so breakfast moves earlier.
        $priorityTrail = $this->nextPriorityTrailFor($day);
        $drive = $this->driveToAreaMinutes($day, $priorityTrail);
        $timeline = Timeline::fromDay($day, self::DAY_START, self::DAY_END);
        $returnBy = Timeline::addMinutes(self::LATEST_RETURN_HOME, -$drive);

        $priorityPeriod = $priorityTrail ? $this->trailService->getBestPeriodOfDayForTrail($priorityTrail) : null;
        $earlyZionStart = $priorityTrail && $priorityPeriod === Period::MORNING && $priorityTrail->area_id === Area::ZION;

        $breakfastStart = $earlyZionStart
            ? Timeline::floorToQuarter(Timeline::addMinutes('08:00', -$drive - self::HOME_MEAL_MINUTES['breakfast']))
            : self::BREAKFAST_AT_HOME[0];
        $arrival = $this->addBreakfast($day, $timeline, $breakfastStart, $drive, $earlyZionStart);

        $this->addPriorityEvents($day, $timeline, $returnBy);

        if ($priorityTrail) {
            $earliest = $earlyZionStart ? '08:00' : $this->later(self::TRAIL_WINDOWS[$priorityPeriod][0], $arrival);
            $this->placeTrail($day, $timeline, $priorityTrail, $earliest, self::TRAIL_WINDOWS[$priorityPeriod][1], $returnBy,
                $earlyZionStart ? 'Ideally you\'ll reach the trail by 8:00am to avoid larger crowds.' : null);
        }

        if ($this->wantsEvents()) {
            foreach (self::EVENT_WINDOWS as $window) {
                $this->addEventInWindow($day, $timeline, $this->later($window[0], $arrival), $window[1], $returnBy);
            }
        }

        if (isset($this->trailDays[$day->day_num])) {
            $this->addTrails($day, $timeline, $arrival, $returnBy);
        }

        $this->addPriorityLocation($day, $timeline, $arrival, $returnBy);
        $this->addLunch($day, $timeline, $arrival, $returnBy);
        $this->addDinner($day, $timeline, $drive, $returnBy);
    }

    /**
     * Breakfast at home before leaving, or (adventurousness ≥ 0.6, coin flip) at a restaurant in the day's area.
     * Returns the earliest time other activities can start in the day's area.
     */
    private function addBreakfast(Day $day, Timeline $timeline, string $homeStart, int $drive, bool $mustBeHome): string
    {
        $eatOut = !$mustBeHome && $this->getItinerary()->getAdventurousness() >= 0.6 && mt_rand(0, 1) === 1;
        if ($eatOut) {
            $arrival = Timeline::addMinutes(self::BREAKFAST_AT_HOME[0], $drive);
            $meal = $this->addRestaurantMeal($day, $timeline, 'breakfast', [$arrival, Timeline::addMinutes($arrival, 60)], '10:30');
            if ($meal) {
                return $meal->end_time;
            }
        }
        $meal = $this->addHomeMeal($day, $timeline, 'breakfast', $homeStart);
        return Timeline::addMinutes($meal->end_time, $drive);
    }

    private function addLunch(Day $day, Timeline $timeline, string $arrival, string $returnBy): void
    {
        [$from, $to] = self::LUNCH_WINDOW;
        $from = $this->later($from, $arrival);
        if ($this->addRestaurantMeal($day, $timeline, 'lunch', [$from, $to], $returnBy)) {
            return;
        }
        if ($this->isAtBase($day)) {
            $start = $timeline->findSlot(self::HOME_MEAL_MINUTES['lunch'], $from, $to);
            if ($start) {
                $this->addHomeMeal($day, $timeline, 'lunch', $start);
                return;
            }
        }
        // Out on a trail through lunch, or nowhere to eat: bring it along.
        $during = $this->activityAt($day, '12:00');
        $this->getItinerary()->addTask(ActivityType::COOKING, 'Pack a lunch',
            $during ? 'You\'ll be out at ' . $during->resource?->name . ' over lunch, so bring a packed lunch and plenty of water.'
                : 'Bring a packed lunch and snacks for the day.',
            '12:00', '12:00', $day->day_num);
    }

    /**
     * Dinner after the day's last activity: at a restaurant in the area, or at home if there's time to drive back.
     */
    private function addDinner(Day $day, Timeline $timeline, int $drive, string $returnBy): void
    {
        [$from, $to] = self::DINNER_WINDOW;
        $afterActivities = $timeline->lastEnd() ?? $from;
        $from = $this->later($from, $afterActivities);

        $eatOut = $this->isAtBase($day) || $this->getItinerary()->getAdventurousness() >= 0.3 || mt_rand(0, 1) === 1;
        // Away from base, dinner has to finish in time for the drive home.
        $latestEnd = $this->isAtBase($day) ? $this->later($returnBy, '21:00') : $returnBy;
        if ($eatOut && $this->addRestaurantMeal($day, $timeline, 'dinner', [$from, $to], $latestEnd)) {
            return;
        }
        $homeFrom = $this->later($from, Timeline::addMinutes($afterActivities, $drive));
        $start = $timeline->findSlot(self::HOME_MEAL_MINUTES['dinner'], $homeFrom, '20:30');
        if ($start) {
            $this->addHomeMeal($day, $timeline, 'dinner', $start);
            return;
        }
        if (!$eatOut) {
            $this->addRestaurantMeal($day, $timeline, 'dinner', [$from, $to], $latestEnd);
        }
    }

    private function addHomeMeal(Day $day, Timeline $timeline, string $meal, string $start): Activity
    {
        $end = Timeline::addMinutes($start, self::HOME_MEAL_MINUTES[$meal]);
        $phrase = match ($meal) {
            'breakfast' => $this->phraseService->getPhraseForBreakfast(),
            'lunch' => $this->phraseService->getPhraseForLunch(),
            default => $this->phraseService->getPhraseForLightMeal(),
        };
        $timeline->reserve($start, $end);
        return $this->getItinerary()->addTask(ActivityType::COOKING, ucfirst($meal), $phrase, $start, $end, $day->day_num);
    }

    /**
     * An unused restaurant in the day's area that's open when the meal starts, priority restaurants first.
     */
    private function addRestaurantMeal(Day $day, Timeline $timeline, string $meal, array $window, string $latestEnd): ?Activity
    {
        [$from, $to] = $window;
        $baseHours = self::RESTAURANT_MEAL_HOURS[$meal];
        $minutes = (int) round($baseHours * 60);
        $start = $timeline->findSlot($minutes, $from, $this->earlier($to, Timeline::addMinutes($latestEnd, -$minutes)));
        if (!$start) {
            return null;
        }

        $query = fn() => Restaurant::whereIsOpen($day->date . ' ' . $start)
            ->whereNotIn('restaurants.id', $this->getItinerary()->restaurant_ids)
            ->whereHas('location', fn($q) => $q->where('area_id', $day->area_id));
        $restaurant = $query()->whereIn('restaurants.id', $this->getItinerary()->getRestaurantPriorities())->first()
            ?? $query()->inRandomOrder()->first();
        if (!$restaurant) {
            return null;
        }

        $end = $restaurant->calculateEndTime($day->date, $start, ['base_hours' => $baseHours]);
        if (!$timeline->isFree($start, $end)) {
            $end = Timeline::addMinutes($start, $minutes);
        }
        return $this->addTimed($day, $timeline, $restaurant, ActivityType::DINING, $start, $end,
            $this->phraseService->getPhraseForDining($restaurant));
    }

    /**
     * Morning or afternoon (coin flip) first; a second hike only for adventurousness ≥ 0.7; a short evening hike
     * when the day still has room.
     */
    private function addTrails(Day $day, Timeline $timeline, string $arrival, string $returnBy): void
    {
        $periods = mt_rand(0, 1) ? [Period::MORNING, Period::AFTERNOON] : [Period::AFTERNOON, Period::MORNING];
        foreach ([...$periods, Period::EVENING] as $period) {
            if ($day->hasTrail() && $this->getItinerary()->getAdventurousness() < 0.7) {
                return;
            }
            $trail = $this->trailService->getRandomTrailForPeriod(
                $day->area_id, $period, $this->getItinerary(), $this->getItinerary()->trail_ids
            );
            if ($trail) {
                [$from, $to] = self::TRAIL_WINDOWS[$period];
                $this->placeTrail($day, $timeline, $trail, $this->later($from, $arrival), $to, $returnBy);
            }
        }
    }

    private function placeTrail(Day $day, Timeline $timeline, Trail $trail, string $earliest, string $latestStart, string $returnBy, ?string $note = null): ?Activity
    {
        $minutes = (int) round(60 * (float) $trail->getAverageHours(['average_hiking_speed' => $this->getItinerary()->getAverageHikingSpeed()]));
        $start = $timeline->findSlot($minutes, $earliest, $this->earlier($latestStart, Timeline::addMinutes($returnBy, -$minutes)));
        if (!$start) {
            return null;
        }
        $phrase = trim($this->phraseService->getPhraseForHiking($trail) . ' ' . ($note ?? ''));
        return $this->addTimed($day, $timeline, $trail, ActivityType::HIKING, $start, Timeline::addMinutes($start, $minutes), $phrase);
    }

    /**
     * An event in the day's area with an occurrence overlapping the window, at that occurrence's time (capped at
     * three hours), if the slot is free.
     */
    private function addEventInWindow(Day $day, Timeline $timeline, string $from, string $to, string $returnBy, ?array $onlyIds = null): ?Activity
    {
        if ($from >= $to) {
            return null;
        }
        $windowStart = $day->date . ' ' . $from;
        $windowEnd = $day->date . ' ' . $to;
        $query = Event::betweenDates($windowStart, $windowEnd)
            ->whereNotIn('events.id', $this->getItinerary()->event_ids)
            ->whereHas('location', fn($q) => $q->where('area_id', $day->area_id));
        if ($onlyIds !== null) {
            $query->whereIn('events.id', $onlyIds);
        }

        foreach ($query->inRandomOrder()->limit(5)->get() as $event) {
            $occurrence = EventDate::where('event_id', $event->id)->overlapping($windowStart, $windowEnd)->orderBy('start_date')->first();
            if (!$occurrence) {
                continue;
            }
            $start = $this->later(date('H:i', strtotime($occurrence->start_date)), $from);
            $occurrenceEnd = date('Y-m-d', strtotime($occurrence->effective_end_date)) > $day->date
                ? '23:59' : date('H:i', strtotime($occurrence->effective_end_date));
            $end = $this->earlier($this->earlier($occurrenceEnd, Timeline::addMinutes($start, self::MAX_EVENT_MINUTES)), $returnBy);
            if ($end > $start && $timeline->isFree($start, $end)) {
                return $this->addTimed($day, $timeline, $event, ActivityType::EVENT, $start, $end);
            }
        }
        return null;
    }

    private function addPriorityEvents(Day $day, Timeline $timeline, string $returnBy): void
    {
        $ids = $this->getItinerary()->getEventPriorities();
        if ($ids) {
            $this->addEventInWindow($day, $timeline, '06:00', '23:59', $returnBy, $ids);
        }
    }

    /**
     * A priority location (museum, viewpoint, visitor center...) in the day's area, as 90 minutes of sightseeing.
     */
    private function addPriorityLocation(Day $day, Timeline $timeline, string $arrival, string $returnBy): void
    {
        $ids = array_diff($this->getItinerary()->getLocationPriorities(), $this->getItinerary()->location_ids);
        if (!$ids) {
            return;
        }
        $location = Location::whereIn('id', $ids)->where('area_id', $day->area_id)->first();
        if (!$location) {
            return;
        }
        $start = $timeline->findSlot(self::SIGHTSEEING_MINUTES, $this->later('09:00', $arrival),
            $this->earlier('17:00', Timeline::addMinutes($returnBy, -self::SIGHTSEEING_MINUTES)));
        if ($start) {
            $this->addTimed($day, $timeline, $location, ActivityType::SIGHTSEEING, $start, Timeline::addMinutes($start, self::SIGHTSEEING_MINUTES));
        }
    }

    private function addTimed(Day $day, Timeline $timeline, $resource, string $type, string $start, string $end, ?string $phrase = null): Activity
    {
        $timeline->reserve($start, $end);
        return $this->getItinerary()->addActivity($resource, $type, $start, $end, $day->day_num, null, null, null, $phrase);
    }

    /**
     * Look at what's happening in the day, and add some extra text, if appropriate.
     */
    public function addPreamble(Day $day)
    {
        if ($this->isArrivalDay($day) || $this->isDepartureDay($day)) {
            return;
        }
        if ($this->isAtBase($day) && !$day->hasEvent() && !$day->hasLocation() && !$day->hasTrail()) {
            $day->setPreamble($this->phraseService->getPhraseForRelaxing());
        }
    }

    private function nextPriorityTrailFor(Day $day): ?Trail
    {
        foreach ($this->getItinerary()->getTrailPriorities(true) as $trailId) {
            if (in_array($trailId, $this->getItinerary()->trail_ids, true)) {
                continue;
            }
            $trail = Trail::where('id', $trailId)->where('area_id', $day->area_id)->whereNotNull('distance_miles')->first();
            if ($trail) {
                return $trail;
            }
        }
        return null;
    }

    private function activityAt(Day $day, string $time): ?Activity
    {
        foreach ($day->getActivities() as $activity) {
            if ($activity->start_time <= Itinerary::normalizeTime($time) && $activity->end_time > Itinerary::normalizeTime($time)) {
                return $activity;
            }
        }
        return null;
    }

    /**
     * Pick an area for every day of the trip.
     *
     * Arrival and departure days are at the base area. Then every area the user asked for (through priority trails,
     * events, places, restaurants or areas) gets a day. The rest are sampled from the areas that pass the filters:
     * open for the season, have trails in the user's distance range, close enough for low adventurousness, and (below
     * 0.8) outlying areas at most once.
     */
    public function assignDaysForAreas(bool $useFirstDay = false, bool $useLastDay = false): array
    {
        $itinerary = $this->getItinerary();
        $adventurousness = $itinerary->getAdventurousness();
        $baseArea = $itinerary->getBaseArea();
        $durationDays = $itinerary->getDurationDays();
        $date = $itinerary->getStartDate();
        $days = [];
        $areaIds = [];
        $dayNum = 0;

        if (!$useFirstDay) {
            $days[$dayNum] = new Day(['date' => $date, 'area' => $baseArea, 'day_num' => $dayNum, 'start_time' => Day::DEFAULT_ARRIVAL_TIME]);
            $dayNum++;
            $date = $this->incrementDate($date);
            $areaIds[] = $baseArea->id;
        }
        if (!$useLastDay && $durationDays > 1) {
            $days[$durationDays - 1] = new Day([
                'date' => $this->calculateEndDate($itinerary->getStartDate(), $durationDays),
                'area' => $baseArea, 'day_num' => $durationDays - 1, 'end_time' => Day::DEFAULT_DEPARTURE_TIME,
            ]);
            $areaIds[] = $baseArea->id;
        }

        foreach ($this->requestedAreaIds() as $areaId) {
            if (count($days) >= $durationDays) {
                break;
            }
            $days[$dayNum] = new Day(['date' => $date, 'area' => Area::find($areaId), 'day_num' => $dayNum]);
            $dayNum++;
            $date = $this->incrementDate($date);
            $areaIds[] = $areaId;
        }

        while (count($days) < $durationDays) {
            $area = Area::pickWeighted($this->eligibleAreas($areaIds), $adventurousness) ?? $baseArea;
            $days[$dayNum] = new Day(['date' => $date, 'area' => $area, 'day_num' => $dayNum]);
            $dayNum++;
            $date = $this->incrementDate($date);
            $areaIds[] = $area->id;
        }

        $itinerary->setDays($days);
        return $itinerary->getDays();
    }

    /**
     * Areas the trip must include, most-requested first, skipping any closed for the season.
     */
    private function requestedAreaIds(): array
    {
        $itinerary = $this->getItinerary();
        $counts = [];
        $add = function($ids) use (&$counts) {
            foreach ($ids as $id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        };
        $add(Trail::whereIn('id', $itinerary->getTrailPriorities())->whereNotNull('distance_miles')->pluck('area_id'));
        $add(Location::whereIn('id', $itinerary->getLocationPriorities())->pluck('area_id'));
        $add(Restaurant::with('location')->whereIn('id', $itinerary->getRestaurantPriorities())->get()->pluck('location.area_id')->filter());
        $add(Event::with('location')->whereIn('id', $itinerary->getEventPriorities())
            ->betweenDates($itinerary->getStartDate() . ' 00:00:00', $itinerary->getEndDate() . ' 23:59:59')
            ->get()->pluck('location.area_id')->filter());
        $add($itinerary->getAreaPriorities());
        arsort($counts);

        $open = $this->openAreas()->pluck('id')->all();
        return array_values(array_filter(array_keys($counts), fn($id) => in_array($id, $open, true)
            && $id !== $itinerary->getBaseAreaId()));
    }

    private function openAreas()
    {
        $itinerary = $this->getItinerary();
        return Area::openOn($itinerary->getStartDate())->openOn($itinerary->getEndDate())->get();
    }

    private function eligibleAreas(array $visitedAreaIds): Collection
    {
        $itinerary = $this->getItinerary();
        $adventurousness = $itinerary->getAdventurousness();
        $baseArea = $itinerary->getBaseArea();

        $query = Area::openOn($itinerary->getStartDate())->openOn($itinerary->getEndDate());
        if ($this->wantsTrails()) {
            $query->whereHas('trails', function($q) use ($itinerary) {
                $q->where('trails.distance_miles', '>=', $itinerary->calculateMinimumTrailDistance())
                    ->where('trails.distance_miles', '<=', $itinerary->calculateMaximumTrailDistance());
            });
        }

        // Low adventurousness keeps day trips close to base.
        $maxDistance = match (true) {
            $adventurousness < 0.3 => 60,
            $adventurousness < 0.4 => 75,
            default => null,
        };
        if ($maxDistance) {
            $query->whereProximity($baseArea->latitude, $baseArea->longitude, $maxDistance);
        }

        // If the user's adventurousness < 0.5 and they're staying in Zion, focus on Kanab, not St. George.
        if ($adventurousness < 0.5 && $baseArea->id === Area::ZION) {
            $query->where('id', '!=', Area::ST_GEORGE);
        }

        // Below 0.8, outlying areas (anything but the base and its close neighbours) are visited at most once.
        if ($adventurousness < 0.8) {
            $core = $this->coreAreaIds($baseArea);
            $counts = array_count_values($visitedAreaIds);
            $exclude = array_filter(array_keys($counts), fn($id) => !in_array($id, $core, true));
            $query->whereNotIn('id', $exclude);
        }

        return $query->get();
    }

    /** Areas close enough to the base to come up any number of times (from Kanab: Kanab and Zion). */
    private function coreAreaIds(Area $baseArea): array
    {
        return Area::whereProximity($baseArea->latitude, $baseArea->longitude, self::CORE_AREA_MILES)
            ->pluck('id')->push($baseArea->id)->unique()->values()->all();
    }

    /**
     * Move each priority event's area onto the date it happens (shuffleDays() reorders dates, so this runs after).
     */
    public function placePriorityEventDays(): void
    {
        $itinerary = $this->getItinerary();
        $ids = $itinerary->getEventPriorities();
        if (!$ids) {
            return;
        }
        $days = $itinerary->getDays();
        $occurrences = EventDate::whereIn('event_id', $ids)
            ->overlapping($itinerary->getStartDate() . ' 00:00:00', $itinerary->getEndDate() . ' 23:59:59')
            ->with('event.location')->orderBy('start_date')->get();

        foreach ($occurrences as $occurrence) {
            $areaId = $occurrence->event?->location?->area_id;
            $date = date('Y-m-d', strtotime($occurrence->start_date));
            foreach ($days as $day) {
                if ($day->date === $date && $areaId && !$this->isArrivalDay($day) && !$this->isDepartureDay($day)) {
                    $day->area = Area::find($areaId);
                }
            }
        }
    }

    /**
     * One-way drive in minutes: area_distances when we have it (either direction), otherwise straight-line distance
     * padded for roads at DEFAULT_DRIVING_MPH.
     */
    public function drivingMinutes(int $fromAreaId, int $toAreaId): int
    {
        if ($fromAreaId === $toAreaId) {
            return 0;
        }
        $distance = AreaDistance::where(fn($q) => $q->where(['from_area_id' => $fromAreaId, 'to_area_id' => $toAreaId]))
            ->orWhere(fn($q) => $q->where(['from_area_id' => $toAreaId, 'to_area_id' => $fromAreaId]))
            ->whereNotNull('driving_hours')->first();
        if ($distance) {
            return (int) round($distance->driving_hours * 60);
        }
        return (int) round(60 * $this->drivingMiles($fromAreaId, $toAreaId) / self::DEFAULT_DRIVING_MPH);
    }

    public function drivingMiles(int $fromAreaId, int $toAreaId): float
    {
        if ($fromAreaId === $toAreaId) {
            return 0.0;
        }
        $distance = AreaDistance::where(fn($q) => $q->where(['from_area_id' => $fromAreaId, 'to_area_id' => $toAreaId]))
            ->orWhere(fn($q) => $q->where(['from_area_id' => $toAreaId, 'to_area_id' => $fromAreaId]))
            ->whereNotNull('driving_miles')->first();
        if ($distance) {
            return (float) $distance->driving_miles;
        }
        $from = Area::find($fromAreaId);
        $to = Area::find($toAreaId);
        return $this->locationService->estimateDrivingDistance($from->latitude, $from->longitude, $to->latitude, $to->longitude);
    }

    /**
     * Round trips from base to each away day's area, in miles.
     */
    /** Every drive of the trip, stop to stop, in miles. */
    public function estimateTotalTravelDistance(): float
    {
        $total = 0.0;
        foreach ($this->getItinerary()->getDays() as $day) {
            $total += (float) $day->return_miles;
            foreach ($day->getActivities() as $activity) {
                $total += (float) $activity->travel_miles;
            }
        }
        return round($total);
    }

    /**
     * The drive from where they're staying to the day's area, for planning the day before its stops are known
     * (TravelPlanner::settle() then times the real drives). Curated area_distances rows win when there's no router
     * and no lodging pin.
     */
    private function driveToAreaMinutes(Day $day, ?Trail $firstStop = null): int
    {
        if ($this->isAtBase($day)) {
            return 0;
        }
        $itinerary = $this->getItinerary();
        $home = $itinerary->homePoint();
        $area = Area::find($day->area_id);
        $to = ($firstStop ? Point::make($firstStop->latitude, $firstStop->longitude) : null)
            ?? new Point($area->latitude, $area->longitude);
        $minutes = $this->travelTimes->leg(new Point($home['lat'], $home['lng']), $to,
            CarbonImmutable::parse($day->date . ' 08:00', config('routing.timezone')))->minutes;

        // Without a router, a curated area-to-area time (area_distances) is the better guess when it's longer.
        if (!$this->travelTimes->usesRouter() && !$itinerary->hasLodgingPin()) {
            $minutes = max($minutes, $this->drivingMinutes($itinerary->getBaseAreaId(), $day->area_id));
        }
        return $minutes;
    }

    /** Stops TravelPlanner::settle() may drop to get home in time: anything but meals, lodging and wish-list picks. */
    private function canDrop(Activity $activity): bool
    {
        $itinerary = $this->getItinerary();
        return match ($activity->resource_table) {
            'trails' => !in_array($activity->resource_id, $itinerary->getTrailPriorities(), false),
            'events' => !in_array($activity->resource_id, $itinerary->getEventPriorities(), false),
            'locations' => !in_array($activity->resource_id, $itinerary->getLocationPriorities(), false),
            default => false,
        };
    }

    private function rememberResource(?string $table, $id): void
    {
        $itinerary = $this->getItinerary();
        match ($table) {
            'events' => $itinerary->event_ids[] = $id,
            'locations' => $itinerary->location_ids[] = $id,
            'restaurants' => $itinerary->restaurant_ids[] = $id,
            'trails' => $itinerary->trail_ids[] = $id,
            default => null,
        };
    }

    private function isArrivalDay(Day $day): bool
    {
        return $day->day_num === 0 && !$this->getItinerary()->getUseFirstDay();
    }

    private function isDepartureDay(Day $day): bool
    {
        return $day->day_num === $this->getItinerary()->getDurationDays() - 1 && !$this->getItinerary()->getUseLastDay()
            && $this->getItinerary()->getDurationDays() > 1;
    }

    private function isAtBase(Day $day): bool
    {
        return $day->area_id === $this->getItinerary()->getBaseAreaId();
    }

    /**
     * No preferred activity types means "anything".
     */
    private function wantsTrails(): bool
    {
        $types = $this->getItinerary()->getActivityPriorities();
        return empty($types) || (bool) array_intersect($types, self::TRAIL_ACTIVITY_TYPES);
    }

    private function wantsEvents(): bool
    {
        if ($this->getItinerary()->getAdventurousness() < 0.5) {
            return false;
        }
        $types = $this->getItinerary()->getActivityPriorities();
        return empty($types) || $types === Itinerary::DEFAULT_ACTIVITY_PRIORITIES
            || (bool) array_intersect($types, self::EVENT_ACTIVITY_TYPES);
    }

    private function later(string $a, string $b): string
    {
        return Timeline::toMinutes($a) >= Timeline::toMinutes($b) ? $a : $b;
    }

    private function earlier(string $a, string $b): string
    {
        return Timeline::toMinutes($a) <= Timeline::toMinutes($b) ? $a : $b;
    }

    private function incrementDate($date) {
        return date('Y-m-d', strtotime($date . ' +1 day'));
    }

    public function setupItinerary(Request $request)
    {
        $itinerary = $this->getItinerary();

        if ($request->get('base_area_id')) {
            $area = Area::where('id', $request->get('base_area_id'))->first();
            if ($area) {
                $itinerary->setBaseArea($area);
                $itinerary->setBaseAreaId($area->id);
            }
        }

        $itinerary->name = $request->get('name');
        $itinerary->arrival_date_time = $request->get('arrival_datetime');
        $itinerary->departure_date_time = $request->get('departure_datetime');
        $itinerary->setCheckInTime($request->get('check_in'));
        $itinerary->setCheckOutTime($request->get('check_out'));
        $itinerary->setDurationDays($request->get('duration_days'));
        $itinerary->setEndDate($request->get('end_date'));
        $itinerary->setStartDate($request->get('start_date'));

        // Some methods should be called after others have been set.
        $month = $request->get('month');
        $preferredMonths = array_filter(array_map('intval', (array) ($request->get('preferred_months') ?? [])), fn($m) => $m >= 1 && $m <= 12);
        if (!$month && $preferredMonths && !$itinerary->getStartDate()) {
            $month = $preferredMonths[array_rand($preferredMonths)];
        }
        $itinerary->setMonth($month);
        $itinerary->setSeason($request->get('season'));

        // Where they're staying. Without a pin, days start and end in the middle of the base area (Itinerary::homePoint()).
        $itinerary->lodging_name = filled($request->get('lodging_name')) ? trim($request->get('lodging_name')) : null;
        $itinerary->setLatitude($request->filled('lat') ? (float) $request->get('lat') : null);
        $itinerary->setLongitude($request->filled('lng') ? (float) $request->get('lng') : null);
        $this->ensureLodgingIsInBaseArea();

        $itinerary->setAdventurousness($request->get('adventurousness'));
        $itinerary->setSolitariness($request->get('solitariness'));
        $itinerary->setStrenuousness($request->get('strenuousness'));
        $itinerary->setActivityPriorities($request->get('activities') ?? $request->get('preferred_activity_types'));
        // The Vue app sends *_ids; older clients sent "trails".
        $itinerary->setTrailPriorities($request->get('trail_ids') ?? $request->get('trails'));
        $itinerary->setEventPriorities($request->get('event_ids'));
        $itinerary->setLocationPriorities($request->get('location_ids'));
        $itinerary->setRestaurantPriorities($request->get('restaurant_ids'));
        $itinerary->setAreaPriorities($request->get('area_ids'));
        $itinerary->setAverageHikingSpeed($request->get('average_hiking_speed'));

        $this->determineAndSetMissingDates();
        $this->ensureBaseIsOpen(!$request->filled('start_date') && !$request->filled('month'), $preferredMonths);
    }

    /** A pin far from the chosen base area is almost certainly a mistake, and would make every drive wrong. */
    private function ensureLodgingIsInBaseArea(): void
    {
        $itinerary = $this->getItinerary();
        $base = $itinerary->getBaseArea();
        if (!$base || !$itinerary->hasLodgingPin()) {
            return;
        }
        $miles = $this->locationService->haversineDistance(
            $base->latitude, $base->longitude, $itinerary->getLatitude(), $itinerary->getLongitude());
        if ($miles > self::MAX_LODGING_MILES_FROM_BASE) {
            throw ValidationException::withMessages(['lat' => [sprintf(
                'That pin is %d miles from %s. Choose the area you\'re staying in, or move the pin.', round($miles), $base->name)]]);
        }
    }

    /**
     * The base has to be open for the whole stay (the North Rim closes for the winter). When we picked the dates
     * ourselves, from the preferred months, we try other dates first; otherwise the traveller has to choose.
     */
    private function ensureBaseIsOpen(bool $datesArePicked, array $preferredMonths): void
    {
        $itinerary = $this->getItinerary();
        $base = $itinerary->getBaseArea();
        $isClosed = fn() => $itinerary->getStartDate()
            && ($base->isClosedOn($itinerary->getStartDate()) || $base->isClosedOn($itinerary->getEndDate() ?? $itinerary->getStartDate()));
        if (!$base || !$isClosed()) {
            return;
        }

        if ($datesArePicked) {
            $months = $preferredMonths ?: range(1, 12);
            shuffle($months);
            foreach ($months as $month) {
                for ($attempt = 0; $attempt < 3; $attempt++) {
                    $itinerary->setStartDate(null);
                    $itinerary->setEndDate(null);
                    $itinerary->setMonth($month);
                    $this->determineAndSetMissingDates();
                    if (!$isClosed()) {
                        return;
                    }
                }
            }
        }

        throw ValidationException::withMessages(['base_area_id' => [sprintf(
            '%s is closed %s. Choose somewhere else to stay, or different dates.', $base->name, $base->closureDescription())]]);
    }

    /**
     * Most users probably won't have specific dates in mind. Just a season or month. We can decide on dates here.
     * TODO: Incorporate the reservation system, so we're favoring dates that have accommodation openings.
     */
    public function determineAndSetMissingDates()
    {
        $startDate = $this->getItinerary()->getStartDate();
        $endDate = $this->getItinerary()->getEndDate();
        $durationDays = $this->getItinerary()->getDurationDays();

        if ($startDate) {
            if ($endDate) {
                $dateStart = new \DateTime($startDate);
                $dateEnd = new \DateTime($endDate);
                $this->getItinerary()->setDurationDays($dateStart->diff($dateEnd)->days + 1);
                return;
            } else if ($durationDays) {
                $this->getItinerary()->setEndDate($this->calculateEndDate($startDate, $durationDays));
                return;
            }
        }

        if (!$startDate || !$endDate) {
            // Let's try the month.
            if ($this->getItinerary()->getMonth()) {
                // Pick a random date in the month that's at least 2 weeks ahead of the current date.
                $startDate = $this->getRandomDateInMonth($this->getItinerary()->getMonth());
                $this->getItinerary()->setStartDate($startDate);

                if ($durationDays) {
                    $this->getItinerary()->setEndDate($this->calculateEndDate($startDate, $durationDays));
                    return;
                }
            }

            // Let's try the season.
            if ($this->getItinerary()->getSeason()) {
                $startDate = Season::randomDateInSeason($this->getItinerary()->getSeason());
                $this->getItinerary()->setStartDate($startDate);

                if ($durationDays) {
                    $this->getItinerary()->setEndDate($this->calculateEndDate($startDate, $durationDays));
                    return;
                }
            }
        }
    }

    /**
     * Trip dates are inclusive: start + duration_days - 1. (determineAndSetMissingDates() already treats start/end as
     * inclusive when deriving the duration, so the two directions now agree.)
     */
    public function calculateEndDate(string $startDate, int $durationDays): string
    {
        return date('Y-m-d', strtotime($startDate . ' +' . max(0, $durationDays - 1) . ' days'));
    }

    public function getRandomDateInMonth($month, ?int $year = null, ?\DateTime $currentDate = null) {
        $month = (int) $month;
        $currentDate = $currentDate ? clone $currentDate : new \DateTime();

        $targetYear = $year ?? (int) $currentDate->format('Y');
        if ($year === null && (int) $currentDate->format('n') > $month) {
            $targetYear++; // Move to the next year if the specified month is in the past
        }

        // At least two weeks from now.
        $minimumDate = clone $currentDate;
        $minimumDate->add(new \DateInterval('P2W'));

        $randomDay = mt_rand(1, cal_days_in_month(CAL_GREGORIAN, $month, $targetYear));
        $randomDate = new \DateTime("$targetYear-$month-$randomDay");
        while ($randomDate < $minimumDate) {
            $randomDate->add(new \DateInterval('P1D'));
        }

        // Did we move outside the intended month? Let's try again, but this time jump forward a year.
        if ((int)$randomDate->format('n') !== $month) {
            $randomDate = new \DateTime($this->getRandomDateInMonth($month, $targetYear + 1, $currentDate));
        }
        return $randomDate->format('Y-m-d');
    }

    /**
     * Keep the first and last day, group the rest by area (so you aren't driving back and forth), and shuffle the
     * order of the groups and the days within them.
     */
    public function shuffleDays(): void
    {
        $days = array_values($this->getItinerary()->getDays());
        if (count($days) <= 3) {
            return;
        }

        $first = array_shift($days);
        $last = array_pop($days);

        $groups = [];
        foreach ($days as $day) {
            $groups[$day->area_id][] = $day;
        }
        shuffle($groups);
        $middle = [];
        foreach ($groups as $group) {
            shuffle($group);
            array_push($middle, ...$group);
        }

        $days = [$first, ...$middle, $last];
        $startDate = $this->getItinerary()->getStartDate();
        foreach ($days as $dayNum => $day) {
            $day->day_num = $dayNum;
            $day->date = date('Y-m-d', strtotime($startDate . ' +' . $dayNum . ' days'));
        }

        $this->getItinerary()->setDays($days);
    }
}
