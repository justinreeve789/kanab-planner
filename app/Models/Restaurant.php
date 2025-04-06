<?php

namespace App\Models;

use App\Traits\ActivityData;
use App\Traits\ResourceScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SearchableResource;

class Restaurant extends Resource
{
    use ActivityData;
    use HasFactory;
    use ResourceScopes;
    use SearchableResource;

    protected $casts = ['deviation_percent' => 'float'];
    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];

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

    public function getImagesAttribute()
    {
        return $this->location->images;
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }
}
