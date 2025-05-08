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
     * A lower delta will make the relative distance between each area less, which decreases the randomness.
     */
    public static function getAreaWeights($delta = 1) {
        return [
            Area::BRYCE => 100 * $delta,
            Area::NORTH_RIM => 50 * $delta,
            Area::KANAB => 75 * $delta,
            Area::LAKE_POWELL => 50 * $delta,
            Area::PAGE_AZ => 50 * $delta,
            Area::ST_GEORGE => 10 * $delta,
            Area::ZION => 150 * $delta,
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

    public function scopeInWeightedRandomOrder(Builder $query, $delta = 1)
    {
        // Calculate the total weight.
        $totalWeight = array_sum($this->getAreaWeights());

        // Generate a random number within the total weight.
        $rand = mt_rand(1, $totalWeight);

        // Iterate through the weights to find the selected weight.
        foreach ($this->getAreaWeights() as $areaId => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
               return $query->where('id', $areaId);
            }
        }
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

    public static function getAreaForLatLng(float $latitude, float $longitude): ?Area
    {
        $distance = 30;
        do {
            $area = Area::whereProximity($latitude, $longitude, $distance, true)->first();
            if ($area) {
                return $area;
            }
            $distance += 20;
        } while ($area === null);
        return null;
    }
}
