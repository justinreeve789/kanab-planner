<?php

namespace App\Models\Itinerary;

use App\Models\Area;
use App\Models\AreaWeather;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Take into account that days are longer in summer and shorter in winter.
 */
class Day extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    const DEFAULT_ARRIVAL_TIME = '16:00:00';
    const DEFAULT_DEPARTURE_TIME = '10:00:00';

    protected $casts = ['stats'];
    protected $guarded = [];

    //    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    public $activities;
    public $areaId;
    public $area;
    public $date;
    public $dayNum;
//    public $solitariness = self::DEFAULT_SOLITARINESS;
//    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    public $endTime = '22:00';
    public $formattedAbbrMonthDay;
    public $formattedDate;
    public $formattedDayOfWeek;
    public $formattedMonthDay;
    public $formattedYear;
    public $periods;
    public $preamble;
    public $startTime = '07:00';
    public $stats;
    public $temperatures;

    public function __construct($date, $area, ?int $dayNum)
    {
        $this->date = $date;
        $this->formattedAbbrMonthDay = date('M j', strtotime($this->date));
        $this->formattedDate = date('l, F j, Y', strtotime($this->date));
        $this->formattedDayOfWeek = date('l', strtotime($this->date));
        $this->formattedMonthDay = date('F j', strtotime($this->date));
        $this->formattedYear = date('Y', strtotime($this->date));

        if ($area !== null) {
            if (is_integer($area)) {
                $area = Area::where('id', $area)->first();
            }
            $this->areaId = $area->id;
            $this->area = $area;
        }

        if ($dayNum !== null) {
            $this->dayNum = $dayNum;
        }

        $this->activities = [];
        $this->periods = [
            Period::MORNING => new Period(Period::MORNING, $this->areaId),
            Period::AFTERNOON => new Period(Period::AFTERNOON, $this->areaId),
            Period::EVENING => new Period(Period::EVENING, $this->areaId),
            Period::NIGHT => new Period(Period::NIGHT, $this->areaId),
        ];
        $this->temperatures = $this->getTemperatures();
    }

    public function periods()
    {
        return $this->hasMany(Period::class, 'day_id', 'id');
    }

    public function addActivity($resource, $activityType = null, $startTime = null, $endTime = null)
    {
        $this->activities[] = new Activity($resource, $activityType, $startTime, $endTime);
    }

    public function getPeriods(): array
    {
        return $this->periods;
    }

    /**
     * A trail is probably likely to be the focal point of the day. Weight it heavier than other activities when
     * calculating the average.
     */
    public function getASS(): array
    {
        if ($this->stats) {
            return $this->stats;
        }
        $stats = ['adventurousness' => 0, 'solitariness' => 0, 'strenuousness' => 0];
        $count = count($this->getActivities());
        foreach ($this->getActivities() as $activity) {
            if (empty($activity->data->data)) {
                $count--;
                continue;
            }
            $advModifier = 1;
            $solModifier = 1;
            $strModifier = 1;
            if ($activity->activityType == 'hiking') {
                $advModifier = 1.25;
                $strModifier = 1.5;
            }
            if (isset($activity->data->data[0])) {
//                $stats['adventurousness'] += $advModifier * $activity->data->data[0]->adventurousness ??
//                    $activity->adventurousness() ?? Activity::DEFAULT_ADVENTUROUSNESS;
//                $stats['solitariness'] += $solModifier * isset($activity->data->data[0]) ??
//                    $activity->data->data[0]->solitariness ??
//                    $activity->solitariness() ?? Activity::DEFAULT_SOLITARINESS;
//                $stats['strenuousness'] += $strModifier * isset($activity->data->data[0]) ??
//                    $activity->data->data[0]->strenuousness ??
//                    $activity->strenuousness() ?? Activity::DEFAULT_STRENUOUSNESS;
                $stats['adventurousness'] += $advModifier * $activity->data->data[0]->adventurousness ?? Activity::DEFAULT_ADVENTUROUSNESS;
                $stats['solitariness'] += $solModifier * $activity->data->data[0]->solitariness ?? Activity::DEFAULT_SOLITARINESS;
                $stats['strenuousness'] += $strModifier * $activity->data->data[0]->strenuousness ?? Activity::DEFAULT_STRENUOUSNESS;
            }
        }
        if ($count > 0) {
            $stats['adventurousness'] = round($stats['adventurousness'] / $count, 2);
            $stats['solitariness'] = round($stats['solitariness'] / $count, 2);
            $stats['strenuousness'] = round($stats['strenuousness'] / $count, 2);
        }
        $this->stats = $stats;
        return $stats;
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function getPreamble(): string
    {
        return $this->preamble;
    }

    public function getTemperatures()
    {
        $weather = AreaWeather::where('area_id', $this->areaId)
            ->where('start', '>=', $this->date . ' 00:00:00')
            ->where('end', '<=', $this->date . ' 23:59:59')
            ->select(
                \DB::raw('MAX(temperature) as max_temperature'),
                \DB::raw('MIN(temperature) as min_temperature')
            )->first();

        if ($weather) {
            $maxTemperature = $weather->max_temperature;
            $minTemperature = $weather->min_temperature;
        } else {
            // Handle the case where no weather data is found for the given date and area
            $maxTemperature = null;
            $minTemperature = null;
        }

        return [
            'high' => $maxTemperature,
            'low' => $minTemperature,
        ];
    }

    public function hasActivityType($activityType): bool
    {
        foreach ($this->periods as $period) {
            foreach ($period->activities as $activity) {
                if ($activity->activityType === $activityType) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasActivityTypeInPeriod(string $activityType, string $period): bool
    {
        return $this->getPeriods()[$period]->hasActivityType($activityType);
    }

    public function hasResourceClass($resourceClass): bool
    {
        foreach ($this->periods as $period) {
            foreach ($period->activities as $activity) {
                if ($activity->data && get_class($activity->data) === $resourceClass) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasEvent(): bool
    {
        return $this->hasResourceClass('App\\Models\\Event');
    }

    public function hasLocation(): bool
    {
        return $this->hasResourceClass('App\\Models\\Location');
    }

    public function hasRestaurant(): bool
    {
        return $this->hasResourceClass('App\\Models\\Restaurant');
    }

    public function hasTrail(): bool
    {
        return $this->hasResourceClass('App\\Models\\Trail');
    }

    public function shouldAddActivityTypeForPeriod(string $activityType, string $period): bool
    {
        if ($this->hasActivityTypeInPeriod($activityType, $period)) {
            return false;
        }

        switch ($activityType) {
            case 'cooking':
                if ($this->periods()[$period]->hasRestaurant()) {
                    return false;
                }
                break;
            default:
        }

        return true;
    }

    public function shouldAddCookingForPeriod(string $period): bool
    {
        return $this->shouldAddActivityTypeForPeriod('cooking', $period);
    }

    public function shouldAddTrail(): bool
    {
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public function setAreaId(int $areaId)
    {
        $this->areaId = $areaId;
    }

    public function setPreamble(string $preamble): void
    {
        $this->preamble = $preamble;
    }
}
