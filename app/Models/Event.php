<?php

namespace App\Models;

use App\Traits\ActivityData;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Resource
{
    use ActivityData;
    use HasFactory;
    use Searchable;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];

    public function dates()
    {
        return $this->hasMany(EventDate::class, 'event_id', 'id');
    }

    public function links()
    {
        return $this->hasMany(EventLink::class, 'event_id', 'id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
