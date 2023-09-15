<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Itinerary\Itinerary;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Traits\ActivityData;
use App\Services\ConversionService;
use App\Services\EventService;
use App\Services\ItineraryService;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    private $conversionService;
    private $itinerary;
    private $itineraryService;

    public function __construct()
    {
        $this->itinerary = new Itinerary();
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
        $accommodationPreference = $request->get('accommodation_preference');
        $arrivalDateTime = $request->get('arrival_datetime');
        $checkInTimeOfDay = $request->get('check_in');
        $checkOutTimeOfDay = $request->get('check_out');
        $departureDateTime = $request->get('departure_datetime');
        $durationDays = $request->get('durationDays');
        $endDate = $request->get('end_date');
        $interests = $request->get('interests');
        $startDate = $request->get('start_date');
        $tripPreference = $request->get('trip_preference');

//        $response = $this->openAIService->query('Create a vacation itinerary for Kanab, Utah from October 3, 2023 to October 10, 2023. Display dining and activities for morning, afternoon, and evening each day. Place more weight on rock climbing and hiking and escape rooms.');
//        echo $response;

        $itinerary = new Itinerary();
        $itinerary->setDates($startDate . ' ' . $checkInTimeOfDay, $endDate . ' ' . $checkOutTimeOfDay);

        // Find out which events are within their dates. Since these are usually one-time events, we may want to weigh them heavier, especially if they match their preferences.
        $events = Event::between($startDate, $endDate)
            ->with('data')
            ->sortByDeviation('adventurousness', $this->itinerary->adventurousness)
            ->get();

        $restaurants = Restaurant::with('data')->with('location')->get();
        foreach ($restaurants as $item) {
//            echo $item->location->name . ' ' . $item->average_price . ' ' . $item->adventurousness() . ' ' . $item->solitariness() . ' ' . $item->strenuousness() . '<br>';
        }

        $trails = Trail::with('data')
            ->sortByDeviation('strenuousness', $this->itinerary->strenuousness)
            ->get();
        echo '<pre>'; print_r($trails->toArray()); echo '</pre>';
        return;
    }
}
