<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
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

    /**
     * @return \App\Models\Builder\CustomQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomQueryBuilder($query);
    }

    public function weather()
    {
        return $this->hasMany(AreaWeather::class, 'area_id', 'id');
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
