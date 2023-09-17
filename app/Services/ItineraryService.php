<?php

namespace App\Services;

use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Season;
use App\Models\Area;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ItineraryRepository;
use Illuminate\Database\Eloquent\Model;

class ItineraryService
{
    private $itinerary;
    private $repository;
    private $seasons;

    public function __construct($itinerary = null)
    {
        $this->setItinerary($itinerary);
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

    /**
     * We'll need to examine the priorities and make sure we include those areas.
     */
    public function getAreasForDays(bool $useFirstDay = false, bool $useLastDay = false)
    {
        // Default to the user's accommodation area for the first day, if $useFirstDay is false.
        $days = ($useFirstDay === false) ? [$this->getItinerary()->getBaseAreaId()] : [];

        $durationDays = $this->getItinerary()->getDurationDays();
        $activityPriorities = $this->getItinerary()->getActivityPriorities();
        $trailPriorities = $this->getItinerary()->getTrailPriorities();

        // Get the areas for the trails. Weight the areas that appear the most so they are first in the array.
        $trailAreaIds = Trail::select('area_id', \DB::raw('COUNT(area_id) AS occurrences'))->whereIn('id', $trailPriorities)->groupBy('area_id')->orderByRaw('COUNT(area_id) DESC')->get()->keyBy('area_id');

        // Now find activities in those areas with the priorities.
        echo '<pre>'; print_r($trailAreaIds->toArray()); echo '</pre>';
    }
}