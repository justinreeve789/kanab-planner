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
    protected $attributes = ['preamble' => null, 'drive_minutes' => null, 'return_minutes' => null, 'return_miles' => null, 'return_notes' => null];
    protected $casts = [
        'id' => 'string', 'itinerary_id' => 'string', 'stats' => 'json',
        'drive_minutes' => 'integer', 'return_minutes' => 'integer', 'return_miles' => 'float', 'return_notes' => 'json',
    ];
    protected $guarded = [
        'formatted_abbr_month_day',
        'formatted_date',
        'formatted_day_of_week',
        'formatted_month_day',
        'formatted_year',
        'weather',
    ];
    protected $table = 'itinerary_days';

    /**
     * A new day (constructed with attributes) gets its four empty periods in memory. Rows hydrated from the database
     * are constructed without attributes and get their periods from the relation, so this never queries.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!empty($attributes)) {
            $this->id = $this->id ?? Str::uuid();
            $this->start_time = $this->start_time ?? '07:00:00';
            $this->end_time = $this->end_time ?? '22:00:00';
            $this->createPeriods();
        }
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

    protected function createPeriods(): void
    {
        $periods = new Collection();
        foreach (Period::getPeriodTypes() as $type) {
            $periods->put($type, new Period(['day_id' => $this->id, 'type' => $type]));
        }
        $this->setRelation('periods', $periods);
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

    public function getPeriod(int $type): Period
    {
        return $this->periods->first(fn(Period $period) => $period->type === $type);
    }

    /**
     * Periods in morning → night order.
     */
    public function getPeriods(): Collection
    {
        return $this->periods->sortBy('type')->values();
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
    /** Take an activity out of the day (in memory; for days that haven't been saved yet). */
    public function removeActivity(Activity $activity): void
    {
        foreach ($this->getPeriods() as $period) {
            $period->setRelation('activities', $period->activities->reject(fn(Activity $a) => $a === $activity)->values());
        }
    }

    /** Re-time an activity, moving it to the period its new start falls in (in memory). */
    public function moveActivity(Activity $activity, string $start, string $end): void
    {
        $this->removeActivity($activity);
        $activity->start_time = Timeline::toTime(Timeline::toMinutes($start));
        $activity->end_time = Timeline::toTime(Timeline::toMinutes($end));
        $this->getPeriod(Period::forTime($activity->start_time))->addActivity($activity);
    }

    public function getActivities(): Collection
    {
        $activities = new Collection();
        foreach ($this->getPeriods() as $period) {
            $activities = $activities->merge($period->getActivities());
        }
        return $activities->sortBy('start_time')->values();
    }

    public function getPreamble(): ?string
    {
        return $this->attributes['preamble'] ?? null;
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
        return $this->getPeriod($period)->hasActivityType($activityType);
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

    public function setPreamble(?string $preamble): void
    {
        $this->attributes['preamble'] = $preamble;
    }
}
