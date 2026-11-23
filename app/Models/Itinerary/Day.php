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
        'preamble',
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
        'preamble',
        'weather',
    ];
    protected $table = 'itinerary_days';

    public $activities;
    private ?string $preamble = null;

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
            // Return the attributes to write. Assigning $this->area_id inside the closure is silently discarded:
            // Eloquent merges the closure's return value over a copy of the attributes taken before it ran, so
            // area_id stayed null for every day built with ['area' => $area].
            set: fn(?Area $area) => ['area_id' => $area?->id]
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
     *
     * Each activity contributes the resource_data row for its resource (matching the activity type when there is
     * one). Tasks without a resource (cooking, etc.) don't count. Pass $force to recompute after the day changes.
     */
    public function calculateASS(bool $force = false): array
    {
        if ($this->stats && !$force) {
            return $this->stats;
        }
        $props = ['adventurousness', 'solitariness', 'strenuousness'];
        $weights = ['adventurousness' => 1.25, 'solitariness' => 1, 'strenuousness' => 1.5];
        $sums = array_fill_keys($props, 0.0);
        $totals = array_fill_keys($props, 0.0);

        foreach ($this->getActivities() as $activity) {
            $resource = $activity->resource;
            if (!$resource || !isset($resource->data) || $resource->data->isEmpty()) {
                continue;
            }
            $data = $resource->data->firstWhere('activity_type_id', $activity->activity_type_id)
                ?? $resource->data->first();
            $isHiking = $activity->activity_type?->name === 'hiking';

            foreach ($props as $prop) {
                if ($data->{$prop} === null) {
                    continue;
                }
                $weight = $isHiking ? $weights[$prop] : 1;
                $sums[$prop] += $weight * (float) $data->{$prop};
                $totals[$prop] += $weight;
            }
        }

        $stats = [];
        foreach ($props as $prop) {
            $stats[$prop] = $totals[$prop] > 0 ? round(min(1, max(0, $sums[$prop] / $totals[$prop])), 2) : 0;
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
            $activities = $activities->merge($period->getActivities());
        }
        return $activities;
    }

    public function getPreamble(): ?string
    {
        return $this->preamble;
    }

    public function getPreambleAttribute(): ?string
    {
        return $this->preamble;
    }

    public function getWeatherAttribute()
    {
        $weather = AreaWeather::where('area_id', $this->area->id)
            ->whereDate('start', '=', Carbon::parse($this->date)->toDateString())
            ->get();
        foreach ($weather as $w) {
            if (!isset($shortForecast) && Carbon::parse($w->start)->toTimeString() >= '12:00:00') {
                $shortForecast = $w->short_forecast;
                $detailedForecast = $w->detailed_forecast;
            }
            $maxTemperature = isset($maxTemperature) ? max($maxTemperature, $w->temperature) : $w->temperature;
            $minTemperature = isset($minTemperature) ? min($minTemperature, $w->temperature) : $w->temperature;
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
                if ($activity->activity_type?->name === $activityType) {
                    return true;
                }
            }
        }
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
                $resource = $activity->resource;
                if ($resource && get_class($resource) === $resourceClass) {
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
                if ($this->getPeriod($period)->hasRestaurant()) {
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
