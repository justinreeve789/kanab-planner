<?php

namespace App\Models;

use App\Traits\ActivityData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Resource
{
    use ActivityData;
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];

    public function data()
    {
        return $this->hasMany(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'events');
    }

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

    public function text()
    {
        return $this->hasOne(ResourceData::class, 'resource_id', 'id')->where('resource_text', 'events');
    }

    public static function between($startDateTime, $endDateTime)
    {
        if ((int) date('Hi', strtotime($endDateTime)) === 0) {
            $endDateTime = date('Y-m-d 23:59:59', strtotime($endDateTime));
        }

        return Event::whereHas('dates', function($query) use ($startDateTime, $endDateTime) {
            $query->where('event_dates.start_date', '>=', date('Y-m-d H:i:s', strtotime($startDateTime)));
            $query->where('event_dates.start_date', '<=', date('Y-m-d H:i:s', strtotime($endDateTime)));
            $query->where('event_dates.end_date', '<=', date('Y-m-d H:i:s', strtotime($endDateTime)));
        });
    }

    public static function sortByDeviation($events, string $prop, float $value)
    {
    }
}
