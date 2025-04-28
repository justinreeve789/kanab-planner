<?php

namespace App\Models;

use App\Traits\ResourceScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SearchableResource;

class Restaurant extends Resource
{
    use HasFactory;
    use ResourceScopes;
    use SearchableResource;

    protected $casts = ['deviation_percent' => 'float'];
    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];
//    protected $with = ['location'];

    public function toSearchableArray()
    {
        return parent::toSearchableArray();
    }

    public function getAppends()
    {
        return array_merge(parent::getAppends(), $this->appends);
    }

    public function area()
    {
        return $this->hasOneThrough(Area::class, Location::class, 'id', 'id', 'location_id', 'area_id');
    }

    public function images()
    {
        return $this->hasMany(LocationImage::class, 'location_id', 'location_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function tags()
    {
        return $this->hasMany(LocationTag::class, 'location_id', 'location_id');
    }

    public function getAreaIdAttribute()
    {
        return $this->location->area_id;
    }

    public function getImagesAttribute()
    {
        return $this->location->images;
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }
}
