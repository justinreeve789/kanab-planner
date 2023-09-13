<?php

namespace App\Http\Controllers;

use App\Services\ConversionService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    private $openAIService;
    private $conversionService;

    public function __construct()
    {
        $this->openAIService = new OpenAIService();
        $this->conversionService = new ConversionService();
    }

    public function index(Request $request)
    {
    }

    public function create(Request $request)
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

        $events = Event::between($startDate, $endDate);
        print_r($events);
        return;
    }
}
