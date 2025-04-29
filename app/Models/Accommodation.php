<?php

namespace App\Models;

use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accommodation extends Resource
{
    use HasFactory;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $table = 'accommodations';

    public function toSearchableArray()
    {
        $searchableArray = $this->toArray();
        $searchableArray['area_id'] = $this->area_id;
        return $searchableArray;
    }

    public function getAreaIdAttribute()
    {
        return Area::KANAB;
    }

    public function area()
    {
        return $this->hasOneThrough(Area::class, Location::class, 'id', 'id', 'location_id', 'area_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(AccommodationImage::class, 'accommodation_id', 'id');
    }
}
