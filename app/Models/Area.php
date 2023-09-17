<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
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
