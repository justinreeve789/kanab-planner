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
use App\Services\EventService;
use App\Services\ItineraryService;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    const DEFAULT_AREA = Area::KANAB;
    const DEFAULT_DAYS = 5;
    const DEFAULT_SEASON = 'summer';
    const DEFAULT_DISTANCE = 30;

    private $conversionService;
    private $itinerary;
    private $itineraryService;

    public function __construct()
    {
        $this->conversionService = new ConversionService();
        $this->itineraryService = new ItineraryService();
    }

    public function index(Request $request)
    {
    }

    private function getItinerary()
    {
        return $this->itinerary;
    }

    private function setupItinerary(Request $request)
    {
        $this->itinerary = new Itinerary();

        $accommodationPreference = $request->get('accommodation_preference');
        $arrivalDateTime = $request->get('arrival_datetime');
        $departureDateTime = $request->get('departure_datetime');
        $interests = $request->get('interests');
        $priorities = ['trails', 'events', 'locations', 'restaurants'];
        $tripPreference = $request->get('trip_preference');

        $this->getItinerary()->setCheckInTime($request->get('check_in'));
        $this->getItinerary()->setCheckOutTime($request->get('check_out'));
        $this->getItinerary()->setDurationDays($request->get('duration_days'));
        $this->getItinerary()->setEndDate($request->get('end_date'));
        $this->getItinerary()->setLatitude($request->get('lat'));
        $this->getItinerary()->setLongitude($request->get('lng'));
        $this->getItinerary()->setStartDate($request->get('start_date'));

        // Some methods should be called after others have been set.
        $this->getItinerary()->setMonth($request->get('month'));
        $this->getItinerary()->setSeason($request->get('season'));

        if (!$this->getItinerary()->getLatitude() || !$this->getItinerary()->getLongitude()) {
            $areaId = $request->get('area_id');
            if ($areaId) {
                $area = Area::where('id', $areaId)->first();
                $this->getItinerary()->setLatitude($area->latitude);
                $this->getItinerary()->setLongitude($area->longitude);
            }
        }

        $adventurousness = $request->get('adventurousness');
        $solitariness = $request->get('solitariness');
        $strenuousness = $request->get('strenuousness');
    }

    /**
     * Most users probably won't have specific dates in mind. Just a season or month. We can decide on dates here.
     * TODO: Incorporate the reservation system, so we're favoring dates that have accommodation openings.
     */
    private function determineDates()
    {
    }

    /**
     * Activity weight: 0.25
     * Event weight: 0.5 (they're not something anyone can do any day)
     * Trail weight: 0.25
     */
    public function createItinerary(Request $request)
    {
        $this->setupItinerary($request);

        echo $this->itinerary->getStartDate() . ' - ' . $this->getItinerary()->getEndDate() . '<br>';

        // Find out which events are within their dates. Since these are usually one-time events, we may want to weigh them heavier, especially if they match their preferences.
        $events = Event::between($this->itinerary->getStartDate(), $this->getItinerary()->getEndDate())
            ->with('data')
            ->sortByDeviation(['adventurousness' => $this->itinerary->adventurousness, 'solitariness' => $this->itinerary->solitariness, 'strenuousness' => $this->itinerary->strenuousness])
            ->get();

        $locations = Location::with('data')
            ->whereIsOpen('2023-10-01 12:00:00')
            ->whereProximity($this->getItinerary()->getLatitude(), $this->getItinerary()->getLongitude(), self::DEFAULT_DISTANCE)
//            ->sortByDeviation(['adventurousness' => $this->itinerary->adventurousness, 'solitariness' => $this->itinerary->solitariness, 'strenuousness' => $this->itinerary->strenuousness]);
            ->get();

        $restaurants = Restaurant::with('data')
            ->with('location')
            ->sortByDeviation(['adventurousness' => $this->itinerary->adventurousness, 'solitariness' => $this->itinerary->solitariness, 'strenuousness' => $this->itinerary->strenuousness])
            ->get();

        $trails = Trail::with('data')
            ->whereProximity($this->getItinerary()->getLatitude(), $this->getItinerary()->getLongitude(), self::DEFAULT_DISTANCE)
            ->sortByDeviation(['strenuousness' => $this->itinerary->strenuousness])
            ->get();
        echo '<pre>'; print_r($trails->toArray()); echo '</pre>';
        return;
    }

    /**
     * Get areas for days.
     */
    public function getAreasForDays()
    {}
}
