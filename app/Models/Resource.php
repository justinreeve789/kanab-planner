<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
use App\Traits\Usable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;
    use Usable;

    /**
     * @return \App\Models\Builder\CustomQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomQueryBuilder($query);
    }

    public function isEvent() {
        return get_class($this) === 'App\\Models\\Event';
    }

    public function isLocation() {
        return get_class($this) === 'App\\Models\\Location';
    }

    public function isRestaurant() {
        return get_class($this) === 'App\\Models\\Restaurant';
    }

    public function isTask() {
        return get_class($this) === 'App\\Models\\Itinerary\\Task';
    }

    public function isTrail() {
        return get_class($this) === 'App\\Models\\Trail';
    }
}
