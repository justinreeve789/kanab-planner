<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
    private $itineraryService;

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
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $durationDays = $request->get('durationDays');
        $checkInTimeOfDay = $request->get('check_in');
        $checkOutTimeOfDay = $request->get('check_out');
        $arrivalDateTime = $request->get('arrival_datetime');
        $departureDateTime = $request->get('departure_datetime');
        $accommodationPreference = $request->get('accommodation_preference');
        $tripPreference = $request->get('trip_preference');
        $interests = $request->get('interests');

//        $response = $this->openAIService->query('Create a vacation itinerary for Kanab, Utah from October 3, 2023 to October 10, 2023. Display dining and activities for morning, afternoon, and evening each day. Place more weight on rock climbing and hiking and escape rooms.');
//        echo $response;

        $events = Event::between($startDate, $endDate)->with('data')->get();
        foreach ($events as $event) {
        }

        $restaurants = Restaurant::with('data')->with('location')->get();
        foreach ($restaurants as $item) {
            echo $item->location->name . ' ' . $item->average_price . ' ' . $item->adventurousness() . ' ' . $item->solitariness() . ' ' . $item->strenuousness() . '<br>';
        }

        $trails = Trail::with('data')->get();
        foreach ($trails as $item) {
            $item->setActivityType('hiking');
            echo $item->name . ' ' . $item->adventurousness() . ' ' . $item->solitariness() . ' ' . $item->strenuousness() . ' - ' . $item->distance_miles . ' miles<br>';
        }
        return;
    }
}
