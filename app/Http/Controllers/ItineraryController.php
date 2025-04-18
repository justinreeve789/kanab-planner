<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Event;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Season;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Traits\ActivityData;
use App\Services\ConversionService;
use App\Services\ItineraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItineraryController extends Controller
{
    const DEFAULT_AREA = Area::KANAB;
    const DEFAULT_DAYS = 5;
    const DEFAULT_SEASON = 'summer';
    const DEFAULT_DISTANCE = 30;

    private $conversionService;
    private $itinerary;
    private $itineraryService;
    private $locationService;

    public function __construct()
    {
        $this->conversionService = new ConversionService();
        $this->itineraryService = new ItineraryService();
    }

    public function index(Request $request)
    {
    }

    /**
     * Activity weight: 0.25
     * Event weight: 0.5 (they're not something anyone can do any day)
     * Trail weight: 0.25
     */
    public function createItinerary(Request $request)
    {
        $this->itineraryService->setupItinerary($request);
        $this->itineraryService->assignDaysForAreas($this->getItinerary()->getUseFirstDay(), $this->getItinerary()->getUseLastDay());
        $this->itineraryService->shuffleDays();
        $this->itineraryService->getItinerary()->setTotalPossibleTrails($this->itineraryService->calculateTotalPossibleTrailsForTrip());
        foreach ($this->itineraryService->getItinerary()->getDays() as $dayNum => $day) {
            $this->itineraryService->fillDay($day);
        }

        // TODO: If the user is visiting two or more national parks or monuments, or spending two or more days at the
        // same national park or monument, advise they purchase a national park pass.

        // TODO: If we have a situation where the last days aren't as well-filled as earlier days, let's shuffle them
        // around. Take into account days and hours that trails and locations are open.

//        $this->itineraryService->adjustDays();

//        $this->getItinerary()->setTotalTravelDistance($this->itineraryService->estimateTotalTravelDistance());

        $this->saveItinerary();

//        return response()->json($this->itineraryService->getItinerary());
        return $this->itineraryService->getItinerary();
    }

    /**
     * @return Itinerary
     */
    private function getItinerary()
    {
        return $this->itineraryService->getItinerary();
    }

    /**
     * TODO: Fix the model definitions so we're not doing any of this offsetUnset() and setRelation() stuff.
     */
    private function saveItinerary()
    {
        DB::transaction(function() {
            $itinerary = clone $this->getItinerary();
            $days = $itinerary->getDays();
            $itinerary->offsetUnset('days');
//            $itinerary->setRelation('days', collect($days));
            $itinerary->save();
            $itinerary->days = $days;

            foreach ($days as $day) {
                $day = clone $day;
                $day->itinerary_id = $itinerary->id;
                $day->area_id = $day->area->id;
                $area = $day->area;
                $day->offsetUnset('area');
                $day->offsetUnset('formatted_abbr_month_day');
                $day->offsetUnset('formatted_date');
                $day->offsetUnset('formatted_day_of_week');
                $day->offsetUnset('formatted_month_day');
                $day->offsetUnset('formatted_year');
                $day->setRelation('area', $area);
                $day->save();

                foreach ($day->periods as $period) {
                    $period->day_id = $day->id;
                    $period->type = (string) $period->type;
                    $period->save();
                    $period->type = (int) $period->type;
                    $activities = clone $period->activities;

                    foreach ($activities as $activity) {
                        $activity->period_id = $period->id;
                        $resource = $activity->resource;
                        $activity->offsetUnset('resource');
                        $activity->setRelation('resource', $resource);
                        $activity->resource_table = $resource->getTable();
                        $activity->resource_id = $resource->id;
                        $activityType = $activity->activity_type;
                        $activity->offsetUnset('activity_type');
                        $activity->activity_type_id = $activity->activity_type_id ?? 0;
                        $activity->save();
                        $activity->activity_type = $activityType;
                    }
                }
            }
        });
    }
}
