<?php

namespace App\Models;

use App\Traits\ActivityData;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Resource
{
    use HasFactory;
    use SearchableResource;

    protected $appends = ['area_id', 'type'];
    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];
//    protected $with = ['dates', 'location'];

    public function toSearchableArray()
    {
        $searchableArray = $this->getAttributes();
        $searchableArray['area_id'] = $this->location->area_id;
        $searchableArray['type'] = $this->type;
        return $searchableArray;
    }

    public function dates()
    {
        return $this->hasMany(EventDate::class, 'event_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(EventImage::class, 'event_id', 'id');
    }

    public function links()
    {
        return $this->hasMany(EventLink::class, 'event_id', 'id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function getAreaIdAttribute()
    {
        return $this->location->area_id;
    }

    public function scopeAfter(Builder $query, $startDateTime)
    {
        return $query->whereHas('dates', function (Builder $subQuery) use ($startDateTime) {
            $subQuery->whereRaw("
                    ? >= DATE_FORMAT(DATE(event.start_date), '%Y-%m-%d 10:00:00')
                ", [$startDateTime]);
        });
    }

    public function scopeBetweenDates(Builder $query, $startDateTime, $endDateTime)
    {
        return $query->whereHas('dates', function (Builder $subQuery) use ($startDateTime, $endDateTime) {
            $subQuery->whereRaw("
                    ? >= COALESCE(
                        event_dates.start_date,
                        DATE_FORMAT(DATE(event_dates.start_date), '%Y-%m-%d 10:00:00')
                    )
                ", [$startDateTime]);
            $subQuery->whereRaw("
                    ? <= COALESCE(
                        event_dates.end_date,
                        CASE
                            WHEN TIME(event_dates.start_date) >= '22:00:00'
                                THEN DATE_FORMAT(DATE(event_dates.start_date), '%Y-%m-%d 23:59:59')
                            ELSE
                                LEAST(
                                    DATE_ADD(event_dates.start_date, INTERVAL 4 HOUR),
                                    DATE_FORMAT(DATE(event_dates.start_date), '%Y-%m-%d 22:00:00')
                                )
                        END
                    )
                ", [$endDateTime]);
        });
    }
}
