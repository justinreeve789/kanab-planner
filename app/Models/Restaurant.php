<?php

namespace App\Models;

use App\Traits\ActivityData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use ActivityData;
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];

    public function data()
    {
        return $this->hasOne(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'restaurant');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function tags()
    {
        return $this->hasMany(LocationTag::class, 'location_id', 'location_id');
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }
}
