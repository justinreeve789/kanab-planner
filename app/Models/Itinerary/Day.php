<?php

namespace App\Models\Itinerary;

use App\Models\Area;
use App\Models\AreaWeather;
use App\Models\Trail;
use App\ResourceEnum;
use App\Traits\ReturnAllProperties;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Take into account that days are longer in summer and shorter in winter.
 */
class Day extends CachedModel
{
    const DEFAULT_ARRIVAL_TIME = '16:00:00';
    const DEFAULT_DEPARTURE_TIME = '10:00:00';
    const WEATHER_ICON_DEFAULT = 'cloudy.svg';
    const WEATHER_ICON_PATH = 'images/icons/weather/';

    protected $appends = [
        'area',
        'weather',
        'formatted_abbr_month_day',
        'formatted_date',
        'formatted_day_of_week',
        'formatted_month_day',
        'formatted_year',
    ];
    protected $attributes = [
        'formatted_abbr_month_day' => '',
        'formatted_date' => '',
        'formatted_day_of_week' => '',
        'formatted_month_day' => '',
        'formatted_year' => '',
    ];
    protected $casts = ['id' => 'string', 'itinerary_id' => 'string', 'stats' => 'json'];
    protected $guarded = [
        'formatted_abbr_month_day',
        'formatted_date',
        'formatted_day_of_week',
        'formatted_month_day',
        'formatted_year',
        'weather',
    ];
    protected $table = 'itinerary_days';

    public $activities;
    public $preamble;

    /** @var Period[] $periods */

    public function __construct(array $attributes = [])
    {
        $this->id = Str::uuid();

        foreach ($attributes as $key => $attr) {
            $this->setAttribute($key, $attr);
        }

        // Activities could potentially occur outside the day's area, such as eating dinner close to the home area after
        // spending time in other areas, if the location is still in a reasonable proximity, but generally all activities
        // should occur in the same area.

        $this->start_time = '07:00';
        $this->end_time = '22:00';

        $this->activities = [];
        $this->createPeriodsIfNotExist();

        parent::__construct($attributes);
    }

    public function getFormattedAbbrMonthDayAttribute()
    {
        return Carbon::parse($this->date)->format('M j');
    }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->format('l, F j, Y');
    }

    public function getFormattedDayOfWeekAttribute()
    {
        return Carbon::parse($this->date)->format('l');
    }

    public function getFormattedMonthDayAttribute()
    {
        return Carbon::parse($this->date)->format('F j');
    }

    public function getFormattedYearAttribute()
    {
        return Carbon::parse($this->date)->format('Y');
    }

    protected function createPeriodsIfNotExist()
    {
        $periods = [
            Period::MORNING,
            Period::AFTERNOON,
            Period::EVENING,
            Period::NIGHT
        ];

        foreach ($periods as $period) {
            $this->periods->put($period, new Period(['day_id' => $this->id, 'type' => $period]));
        }

//        foreach ($periods as $periodType) {
//            // Check if the period already exists for this day, if not, create it.
//            $existingPeriod = $this->periods()->where('type', $periodType)->first();
//            if (!$existingPeriod) {
//                $this->periods()->create([
//                    'day_id' => $this->id,
//                    'type' => $periodType,
//                ]);
//            }
//        }
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class, 'itinerary_id', 'id');
    }

    public function periods()
    {
        return $this->hasMany(Period::class, 'day_id', 'id');
    }

    protected function area(): Attribute
    {
        return Attribute::make(
            get: function() {
                return Area::find($this->area_id);
            },
            set: function(Area $area) {
                $this->area_id = $area->id;
            }
        );
    }

    public function saveAll()
    {
        $this->periods->each()->save();
    }

    public function getPeriod($index): Period
    {
        return $this->periods[$index];
    }

    public function getPeriods(): Collection
    {
        return $this->periods;
    }

    /**
     * Since a trail is likely to be the focal point of the day, weight it heavier than other activities when
     * calculating the average.
     */
    public function calculateASS(): array
    {
        if ($this->stats) {
            return $this->stats;
        }
        $stats = ['adventurousness' => 0, 'solitariness' => 0, 'strenuousness' => 0];
        $activities = $this->getActivities();
        $count = count($activities);
        foreach ($activities as $activity) {
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

    /**
     * Return a flat array of activities from each period.
     *
     * @return Collection
     */
    public function getActivities(): Collection
    {
        $activities = new Collection();
        foreach ($this->getPeriods() as $period) {
            $activities = $activities->merge($period->activities);
        }
        return $activities;
    }

    public function getPreamble(): string
    {
        return $this->preamble;
    }

    public function getWeatherAttribute()
    {
        $weather = AreaWeather::where('area_id', $this->area->id)
            ->whereDate('start', '=', Carbon::parse($this->date)->toDateString())
            ->get();
        foreach ($weather as $w) {
            if (Carbon::parse($w->start)->toTimeString() >= '12:00:00') {
                $shortForecast = $w->short_forecast;
                $detailedForecast = $w->detailed_forecast;
            }
            if (isset($maxTemperature) && $maxTemperature < $w->temperature) {
                $minTemperature = $maxTemperature;
            }
            $maxTemperature = $w->temperature;
        }

        return (object) [
            'forecast' => (object) [
                'short' => $shortForecast ?? null,
                'detailed' => $detailedForecast ?? null,
            ],
            'temperatures' => (object) [
                'high' => $maxTemperature ?? null,
                'low' => $minTemperature ?? null,
            ],
            'icon' => isset($shortForecast) ? $this->getWeatherIconFromShortForecast($shortForecast) : null,
        ];
    }

    private function getWeatherIconFromShortForecast($str): string {
        $filename = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $str), '-'));
        $path = base_path() . '/public/' . self::WEATHER_ICON_PATH . $filename;
        if (file_exists($path . '.svg')) {
            return asset(self::WEATHER_ICON_PATH . $filename . '.svg');
        }
        if (file_exists($path . '.png')) {
            return asset(self::WEATHER_ICON_PATH . $filename . '.png');
        }
        return asset(self::WEATHER_ICON_PATH . self::WEATHER_ICON_DEFAULT);
    }

    public function hasActivityType($activityType): bool
    {
        foreach ($this->periods as $period) {
            foreach ($period->activities as $activity) {
                if ($activity->activityType === $activityType) {
                    return true;
                }
            }        }
        return false;
    }

    public function hasActivityTypeInPeriod(string $activityType, int $period): bool
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
        return $this->hasResourceClass(ResourceEnum::EVENTS_CLASS);
    }

    public function hasLocation(): bool
    {
        return $this->hasResourceClass(ResourceEnum::LOCATIONS_CLASS);
    }

    public function hasRestaurant(): bool
    {
        return $this->hasResourceClass(ResourceEnum::RESTAURANTS_CLASS);
    }

    public function hasTrail(): bool
    {
        return $this->hasResourceClass(ResourceEnum::TRAILS_CLASS);
    }

    public function shouldAddActivityTypeForPeriod(string $activityType, int $period): bool
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

    public function shouldAddCookingForPeriod(int $period): bool
    {
        return $this->shouldAddActivityTypeForPeriod('cooking', $period);
    }

    public function shouldAddTrail(): bool
    {
    }

    public function setPreamble(string $preamble): void
    {
        $this->preamble = $preamble;
    }

    /**
     * After activities are added to periods, we can calculate specific times. Activities may be moved into different periods.
     */
    public function chronotizeActivities()
    {
        foreach ($this->periods as $period) {
            foreach ($period->activities as $activity) {
                if ($period->type === Period::MORNING) {
                    // Breakfast should always come first.
                }
            }
        }
    }
}
