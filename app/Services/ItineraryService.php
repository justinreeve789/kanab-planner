<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Event;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
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

    public function getResourcesNearAdventurousness(Model $model, float $adventurousness)
    {
        return $this->getRepository()->getResourcesNearAdventurousness(with(new $model)->getTable(), $adventurousness);
    }

    public function fillDay(Day $day)
    {
        // Add a restaurant in the area for the morning.
//        $restaurant = Restaurant::where('area_id', $day->getAreaId())->whereIsOpen('10:00')->inRandomOrder()->first();
        $restaurant = Restaurant::with('location')->whereHas('location', function ($query) use ($day) {
            $query->where('area_id', $day->getAreaId());
        })->inRandomOrder()->first();
        $day->periods[Period::MORNING]->addActivity($restaurant);

        // Using the trail priorities for the day's area, determine when the user should best start the trail.
        foreach ($this->getItinerary()->getTrailPriorities(true) as $trailId) {
            $trail = Trail::where('id', $trailId)->first();
            if ($trail->area_id == $day->getAreaId() && !$this->getItinerary()->trailExists()) {
                $period = $this->trailService->getBestPeriodOfDayForTrail($trail);
                $day->periods[$period]->addActivity($trail);
                break;
            }
        }

        // Add a restaurant in the area for the evening.
//        $restaurant = Restaurant::where('area_id', $day->getAreaId())->whereIsOpen('17:00')->inRandomOrder()->first();
        $restaurant = Restaurant::with('location')->whereHas('location', function ($query) use ($day) {
            $query->where('area_id', $day->getAreaId());
        })->inRandomOrder()->first();
        $day->periods[Period::EVENING]->addActivity($restaurant);

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
//            $days = [$date => new Day($date, $this->getItinerary()->getBaseAreaId())];
            $days[$dayNum++] = new Day($date, $this->getItinerary()->getBaseAreaId());
            $date = $this->incrementDate($date);
        } else {
            $days = [];
        }

        // Do the same for the last day.
        if ($useLastDay === false) {
//            $days[$this->getItinerary()->getEndDate()] = new Day($this->getItinerary()->getEndDate(), $this->getItinerary()->getBaseAreaId());
            $days[$durationDays - 1] = new Day($this->getItinerary()->getEndDate(), $this->getItinerary()->getBaseAreaId());
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
//                $days[$date] = new Day($date, $areaId);
                $days[$dayNum++] = new Day($date, $areaId);
                $date = $this->incrementDate($date);
            }
        }

        // Fill in any remaining days.
        do {
            // TODO: Don't be so random about the area. Get an area with other events they'd be interested in. Also take
            // into account other preferences like maximum distance they want to travel.
            $days[$dayNum++] = new Day($date, Area::inRandomOrder()->value('id'));
            $date = $this->incrementDate($date);
        } while (count($days) < $durationDays + 1); // TODO: Why does this need to be +1?
        ksort($days);

        return $days;
    }

    private function incrementDate($date) {
        return date('Y-m-d', strtotime($date . ' +1 day'));
    }
}