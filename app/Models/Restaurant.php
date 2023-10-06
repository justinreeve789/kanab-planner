<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
use App\Traits\ActivityData;
use App\Traits\ResourceScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Resource
{
    use ActivityData;
    use HasFactory;
    use ResourceScopes;

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

    public function calculateEndDate(string $startDateTime)
    {
        // TODO: Use busyness and popularity rankings.
        return date('Y-m-d H:i:s', strtotime($startDateTime) + (90 * 60));
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }
}
