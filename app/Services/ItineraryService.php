<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Event;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Models\Itinerary\Task;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Services\TrailService;
use App\Repositories\ItineraryRepository;
use Illuminate\Database\Eloquent\Model;

class ItineraryService
{
    private $itinerary;
    private $repository;
    private $seasons;
    private $trailService;

    public function __construct($itinerary = null)
    {
        $this->setItinerary($itinerary);
        $this->trailService = new TrailService();
//        $this->seasons = Season::all();
    }

    public function getItinerary(): ?Itinerary
    {
        return $this->itinerary;
    }

    public function setItinerary(?Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

    public function getRepository()
    {
        if (!isset($this->repository)) {
            $this->repository = new ItineraryRepository();
        }
        return $this->repository;
    }

    public function getDeviations(): array
    {
        $deviations = [];
        if ($this->getItinerary()->getAdventurousness()) {
            $deviations['adventurousness'] = $this->getItinerary()->getAdventurousness();
        }
        if ($this->getItinerary()->getSolitariness()) {
            $deviations['adventurousness'] = $this->getItinerary()->getSolitariness();
        }
        if ($this->getItinerary()->getStrenuousness()) {
            $deviations['strenuousness'] = $this->getItinerary()->getStrenuousness();
        }
        return $deviations;
    }

    /**
     * Let's go hour by hour and figure out where activities fit. Breakfast ~8:00am, lunch ~12:00pm, dinner ~5:00pm.
     * Make sure there's at least 3 hours between breakfast and lunch, and 4 hours between lunch and dinner. If lunch
     * won't be possible, set a "bringSnacks" flag in the overlapping activity to true.
     */
    public function fillDay(Day $day)
    {
        // TODO: getEventPriorities()

        $curTime = $day->startTime;
        $dayNum = $day->dayNum;
        $durationDays = $this->getItinerary()->getDurationDays();

        // If this is the first day and they're arriving at a normal late time, let's not plan any activities. Just
        // grocery shopping, maybe fast food.
        if ($dayNum === 0 && !$this->getItinerary()->getUseFirstDay()) {
            $day->periods[Period::EVENING]->addActivity(new Task([
                'name' => 'Arrive at your accommodations',
                'description' => 'Get situated. If there\'s time, drop by the grocery store to pick up a few things.',
            ]));
            return;
        }

        // If this is the first day and they're departing at a normal early time, let's not plan any activities.
        if ($dayNum === $durationDays && !$this->getItinerary()->getUseLastDay()) {
            echo $dayNum . ' ' . $durationDays . ' ';
            $day->periods[Period::MORNING]->addActivity(new Task([
                'name' => 'Check out from  your accommodations',
                'description' => 'Hit the road, reminisce on the adventures you had, and travel safely.',
            ]));
            return;
        }

        // Using the trail priorities for the day's area, determine when the user should best start the trail.
        foreach ($this->getItinerary()->getTrailPriorities(true) as $trailId) {
            $trail = Trail::where('id', $trailId)->first();
            if ($trail->area_id == $day->getAreaId() && !$this->getItinerary()->trailExists()) {
                $period = $this->trailService->getBestPeriodOfDayForTrail($trail);
                $day->periods[$period]->addActivity($trail);
                break;
            }
        }

        // Are there possible events for the day to fill in any gaps?
        if (empty($day->periods[Period::AFTERNOON]->getActivities())) {
            $event = Event::with('location')->whereHas('location', function($query) use ($day) {
                $query->where('area_id', $day->getAreaId());
            })->sortByDeviation($this->getDeviations())->limit(5)->inRandomOrder()->first();
            if ($event && !$this->getItinerary()->eventExists()) {
                $day->periods[Period::AFTERNOON]->addActivity($event);
            }
        }

        if (empty($day->periods[Period::EVENING]->getActivities())) {
            $event = Event::betweenDates($day->date . ' 12:00:00', $day->date . ' 16:00:00')->with('location')->whereHas('location', function($query) use ($day) {
                $query->where('area_id', $day->getAreaId());
            })->inRandomOrder()->first();
            if ($event) {
                $day->periods[Period::EVENING]->addActivity($event);
            }
        }

        if (empty($day->periods[Period::EVENING]->getActivities())) {
            $event = Event::betweenDates($day->date . ' 12:00:00', $day->date . ' 16:00:00')->with('location')->whereHas('location', function($query) use ($day) {
                $query->where('area_id', $day->getAreaId());
            })->inRandomOrder()->first();
            if ($event) {
                $day->periods[Period::EVENING]->addActivity($event);
            }
        }

        // Are there trails for the day to fill in any gaps?
        if (empty($day->periods[Period::MORNING]->getActivities())) {
            $trail = $this->trailService->getRandomTrailForPeriod($day->getAreaId(), Period::MORNING);
            if ($trail && !$this->getItinerary()->trailExists()) {
                $day->periods[Period::MORNING]->addActivity($trail);
            }
        }

        if (empty($day->periods[Period::AFTERNOON]->getActivities())) {
            $trail = $this->trailService->getRandomTrailForPeriod($day->getAreaId(), Period::AFTERNOON);
            if ($trail && !$this->getItinerary()->trailExists()) {
                $day->periods[Period::AFTERNOON]->addActivity($trail);
            }
        }

        // Add a restaurant in the area for the morning.
        $restaurant = Restaurant::with('location')->whereIsOpen($day->date . ' 09:00:00')->whereHas('location', function($query) use ($day) {
            $query->where('area_id', $day->getAreaId());
        })->inRandomOrder()->first();
        $day->periods[Period::MORNING]->addActivity($restaurant);

        // Add a restaurant in the area for the evening.
        $restaurant = Restaurant::with('location')->whereIsOpen($day->date . ' 17:00:00')->whereHas('location', function($query) use ($day) {
            $query->where('area_id', $day->getAreaId());
        })->inRandomOrder()->first();
        $day->periods[Period::EVENING]->addActivity($restaurant);

        // TODO: $this->eventService()->getEventForDay(
        return $day;
    }

    /**
     * We'll need to examine the priorities and make sure we include those areas.
     */
    public function getDaysForAreas(bool $useFirstDay = false, bool $useLastDay = false): array
    {
        $durationDays = $this->getItinerary()->getDurationDays();
        $dayNum = 0;

        // Default to the user's accommodation area for the first day, if $useFirstDay is false.
        $date = $this->getItinerary()->getStartDate();
        if ($useFirstDay === false) {
            $day = new Day($date, $this->getItinerary()->getBaseAreaId(), $dayNum);
            $day->startTime = Day::DEFAULT_ARRIVAL_TIME;
            $days[$dayNum++] = $day;
            $date = $this->incrementDate($date);
        } else {
            $days = [];
        }

        // Do the same for the last day.
        if ($useLastDay === false) {
            $day = new Day($date, $this->getItinerary()->getBaseAreaId(), $durationDays - 1);
            $day->endTime = Day::DEFAULT_DEPARTURE_TIME;
            $days[$durationDays - 1] = $day;
        }

        $activityPriorities = $this->getItinerary()->getActivityPriorities();
        $trailPriorities = $this->getItinerary()->getTrailPriorities();

        // Get the areas for the trails. Weight the areas that appear the most so they are first in the array.
        // TODO: Make sure the trails are actually opening on the date.
        $trailAreaIds = Trail::select('area_id', \DB::raw('COUNT(area_id) AS occurrences'))->whereIn('id', $trailPriorities)->groupBy('area_id')->orderByRaw('COUNT(area_id) DESC')->get()->keyBy('area_id');

        // Add areas as long as we have days remaining in the duration of the trip.
        $modifier = $useLastDay ? 0 : -1;
        foreach ($trailAreaIds as $areaId => $tai) {
            if (count($days) < $durationDays + $modifier) {
                $day = new Day($date, $this->getItinerary()->getBaseAreaId(), $dayNum);
                $days[$dayNum++] = $day;
                $date = $this->incrementDate($date);
            }
        }

        // Fill in any remaining days.
        do {
            // TODO: Don't be so random about the area. Get an area with other events they'd be interested in. Also take
            // into account other preferences like maximum distance they want to travel.
            $day = new Day($date, Area::inRandomOrder()->value('id'), $dayNum);
            $days[$dayNum++] = $day;
            $date = $this->incrementDate($date);
        } while (count($days) < $durationDays + 1); // TODO: Why does this need to be +1?
        ksort($days);

        return $days;
    }

    private function incrementDate($date) {
        return date('Y-m-d', strtotime($date . ' +1 day'));
    }
}