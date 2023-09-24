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
        $days = $this->itineraryService->assignDaysForAreas($this->getItinerary()->getUseFirstDay(), $this->getItinerary()->getUseLastDay());
        $this->shuffleDays();
        $this->getItinerary()->setTotalPossibleTrails($this->itineraryService->calculateTotalPossibleTrailsForTrip());
        foreach ($days as $dayNum => $day) {
            $day = $this->itineraryService->fillDay($day);
        }
        $this->getItinerary()->setDays($days);

        // TODO: If the user is visiting two or more national parks or monuments, or spending two or more days at the
        // same national park or monument, advise they purchase a national park pass.

        // TODO: If we have a situation where the last days aren't as well-filled as earlier days, let's shuffle them
        // around. Take into account days and hours that trails and locations are open.

        $this->getItinerary()->save();

        return response()->json($this->getItinerary());
    }

    /**
     * Keep the first and last day, and shuffle the rest.
     */
    private function shuffleDays()
    {
        $days = $this->getItinerary()->getDays();
        if (count($days) <= 3) {
            return $days;
        }
        $first = array_shift($days);
        $last = array_pop($days);
        shuffle($days);
        $days = array_merge([$first], $days, [$last]);
        foreach ($days as $dayNum => $day) {
            $day->dayNum = $dayNum;
        }
        $this->getItinerary()->setDays($days);
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

        if ($request->get('base_area_id')) {
            $area = Area::with('weather')->where('id', $request->get('base_area_id'))->first();
            if ($area) {
                $this->getItinerary()->setBaseArea($area);
                $this->getItinerary()->setBaseAreaId($request->get('base_area_id'));
            }
        }

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
        $startDate = $this->getItinerary()->getStartDate();
        $endDate = $this->getItinerary()->getEndDate();
        $durationDays = $this->getItinerary()->getDurationDays();

        if ($startDate) {
            if ($endDate) {
                $dateStart = new \DateTime($startDate);
                $dateEnd = new \DateTime($endDate);
                $this->getItinerary()->setDurationDays($dateStart->diff($dateEnd)->days + 1);
                return;
            } else if ($durationDays) {
                $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($startDate . ' +' . $durationDays . ' days')));
                return;
            }
        }

        if (!$startDate || !$endDate) {
            // Let's try the month.
            if ($this->getItinerary()->getMonth()) {
                // Pick a random date in the month that's at least 2 weeks ahead of the current date.
                $startDate = $this->getRandomDateInMonth($this->getItinerary()->getMonth());
                $this->getItinerary()->setStartDate($startDate);

                if ($durationDays) {
                    $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($startDate . ' +' . $durationDays . ' days')));
                    return;
                }
            }

            // Let's try the season.
            if ($this->getItinerary()->getSeason()) {
                $startDate = Season::randomDateInSeason($this->getItinerary()->getSeason());
                $this->getItinerary()->setStartDate($startDate);

                if ($durationDays) {
                    $this->getItinerary()->setEndDate(date('Y-m-d', strtotime($startDate . ' +' . $durationDays . ' days')));
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
