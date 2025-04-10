<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\AreaDistance;
use App\Models\AreaWeather;
use App\Models\Builder\CustomQueryBuilder;
use App\Models\Event;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Period;
use App\Models\Itinerary\Season;
use App\Models\Itinerary\Task;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ItineraryRepository;
use App\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItineraryService
{
    const DEFAULT_DRIVING_TIME_BETWEEN_AREAS = 1800;

    private $imageService;
    private $itinerary;
    private $locationService;
    private $phraseService;
    private $repository;
    private $restaurantService;
    private $seasons;
    private $trailService;

    public function __construct($itinerary = null)
    {
        $this->setItinerary(new Itinerary());
        $this->imageService = new ImageService();
        $this->locationService = new LocationService();
        $this->phraseService = new PhraseService();
        $this->restaurantService = new RestaurantService();
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

    public function getDeviations(): array
    {
        $deviations = [];
        if ($this->getItinerary()->getAdventurousness()) {
            $deviations['adventurousness'] = $this->getItinerary()->getAdventurousness();
        }
        if ($this->getItinerary()->getSolitariness()) {
            $deviations['solitariness'] = $this->getItinerary()->getSolitariness();
        }
        if ($this->getItinerary()->getStrenuousness()) {
            $deviations['strenuousness'] = $this->getItinerary()->getStrenuousness();
        }
        return $deviations;
    }

    /**
     * A higher adventurousness rating will result in fewer trails.
     * 0.1 = ~1/6 of the days spent hiking.
     * 0.5 = ~1/3 of the days spent hiking.
     * 1.0 = ~3/5 of the days spent hiking.
     * TODO: An adventurousness of 1.0 should really be more like 4/5 or 5/6 of the days spent hiking.
     */
    public function calculateTotalPossibleTrailsForTrip(): int
    {
        $itinerary = $this->getItinerary();
        $adventurousness = $itinerary->getAdventurousness();
        $days = max([$itinerary->getDurationDays() - 2, 2]);
        return round(min([$days * 2 * (log(1 + (5 * $adventurousness), 10) / 1.5), $itinerary->getDurationDays()]));
    }

    /**
     * Let's go hour by hour and figure out where activities fit. Breakfast ~8:00am, lunch ~12:00pm, dinner ~5:00pm.
     * Make sure there's at least 3 hours between breakfast and lunch, and 4 hours between lunch and dinner. If lunch
     * won't be possible, set a "bringSnacks" flag in the overlapping activity to true.
     *
     * A combined less adventurous and more solitary setting will avoid taking them too far, such as St. George or Page.
     */
    public function fillDay(Day $day, $pass = false): void
    {
        // TODO: getEventPriorities()

//        $curTime = $day->startTime;
        $date = $day->date;
        $dayNum = $day->day_num;
        $durationDays = $this->getItinerary()->getDurationDays();

        // If this is the first day and they're arriving at a normal late time, let's not plan any activities. Just
        // grocery shopping, maybe fast food.
        if ($dayNum === 0 && !$this->getItinerary()->getUseFirstDay()) {
            $task = new Task([
                'name' => 'Arrival',
                'description' => $this->phraseService->getPhraseForCheckingIn() . ' ' . $this->phraseService->getPhraseForArrivingLateFood(),
            ]);
            $this->getItinerary()->addActivity($task, 'checking-in', Day::DEFAULT_ARRIVAL_TIME, '23:59:59', $dayNum, Period::EVENING);
            return;
        }

        // If this is the last day and they're departing at a normal early time, let's not plan any activities.
        if ($dayNum === ($durationDays - 1) && !$this->getItinerary()->getUseLastDay()) {
            $task = new Task([
                'name' => 'Departure',
                'description' => $this->phraseService->getPhraseForCheckingOut(),
            ]);
            $this->getItinerary()->addActivity(
                $task,
                'checking-out',
                Day::DEFAULT_DEPARTURE_TIME,
                '23:59:59',
                $dayNum,
                Period::MORNING);
            return;
        }

        // Using the trail priorities for the day's area, determine when the user should best start the trail.
        foreach ($this->getItinerary()->getTrailPriorities(true) as $trailId) {
            $trail = Trail::where('id', $trailId)->whereNotNull('distance_miles')->first();
            if ($trail) {
                if ($trail->area_id == $day->area_id && !$this->getItinerary()->trailExists($trail)) {
                    $trailPeriod = $this->trailService->getBestPeriodOfDayForTrail($trail);
                    break;
                }
            }
        }

        // If we have a trail for today, and it's in the morning, and in Zion, they probably won't have time to stop
        // to eat in the morning if they want to start on time. They should eat at home.
        if (isset($trail) && isset($trailPeriod) && $trailPeriod === Period::MORNING && $trail->area_id == Area::ZION) {
            $task = new Task([
                'name' => 'Breakfast',
                'description' => 'Eat breakfast at home to make sure you can get to the trail at an early hour. Ideally you\'ll reach the trail by 8:00am to avoid larger crowds.',
            ]);
            $this->getItinerary()->addActivity(
                $task,
                'cooking',
                '06:30',
                '07:30',
                $dayNum,
                $trailPeriod
            );
            $this->getItinerary()->addActivity(
                $trail,
                'hiking',
                '08:00',
                $trail->calculateEndTime($day->date, '08:00'),
                $day->day_num,
                $trailPeriod
            );
        }

        // Are there possible events for the day to fill in any gaps?
        $this->addEventForMorning($day);
        $this->addEventForAfternoon($day);
        $this->addEventForEvening($day);
        $this->addEventForNight($day);

        // Determine whether to put a trail in the morning or the afternoon.
        $period = $this->getPeriodForTrail($day);
        if ($period === Period::MORNING) {
            $this->addTrailForMorning($day);
        } else if ($period === Period::AFTERNOON) {
            $this->addTrailForAfternoon($day);
        }

        // Add a trail in the area for the evening.
        $this->addTrailForEvening($day);

        // Add meals for the day.
        $this->addMeal($day, Period::MORNING);
        $this->addMeal($day, Period::AFTERNOON);
        $this->addMeal($day, Period::EVENING);

        $this->addPreamble($day);

        $this->calculateASSForDays();
    }

    /**
     * Do a second pass through the itinerary, and make sure everything makes sense.
     */
    public function adjustDays()
    {
        $baseArea = $this->getItinerary()->getBaseArea();
        $days = $this->getItinerary()->getDays();
        $durationDays = $this->getItinerary()->getDurationDays();
        foreach ($days as $index => $day) {
            $date = $day->date;
            $dayNum = $day->day_num;
            if ($dayNum === 0 || $dayNum === $durationDays - 1) {
                continue;
            }

            // If someone is visiting another area and there's no events/locations/trails, change to the base area.
            // TODO: People may be foodies and want to try restaurants in different areas. Accommodate this.
            if ($day->area_id !== $baseArea->id && !$day->hasEvent() && !$day->hasLocation() && !$day->hasTrail()) {
                unset($days[$index]);
                $day = new Day(['date' => $date, 'area' => $baseArea, 'day_num' => $dayNum]);
                $day = $this->fillDay($day, true);
                $days[$index] = $day;
            }
        }
        $this->getItinerary()->setDays($days);
    }

    /**
     * Look at what's happening in the day, and add some extra text, if appropriate.
     */
    public function addPreamble(Day $day)
    {
        $baseArea = $this->getItinerary()->getBaseArea();
        if ($day->area_id === $baseArea->id && !$day->hasEvent() && !$day->hasLocation() && !$day->hasTrail()) {
            $day->setPreamble($this->phraseService->getPhraseForRelaxing());
        }
    }

    /**
     * TODO: Look at weather forecasts to help determine if it'll be too hot to hike in the afternoon.
     */
    public function getPeriodForTrail(Day $day)
    {
        return rand(0, 1) ? Period::MORNING : Period::AFTERNOON;
    }

    public function addTrail(Day $day, string $period)
    {
    }

    public function addTrailForMorning(Day $day)
    {
        if ($this->shouldAddTrail($day, Period::MORNING)) {
            $this->addRandomTrailToPeriod($day, Period::MORNING);
        }
    }

    public function addTrailForAfternoon(Day $day)
    {
        if ($this->shouldAddTrail($day, Period::AFTERNOON)) {
            $this->addRandomTrailToPeriod($day, Period::AFTERNOON);
        }
    }

    public function addTrailForEvening(Day $day)
    {
        if ($this->shouldAddTrail($day, Period::EVENING)) {
            $this->addRandomTrailToPeriod($day, Period::EVENING);
        }
    }

    private function addEventForPeriod(Day $day, string $period, $startTime, $endTime)
    {
        if ($this->getItinerary()->getAdventurousness() < 0.5) {
            return;
        }
        $dayNum = $day->day_num;
        if (empty($day->periods->get($period)->getActivities())) {
            $event = Event::betweenDates($day->date . ' ' . $startTime, $day->date . ' ' . $endTime)
                ->with('location')
                ->whereHas('location', function($query) use ($day) {
                    $query->where('area_id', $day->area_id);
                })->inRandomOrder()
                ->first();
            if ($event) {
                $this->getItinerary()->addActivity(
                    $event,
                    'event',
                    date('H:i:s', strtotime($event->start_date)),
                    date('H:i:s', strtotime($event->end_date)),
                    $dayNum,
                    $period
                );
            }
        }
    }

    public function addEventForMorning(Day $day)
    {
        $this->addEventForPeriod($day, Period::MORNING, '06:00:00', '11:00:00');
    }

    public function addEventForAfternoon(Day $day): void
    {
        if ($this->getItinerary()->getAdventurousness() < 0.5) {
            return;
        }
        $this->addEventForPeriod($day, Period::AFTERNOON, '12:00:00', '16:00:00');
    }

    public function addEventForEvening(Day $day): void
    {
        if ($this->getItinerary()->getAdventurousness() < 0.5) {
            return;
        }
        $this->addEventForPeriod($day, Period::EVENING, '16:15:00', '22:00:00');
        return;
        $dayNum = $day->day_num;
        if (empty($day->periods[Period::EVENING]->getActivities())) {
//            $event = Event::betweenDates($day->date . ' 17:00:00', $day->date . ' 22:00:00')
//                ->with('location')
//                ->whereHas('location', function($query) use ($day) {
//                    $query->where('area_id', $day->area_id);
//                })->sortByDeviation($this->getDeviations())
//                ->limit(2)
//                ->inRandomOrder()
//                ->first();
            $event = Event::betweenDates($day->date . ' 17:00:00', $day->date . ' 22:00:00')
                ->with('location')
                ->whereHas('location', function($query) use ($day) {
                    $query->where('area_id', $day->area_id);
                })->inRandomOrder()
                ->first();
            if ($event) {
                $this->getItinerary()->addActivity(
                    $event,
                    'event',
                    date('H:i:s', strtotime($event->start_date)),
                    date('H:i:s', strtotime($event->end_date)),
                    $dayNum,
                    Period::EVENING
                );
            }
        }
    }

    public function addEventForNight(Day $day): void
    {
        if ($this->getItinerary()->getAdventurousness() < 0.5) {
            return;
        }
        $this->addEventForPeriod($day, Period::NIGHT, '22:00:00', '23:59:59');
        return;
//        $dayNum = $day->day_num;
//        if (empty($day->periods[Period::EVENING]->getActivities())) {
//            $event = Event::betweenDates($day->date . ' 22:00:00', $day->date . ' 23:59:59')
//                ->with('location')
//                ->whereHas('location', function($query) use ($day) {
//                    $query->where('area_id', $day->area_id);
//                })->inRandomOrder()
//                ->first();
//            if ($event) {
//                $this->getItinerary()->addActivity(
//                    $event, 'event', $event->start_date, $event->end_date, $dayNum, Period::EVENING);
//            }
//        }
    }

    // TODO: assessMeal? Split into morning/afternoon/evening functions?
    public function addMeal(Day $day, string $period)
    {
        $date = $day->date;
        $dayNum = $day->day_num;
        $mealTime = Period::getTimesForMeals($period);
        $startTime = $mealTime['startTime'];

        // Add a restaurant in the area for the period.
        if ($this->shouldAddDining($day, $period)) {
            $restaurant = Restaurant::with(['location' => function($query) {
                $query->with('images');
            }])->whereNotIn('restaurants.id', $this->getItinerary()->restaurant_ids)
//                ->whereIsOpen($date . ' ' . $mealTime['startTime'])
                ->whereHas('location', function($query) use ($day) {
                    $query->where('area_id', $day->area->id);
                })->inRandomOrder()->first();
            if ($restaurant && !$this->getItinerary()->restaurantExists($restaurant)) {
                $this->getItinerary()->addActivity(
                    $restaurant,
                    ActivityType::DINING,
                    $startTime,
                    $restaurant->calculateEndTime($day->date, $startTime),
                    $dayNum,
                    $period
                );
            }
        }

        // Tell the user to cook if necessary. If their adventurousness is at least 0.6, we'll randomly choose either
        // cooking or a restaurant.
        if ($this->getItinerary()->getAdventurousness() >= 0.6) {
            $cooking = (bool) rand(0, 1);
        } else {
            $cooking = true;
        }

        if ($this->shouldAddCooking($day, $period)) {
            if ($cooking === true) {
                switch ($period) {
                    case Period::MORNING:
                        $name = 'Breakfast';
                        $phrase = $this->phraseService->getPhraseForBreakfast();
                        break;
                    case Period::AFTERNOON:
                        $name = 'Lunch';
                        $phrase = $this->phraseService->getPhraseForLunch();
                        break;
                    case Period::EVENING:
                        $name = 'Dinner';
//                        $phrase = $this->phraseService->getPhraseForDinner();
                        $phrase = $this->phraseService->getPhraseForLightMeal();
                        break;
                    default:
                        $name = 'Meal time!';
                        $phrase = $this->phraseService->getPhraseForLightMeal();
                }

                $task = new Task([
                    'name' => $name,
                    'description' => $phrase,
                ]);

                $this->getItinerary()->addActivity(
                    $task,
                    'cooking',
                    ($date . ' ' . $startTime),
                    ($date . ' ' . $startTime),
                    $dayNum,
                    $period
                );
            }
        }

        if ($cooking === false) {
            // We still don't have a meal. Just put any restaurant in the area that's open.
            $dayPeriod = $day->getPeriods()[$period];
            if (!$dayPeriod->hasRestaurant() && $dayPeriod->hasActivityType('cooking'))
            {
                $restaurant = Restaurant::with(['location' => function($query) {
                    $query->with('images');
                }])->whereIsOpen($day->date . ' ' . $mealTime['startTime'])
                    ->whereHas('location', function($query) use ($day) {
                        $query->where('area_id', $day->area_id);
                    })->inRandomOrder()->first();
                if ($restaurant && !$this->getItinerary()->restaurantExists($restaurant)) {
                    $this->getItinerary()->addActivity(
                        $restaurant,
                        'dining',
                        $startTime,
                        $restaurant->calculateEndTime($day->date, $startTime),
                        $dayNum,
                        $period
                    );
                }
            }
        }
    }

    /**
     * Sort by how many subarrays each item has, in descending order.
     */
    private function sortBySubarrayCount(&$array, $ascending = false): ?array
    {
        usort($array, function($a, $b) use ($ascending) {
            $countA = is_array($a) ? count($a) : 0;
            $countB = is_array($b) ? count($b) : 0;
            if ($ascending) {
                return $countA - $countB;
            } else {
                return $countB - $countA;
            }
        });
    }

    /**
     * We'll need to examine the priorities and make sure we include those areas.
     */
    public function calculateAreaPriorities(): array
    {
        $eventPriorities = $this->getItinerary()->getEventPriorities();
        $locationPriorities = $this->getItinerary()->getLocationPriorities();
        $restaurantPriorities = $this->getItinerary()->getRestaurantPriorities();
        $trailPriorities = $this->getItinerary()->getTrailPriorities();
        $areaPriorities = [];

        foreach ($eventPriorities as $resourceId) {
            $resource = Event::find($resourceId);
            if ($resource) {
                $areaPriorities[$resource->area_id][] = $resource;
            }
        }

        foreach ($locationPriorities as $resourceId) {
            $resource = Location::find($resourceId);
            if ($resource) {
                $areaPriorities[$resource->area_id][] = $resource;
            }
        }

        foreach ($restaurantPriorities as $resourceId) {
            $resource = Restaurant::find($resourceId);
            if ($resource) {
                $areaPriorities[$resource->area_id][] = $resource;
            }
        }

        foreach ($trailPriorities as $resourceId) {
            $resource = Trail::find($resourceId);
            if ($resource) {
                $areaPriorities[$resource->area_id][] = $resource;
            }
        }

        return $this->sortBySubarrayCount($areaPriorities);
    }

    /**
     * We'll need to examine the priorities and make sure we include those areas.
     */
    public function assignDaysForAreas(bool $useFirstDay = false, bool $useLastDay = false): array
    {
        $adventurousness = $this->getItinerary()->getAdventurousness();
        $baseArea = $this->getItinerary()->getBaseArea();
        $durationDays = $this->getItinerary()->getDurationDays();
        $dayNum = 0;
        $areaIds = [];
        $allAreaIds = Area::all()->pluck('id')->toArray();
        $allAreaIds = array_combine($allAreaIds, $allAreaIds);

        // Default to the user's accommodation area for the first day, if $useFirstDay is false.
        $date = $this->getItinerary()->getStartDate();
        if ($useFirstDay === false) {
            $day = new Day(['date' => $date, 'area' => $baseArea, 'day_num' => $dayNum]);
            $day->start_time = Day::DEFAULT_ARRIVAL_TIME;
            $days[$dayNum++] = $day;
            $date = $this->incrementDate($date);
            $areaIds[] = $baseArea->id;
        } else {
            $days = [];
        }

        // Do the same for the last day.
        if ($useLastDay === false) {
            $day = new Day(['date' => $date, 'area' => $baseArea, 'day_num' => $durationDays - 1]);
            $day->end_time = Day::DEFAULT_DEPARTURE_TIME;
            $days[$durationDays - 1] = $day;
            $areaIds[] = $baseArea->id;
        }

        $activityPriorities = $this->getItinerary()->getActivityPriorities();
        $trailPriorities = $this->getItinerary()->getTrailPriorities();

        $this->getItinerary()->setDays($days);

        // Get the areas for the trails. Weight the areas that appear the most so they are first in the array.
        // TODO: Make sure the trails are actually open on the date.
        $trailAreaIds = Trail::select('area_id', DB::raw('COUNT(area_id) AS occurrences'))
            ->whereNotNull('distance_miles')
            ->whereIn('id', $trailPriorities)
            ->groupBy('area_id')
            ->orderByRaw('COUNT(area_id) DESC')
            ->get()
            ->keyBy('area_id');

        // Add areas as long as we have days remaining in the duration of the trip.
        $modifier = $useLastDay ? 0 : -1;
        foreach ($trailAreaIds as $areaId => $tai) {
            if (count($days) < $durationDays + $modifier) {
                $day = new Day(['date' => $date, 'area_id' => $areaId, 'day_num' => $dayNum]);
                $days[$dayNum++] = $day;
                $date = $this->incrementDate($date);
            }
        }

        // Fill in any remaining days.
        do {
            // TODO: Don't be so random about the area. Get an area with other events they'd be interested in. Also take
            // into account other preferences like maximum distance they want to travel.
            /** @var CustomQueryBuilder $query */
            $query = Area::inWeightedRandomOrder($adventurousness);

            // If the user's adventurousness is low (< 0.3, < 0.4), we won't pick areas that are too far from their base area.
            switch ($adventurousness) {
                case $adventurousness < 0.3:
                    $maxDistance = 60;
                    break;
                case $adventurousness < 0.4:
                    $maxDistance = 75;
                    break;
                default:
                    $maxDistance = null;
            }

            if ($maxDistance) {
                $query->whereProximity($baseArea->latitude, $baseArea->longitude, $maxDistance);
            }

            // If the user's adventurousness < 0.5, let's focus on Kanab, and not St. George.
            if ($adventurousness < 0.5) {
                if ($baseArea->id === Area::ZION) {
                    $query->whereNot('id', Area::ST_GEORGE);
                }
            }

            // TODO: The North Rim is closed from December 1 to May 14. Don't assign this area between these dates.

            // If the user's adventurousness is < 0.8, only pick any area besides Kanab or Zion once, unless it's their base area.
            // TODO: Fix this.
//            if ($adventurousness < 0.8) {
//                $numPossible = 1;
//                $areaCounts = array_count_values($allAreaIds);
//                $filteredAreaIds = array_filter($allAreaIds, function($areaId) use ($areaCounts, $numPossible, $baseArea) {
//                    return (
//                        $areaId !== Area::KANAB &&
//                        $areaId !== $baseArea->id &&
//                        $areaId !== Area::ZION &&
//                        $areaCounts[$areaId] <= $numPossible
//                    );
//                });
//                $query->whereNotIn('id', $filteredAreaIds);
//            }

            // If the user's adventurousness is < 0.9, only pick the remaining non-base area(s) once per 10 days of the trip.
            // TODO: Fix this.
//            if ($adventurousness < 0.9) {
//                $numPossible = ceil($this->getItinerary()->durationDays / 10);
//                $areaCounts = array_count_values($areaIds);
//                $filteredAreaIds = array_filter($allAreaIds, function($areaId) use ($areaCounts, $numPossible, $baseArea) {
//                    return (
//                        $areaId !== $baseArea->id &&
//                        isset($areaCounts[$areaId]) &&
//                        $areaCounts[$areaId] >= $numPossible
//                    );
//                });
//                $query->whereNotIn('id', $filteredAreaIds);
//            }

            $area = $query->first();
            if (!$area) {
                $area = $baseArea;
            }

            $day = new Day(['date' => $date, 'area' => $area, 'day_num' => $dayNum]);
            $days[$dayNum++] = $day;
            $date = $this->incrementDate($date);
            $areaIds[] = $area->id;
        } while (count($days) < $durationDays);
        ksort($days);

        $this->getItinerary()->setDays($days);

        return $days;
    }

    public function isTooCold($day, $period, $activityType) {
        return $day->area->isTooCold($day, $period, $activityType);
    }

    public function isTooHot($day, $period, $activityType) {
        return false;
        return $day->area->isTooHot($day, $period, $activityType);
    }

    /**
     * Should we add a trail for the period to fill in the gaps? If they've already done a trail today, only add another
     * trail if their adventurousness is >= 0.7.
     */
    public function shouldAddTrail(Day $day, string $period): bool {
        switch ($period) {
            case Period::NIGHT:
                break;
            case Period::MORNING:
            case Period::AFTERNOON:
            case Period::EVENING:
            default:
                if (!$this->isTooHot($day, $period, 'hiking')) {
                    $dayHasTrail = $day->hasTrail();
                    if ($this->getItinerary()->getAdventurousness() >= 0.7 || !$dayHasTrail) {
                        if (count($day->periods[$period]->getActivities()) === 0) {
                            return true;
                        }
                    }
                }
                break;
        }
        return false;
    }

    /**
     * If it's breakfast, and the day is not in the base location, just say false.
     */
    public function shouldAddDining(Day $day, string $period): bool
    {
        if ($day->area_id !== $this->getItinerary()->getBaseAreaId()) {
            if ($period === Period::MORNING) {
                return false;
            }
        }
        if ($day->periods[$period]->hasRestaurant() || $day->hasActivityTypeInPeriod('cooking', $period)) {
            return false;
        }
        return true;
    }

    /**
     * Should we add a home-meal option for the period?
     * If they're away from their base area:
     * a. Morning is okay, especially if they're hiking a Zion trail that morning.
     * b. Lunch is out of the question.
     * c. Dinner is okay if their afternoon activity will end and they can travel back by 5pm.
     * back. If they've already done a trail today, only add another
     * trail if their adventurousness is >= 0.7.
     */
    public function shouldAddCooking(Day $day, string $period): bool {
        // Make sure there isn't already a restaurant or cooking activity during the period.
        $dayPeriod = $day->getPeriod($period);
        if ($dayPeriod->hasRestaurant() || $dayPeriod->hasActivityType('cooking')) {
            return false;
        }

        if ($day->area_id !== $this->getItinerary()->getBaseAreaId()) {
            switch ($period) {
                case Period::MORNING:
                    return true;
                case Period::EVENING:
                    $activities = $day->getPeriods()[Period::AFTERNOON]->getActivities();
                    $areaDistance = AreaDistance::where([
                        'from_area_id' => $day->area_id,
                        'to_area_id' => $this->getItinerary()->getBaseAreaId(),
                    ])->first();
                    if ($areaDistance) {
                        $areaDistanceTime = $areaDistance->driving_hours * 60 * 60;
                    } else {
                        $areaDistanceTime = self::DEFAULT_DRIVING_TIME_BETWEEN_AREAS;
                    }
                    foreach ($activities as $activity) {
                        if (strtotime($activity->endTime) + $areaDistanceTime >=
                            strtotime(Period::getTimesForMeals(Period::EVENING)['startTime'])) {
                            return false;
                        }
                    }
                    return true;
                case Period::AFTERNOON:
                case Period::NIGHT:
                default:
                    return false;
            }
        }
        return true;
    }

    public function addRandomTrailToPeriod(Day $day, string $period)
    {
        $trail = $this->trailService->getRandomTrailForPeriod(
            $day->area->id,
            $period,
            $this->getItinerary(),
            $this->getItinerary()->trail_ids
        );

        if ($trail && !$this->getItinerary()->trailExists($trail)) {
            $startTime = Period::getStartTimeForActivities($period);
            $this->getItinerary()->addActivity(
                $trail,
                'hiking',
                ($day->date . ' ' . $startTime),
                $trail->calculateEndTime($day->date, $startTime),
                $day->day_num,
                $period
            );
        }
    }

    public function estimateTotalTravelDistance(): float
    {
        $totalMiles = 0;
        $baseArea = $this->getItinerary()->getBaseArea();
        $baseAreaLat = $baseArea->latitude;
        $baseAreaLon = $baseArea->longitude;
        $days = $this->getItinerary()->getDays();
        for ($i = 1; $i < count($days); $i++) {
            if ($i < count($days)) {
//                try {
                    $areaLat = $days[$i]->area->latitude;
                    $areaLon = $days[$i]->area->longitude;
//                } catch (\Throwable $e) {
//                    echo '<pre>'; print_r($days); echo '</pre>';
//                    echo '<pre>'; print_r($e); echo '</pre>';
//                }

//                $totalMiles += 2 * (AreaDistance::where('from_area_id', $baseArea->id)->where('to_area_id', $days[$i]->getAreaId())->pluck('driving_miles') ?? 0);
                $totalMiles += 2 * $this->locationService->estimateDrivingDistance($baseAreaLat, $baseAreaLon, $areaLat, $areaLon);

                foreach (Period::getPeriodTypes() as $periodType) {
                    $activities = $days[$i]->periods[$periodType]->getActivities();
                    for ($j = 0; $j < count($activities); $j++) {
                        $distance = 0;
                        $data1 = $activities[$j]->data;
                        if ($data1) {
                            $lat1 = $data1->location->latitude ?? $data1->latitude;
                            $lon1 = $data1->location->longitude ?? $data1->longitude;

                            if (in_array(0, [$lat1, $lat1])) {
                                continue;
                            }

                            if ($j === 0) {
                                $distance = $this->locationService->estimateDrivingDistance(
                                    $areaLat,
                                    $areaLon,
                                    $lat1,
                                    $lon1,
                                );
                            }
                        }

                        if ($j < count($activities) - 1) {
                            $data2 = $activities[$j + 1]->data;
                            $lat2 = isset($data2->location) ? $data2->location->latitude : $data2->latitude;
                            $lon2 = isset($data2->location) ? $data2->location->longitude : $data2->longitude;

                            if (in_array(0, [$lat2, $lat2])) {
                                continue;
                            }

                            $distance = $this->locationService->estimateDrivingDistance(
                                $lat1,
                                $lon1,
                                $lat2,
                                $lon2,
                            );
                        }
                        if ($j === count($activities)) {
                            $distance = $this->locationService->estimateDrivingDistance(
                                $lat1,
                                $lon1,
                                $areaLat,
                                $areaLon,
                            );
                        }
                        $totalMiles += $distance;
                    }
                }
            }
        }
        return round($totalMiles);
    }

    private function incrementDate($date) {
        return date('Y-m-d', strtotime($date . ' +1 day'));
    }

    public function setupItinerary(Request $request)
    {
        $accommodationPreference = $request->get('accommodation_preference');
        $arrivalDateTime = $request->get('arrival_datetime');
        $departureDateTime = $request->get('departure_datetime');
        $interests = $request->get('interests');
        $tripPreference = $request->get('trip_preference');

        if ($request->get('base_area_id')) {
            $area = Area::where('id', $request->get('base_area_id'))->first();
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
                $area = Area::find($areaId);
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

        $this->getItinerary()->save();
    }

    /**
     * Most users probably won't have specific dates in mind. Just a season or month. We can decide on dates here.
     * TODO: Incorporate the reservation system, so we're favoring dates that have accommodation openings.
     */
    public function determineAndSetMissingDates()
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
//        throw new \Exception('Could not set dates: ' . print_r($this->getItinerary(), true));
    }

    public function getRandomDateInMonth($month, int $year = null) {
        // Get the current date
        $currentDate = new \DateTime();

        // Calculate the target year
        if ($year === null) {
            $targetYear = (int)$currentDate->format('Y');
        } else {
            $targetYear = $year;
        }

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
        do {
            $randomDate->add(new \DateInterval('P1D'));
        } while ($randomDate < $minimumDate);

        // Did we move outside the intended month? Let's try again, but this time jump forward a year.
        if ((int)$randomDate->format('n') !== $month) {
            $randomDate = new \DateTime($this->getRandomDateInMonth($month, $targetYear + 1));
        }
        return $randomDate->format('Y-m-d');
    }

    /**
     * Keep the first and last day, and shuffle the rest.
     */
    public function shuffleDays(): void
    {
        $days = $this->getItinerary()->getDays();

        // Return early if there are 3 or fewer days.
        if (count($days) <= 3) {
            return;
        }

        // Preserve the first and last elements.
        $first = array_shift($days);
        $last = array_pop($days);

        // Group days by area_id.
        $groupedDays = [];
        foreach ($days as $day) {
            $groupedDays[$day->area_id][] = $day;
        }

        // Flatten the grouped days and shuffle the groups.
        $shuffledGroups = [];
        foreach ($groupedDays as $group) {
            shuffle($group);
            $shuffledGroups = array_merge($shuffledGroups, $group);
        }

        // Reinsert the first and last days and update the dayNum.
        $days = array_merge([$first], $shuffledGroups, [$last]);

        $startDate = $this->getItinerary()->getStartDate();
        foreach ($days as $dayNum => $day) {
            $day->dayNum = $dayNum;
            $day->day_num = $dayNum;
            $day->date = date('Y-m-d', strtotime($startDate . ' +' . $dayNum . ' days'));
        }

        // Set the shuffled and grouped days back to the itinerary.
        $this->getItinerary()->setDays($days);
    }

    public function getEvents()
    {
        $query = Event::betweenDates($this->itinerary->getStartDate(), $this->getItinerary()->getEndDate());
//            ->with('data');
        $deviations = $this->getDeviations();
        if (!empty($deviations)) {
            $query = $query->sortByDeviation($deviations);
        }
        return $query->get();
    }

    private function calculateASSForDays()
    {
        foreach ($this->getItinerary()->getDays() as &$day) {
            $day->calculateASS();
        }
    }
}
