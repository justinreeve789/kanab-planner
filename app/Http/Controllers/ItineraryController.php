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

    /**
     * Activity weight: 0.25
     * Event weight: 0.5 (they're not something anyone can do any day)
     * Trail weight: 0.25
     */
    public function createItinerary(Request $request)
    {
        $this->setupItinerary($request);
        $this->itineraryService->setItinerary($this->getItinerary());
        $days = $this->itineraryService->getDaysForAreas($this->getItinerary()->getUseFirstDay(), $this->getItinerary()->getUseLastDay());
        foreach ($days as $dayNum => $day) {
            $day = $this->itineraryService->fillDay($day, null, $dayNum);
        }
        $this->getItinerary()->setDays($days);
//        echo '<pre>'; print_r($this->getItinerary()); echo '</pre>';

//        echo $this->itinerary->getStartDate() . ' - ' . $this->getItinerary()->getEndDate() . '<br>';
//
//        foreach ($days as $day) {
//            echo '<strong>' . $day->date . '</strong><br>';
//            foreach ($day->periods as $name => $period) {
//                echo strtoupper($name) . ' - ' . Area::where('id', $period->getAreaId())->pluck('name')->first() . '<br><ul>';
//                foreach ($period->getActivities() as $activity) {
//                    echo '<li>';
//                    echo isset($activity) && isset($activity->name) ? $activity->name : (isset($activity->location->name) ? $activity->location->name : 'unknown');
//                    echo '</li>';
//                }
//                echo '</ul><br>';
//            }
//        }

        // Find out which events are within their dates. Since these are usually one-time events, we may want to weigh them heavier, especially if they match their preferences.
/*
        $events = $this->getEvents();

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
*/
        return response()->json($this->getItinerary());
    }

    private function getDeviations(): array
    {
        return $this->itineraryService->getDeviations();
    }

    private function getEvents()
    {
        $query = Event::betweenDates($this->itinerary->getStartDate(), $this->getItinerary()->getEndDate())
            ->with('data');
        $deviations = $this->getDeviations();
        if (!empty($deviations)) {
            $query = $query->sortByDeviation($deviations);
        }
        return $query->get();
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
        $tripPreference = $request->get('trip_preference');

        $this->getItinerary()->setBaseAreaId($request->get('base_area_id'));
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

        $this->getItinerary()->setAdventurousness($request->get('adventurousness'));
        $this->getItinerary()->setSolitariness($request->get('solitariness'));
        $this->getItinerary()->setStrenuousness($request->get('strenuousness'));
        $this->getItinerary()->setActivityPriorities($request->get('activities'));
        $this->getItinerary()->setTrailPriorities($request->get('trails'));
        $this->getItinerary()->setAverageHikingSpeed($request->get('average_hiking_speed'));

        $this->determineAndSetMissingDates();
    }

    /**
     * Most users probably won't have specific dates in mind. Just a season or month. We can decide on dates here.
     * TODO: Incorporate the reservation system, so we're favoring dates that have accommodation openings.
     */
    private function determineAndSetMissingDates()
    {
        if ($this->getItinerary()->getStartDate()) {
            if ($this->getItinerary()->getEndDate()) {
                // We're good. Just exit.
                return;
            } else if ($this->getItinerary()->getDurationDays()) {
                $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($this->getItinerary()->getStartDate() . ' +' . $this->getItinerary()->getDurationDays() . ' days')));
                return;
            }
        }

        if (!$this->getItinerary()->getStartDate() || !$this->getItinerary()->getEndDate()) {
            // Let's try the month.
            if ($this->getItinerary()->getMonth()) {
                // Pick a random date in the month that's at least 2 weeks ahead of the current date.
                $startDate = $this->getRandomDateInMonth($this->getItinerary()->getMonth());
                $this->getItinerary()->setStartDate($startDate);

                if ($this->getItinerary()->getDurationDays()) {
                    $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($this->getItinerary()->getStartDate() . ' +' . $this->getItinerary()->getDurationDays() . ' days')));
                    return;
                }
            }

            // Let's try the season.
            if ($this->getItinerary()->getSeason()) {
                $startDate = Season::randomDateInSeason($this->getItinerary()->getSeason());
                $this->getItinerary()->setStartDate($startDate);

                if ($this->getItinerary()->getDurationDays()) {
                    $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($this->getItinerary()->getStartDate() . ' +' . $this->getItinerary()->getDurationDays() . ' days')));
                    return;
                }
            }
        }
        throw new \Exception('Could not set dates: ' . print_r($this->getItinerary(), true));
    }

    private function getRandomDateInMonth($month) {
        // Get the current date
        $currentDate = new \DateTime();

        // Calculate the target year
        $targetYear = (int)$currentDate->format('Y');
        if ($currentDate->format('n') > $month) {
            $targetYear++; // Move to the next year if the specified month is in the past
        }

        // Calculate the minimum date (2 weeks from the current date)
        $minimumDate = clone $currentDate;
        $minimumDate->add(new \DateInterval('P2W'));

        // Generate a random date in the specified month
        $randomDay = mt_rand(1, cal_days_in_month(CAL_GREGORIAN, $month, $targetYear));
        $randomDate = new \DateTime("$targetYear-$month-$randomDay");

        // Check if the random date is at least 2 weeks ahead
        if ($randomDate < $minimumDate) {
            // If not, add 2 weeks to the random date
            $randomDate->add(new \DateInterval('P2W'));
        }

        return $randomDate->format('Y-m-d');
    }
}
