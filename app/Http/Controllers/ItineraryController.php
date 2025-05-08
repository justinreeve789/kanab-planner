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
    const MINIMUM_TRAIL_DAYS_RATIO_TO_TOTAL_POSSIBLE = 0.8;

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
        $this->itineraryService->getItinerary()->setTotalPossibleTrailDays($this->itineraryService->calculateTotalPossibleTrailDays());
        $this->itineraryService->getItinerary()->minimum_trail_days = max(1, floor(self::MINIMUM_TRAIL_DAYS_RATIO_TO_TOTAL_POSSIBLE * $this->itineraryService->getItinerary()->getTotalPossibleTrailDays()));
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

    public function loadItinerary(Request $request, $id)
    {
        $itinerary = Itinerary::with(['days.periods.activities'])->where('id', $id)->first();
        return $itinerary;
    }

    /**
     * @return Itinerary
     */
    private function getItinerary()
    {
        return $this->itineraryService->getItinerary();
    }

    private function saveItinerary()
    {
        $this->itineraryService->saveItinerary();
    }
}
