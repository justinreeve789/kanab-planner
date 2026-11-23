<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
use App\Models\Itinerary\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    const BRYCE = 1;
    const NORTH_RIM = 2;
    const KANAB = 3;
    const LAKE_POWELL = 4;
    const PAGE_AZ = 5;
    const ST_GEORGE = 6;
    const ZION = 7;

    protected $appends = ['slug'];
    protected $guarded = [];
    protected $table = 'areas';

    /**
     * @return \App\Models\Builder\CustomQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomQueryBuilder($query);
    }

    /**
     * Relative popularity of each area for day-trip selection (see pickWeighted()).
     */
    public static function getAreaWeights(): array
    {
        return [
            Area::BRYCE => 100,
            Area::NORTH_RIM => 50,
            Area::KANAB => 75,
            Area::LAKE_POWELL => 50,
            Area::PAGE_AZ => 50,
            Area::ST_GEORGE => 10,
            Area::ZION => 150,
        ];
    }

    public function getSlugAttribute() {
        return trim(strtolower(str_replace(' ', '-', preg_replace("/[^a-zA-Z0-9 ]/", "", $this->short_name))));
    }

    public function trails()
    {
        return $this->hasMany(Trail::class, 'area_id', 'id');
    }

    public function weather()
    {
        return $this->hasMany(AreaWeather::class, 'area_id', 'id');
    }

    /**
     * Pick one area from $areas at random, favoring the popular ones (getAreaWeights()).
     *
     * Adventurousness flattens the weights: at 0 the big draws (Zion, Bryce) dominate, at 1 every eligible area is
     * close to equally likely. Sampling happens over the areas that already passed every filter, so a rejected pick
     * can't silently fall back to the base area (the old scope picked one id first and filtered afterwards).
     *
     * @param \Illuminate\Support\Collection<int, Area> $areas
     */
    public static function pickWeighted($areas, float $adventurousness = 0.5): ?Area
    {
        if ($areas->isEmpty()) {
            return null;
        }
        $exponent = 1.5 - max(0.0, min(1.0, $adventurousness));
        $weights = self::getAreaWeights();
        $scored = $areas->map(fn(Area $area) => [$area, pow($weights[$area->id] ?? 50, $exponent)]);

        $total = $scored->sum(fn($pair) => $pair[1]);
        $roll = mt_rand() / mt_getrandmax() * $total;
        foreach ($scored as [$area, $weight]) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $area;
            }
        }
        return $scored->last()[0];
    }

    /**
     * Whether the area is inside its seasonal closure (closed_from..closed_until, MM-DD, may wrap the new year).
     */
    public function isClosedOn(string $date): bool
    {
        if (!$this->closed_from || !$this->closed_until) {
            return false;
        }
        $monthDay = date('m-d', strtotime($date));
        if ($this->closed_from <= $this->closed_until) {
            return $monthDay >= $this->closed_from && $monthDay <= $this->closed_until;
        }
        return $monthDay >= $this->closed_from || $monthDay <= $this->closed_until;
    }

    /** "from December 1 to May 14", or null for areas that never close. */
    public function closureDescription(): ?string
    {
        if (!$this->closed_from || !$this->closed_until) {
            return null;
        }
        $format = fn(string $monthDay) => date('F j', strtotime('2001-' . $monthDay));
        return 'from ' . $format($this->closed_from) . ' to ' . $format($this->closed_until);
    }

    public function scopeOpenOn(Builder $query, string $date)
    {
        $monthDay = date('m-d', strtotime($date));
        return $query->where(function($q) use ($monthDay) {
            $q->whereNull('closed_from')
                ->orWhereNull('closed_until')
                ->orWhere(fn($q) => $q->whereColumn('closed_from', '<=', 'closed_until')
                    ->where(fn($q) => $q->where('closed_from', '>', $monthDay)->orWhere('closed_until', '<', $monthDay)))
                ->orWhere(fn($q) => $q->whereColumn('closed_from', '>', 'closed_until')
                    ->where('closed_from', '>', $monthDay)->where('closed_until', '<', $monthDay));
        });
    }

    public function isTooCold($day, $period, $activityType) {
        if ($activityType === 'hiking') {
            if (!$this->where('id', $day->area_id)->isHotterThan(0, Period::getStartTimeForActivities($period))->exists()) {
                return true;
            }
        }
        return false;
    }

    public function isTooHot($day, $period, $activityType) {
        if ($activityType === 'hiking') {
            if ($this->where('id', $day->area_id)->isColderThan(100, Period::getStartTimeForActivities($period))->exists()) {
                return true;
            }
        }
        return false;
    }

    public function scopeTemperatureIsAbout(Builder $query, $temperature, $startDateTime = null, $endDateTime = null, $tolerance = 10)
    {
        return $this->hasTemperature($query, '~', $temperature, $startDateTime, $endDateTime);
    }

    public function scopeIsColderThan(Builder $query, $temperature, $startDateTime = null, $endDateTime = null)
    {
        return $this->hasTemperature($query, '<=', $temperature, $startDateTime, $endDateTime);
    }

    public function scopeIsHotterThan(Builder $query, $temperature, $startDateTime = null, $endDateTime = null)
    {
        return $this->hasTemperature($query, '>=', $temperature, $startDateTime, $endDateTime);
    }

    public function scopeIsRaining(Builder $query, $startDateTime = null, $endDateTime = null)
    {
        return false;
    }

    /**
     * If there's an area, we can determine the temperature. Use the min/max temp, depending on $greaterThanOrLessThen.
     * If $startDateTime and $endDateTime are null, use the current date and time.
     * If only $startDateTime is set, assume $endDateTime is 3 hours.
     */
    private function hasTemperature($query, $greaterThanOrLessThanOrAbout, $temperature, $startDateTime = null, $endDateTime = null, $tolerance = 10)
    {
        if ($startDateTime === null) {
            if ($endDateTime !== null) {
                $endDateTime = null;
            }
            $startDateTime = date('Y-m-d H:i:s');
        }

        if ($endDateTime === null) {
            $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime . ' +3 hours'));
        }

        return $query->whereDoesntHave('weather', function($query) use ($startDateTime, $endDateTime) {
            $query->where('start', '>=', $startDateTime)
                ->where('end', '<=', $endDateTime);
        })->orWhereHas('weather', function($query) use ($greaterThanOrLessThanOrAbout, $temperature, $startDateTime, $endDateTime, $tolerance) {
            if ($greaterThanOrLessThanOrAbout === '~') {
                $query->where(function($query) use ($temperature, $tolerance) {
                    $query->where('temperature', '>=', $temperature - $tolerance)
                        ->orWhere('temperature', '<=', $temperature + $tolerance);
                });
            } else {
                $query->where('temperature', $greaterThanOrLessThanOrAbout, $temperature)
                    ->where('start', '>=', $startDateTime)
                    ->where('end', '<=', $endDateTime);
            }
        });
    }

    public const MAX_AREA_SEARCH_MILES = 500;

    /**
     * The nearest area, widening the search 20 miles at a time. Gives up (null) past MAX_AREA_SEARCH_MILES instead
     * of looping forever when nothing is in range (e.g. an empty areas table or bad coordinates).
     */
    public static function getAreaForLatLng(float $latitude, float $longitude): ?Area
    {
        for ($distance = 30; $distance <= self::MAX_AREA_SEARCH_MILES; $distance += 20) {
            $area = Area::whereProximity($latitude, $longitude, $distance, true)->first();
            if ($area) {
                return $area;
            }
        }
        return null;
    }
}
