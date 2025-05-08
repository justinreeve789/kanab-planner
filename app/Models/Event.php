<?php

namespace App\Models;

use App\Traits\ActivityData;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Event extends Resource
{
    const MAXIMUM_DAYS_IN_FUTURE_FOR_FEATURED_EVENTS = 180;
    const MINIMUM_DAYS_IN_FUTURE_FOR_FEATURED_EVENTS = 7;

    use HasFactory;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data'];
//    protected $with = ['dates', 'location'];

    public function toSearchableArray()
    {
        $searchableArray = parent::toSearchableArray();
        $searchableArray['start_date'] = strtotime($this->start_date);
        $searchableArray['end_date'] = strtotime($this->end_date);
        return $searchableArray;
    }

    public function area()
    {
        return $this->hasOneThrough(Area::class, Location::class, 'id', 'id', 'location_id', 'area_id');
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

    public function getImageIdsAttribute()
    {
        return $this->images->pluck('id')->all();
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

    public static function getFeatured(array $areaIds, int $featuredCount = 3, $startDate = null, $endDate = null)
    {
        $index = 0;
        $maxDate = date('Y-m-d 00:00:00', strtotime('+' . self::MAXIMUM_DAYS_IN_FUTURE_FOR_FEATURED_EVENTS . ' days', time()));
        $minDate = date('Y-m-d 00:00:00', strtotime('+' . self::MINIMUM_DAYS_IN_FUTURE_FOR_FEATURED_EVENTS . ' days', time()));

        // Favor events in the summer when a date range isn't included.
        $now = Carbon::now();
        $currentYear = $now->year;

        if ($now->month <= 5) {
            $nextSummerStart = Carbon::create($currentYear, 6, 21, 0, 0, 0);
            $nextSummerEnd = Carbon::create($currentYear, 9, 22, 23, 59, 59);
        } else {
            $nextSummerStart = Carbon::create($currentYear + 1, 6, 21, 0, 0, 0);
            $nextSummerEnd = Carbon::create($currentYear + 1, 9, 22, 23, 59, 59);
        }

        $resources = self::where('description', '!=', '')
            ->where('start_date', '>=', $minDate)
            ->where('end_date', '<=', $maxDate)
            ->whereHas('area', function($q) use ($areaIds) {
            $q->whereIn('areas.id', $areaIds);
        })->orderByRaw('
            CASE
            WHEN (start_date >= ? AND start_date <= ?)
                THEN FLOOR(RAND(' . date('YmdH') . ') * 10) + 1
            ELSE
                FLOOR(RAND(' . date('YmdH') . ') * 100) + 1
            END ASC', [
                $nextSummerStart->toDateTimeString(),
                $nextSummerEnd->toDateTimeString(),
            ]
        )->take($featuredCount)
            ->get();
        $resourceIds = [];
        foreach ($resources as $resource) {
            $resourceIds[] = $resource->id;
            $index++;
        }
        while ($index++ < $featuredCount) {
            $resource = self::where('description', '!=', '')
                ->where('start_date', '>=', $minDate)
                ->where('end_date', '<=', $maxDate)
                ->whereHas('area', function($q) use ($areaIds) {
                $q->whereIn('areas.id', $areaIds);
            })->whereNotIn('events.id', $resourceIds)
                ->orderByRaw('
                CASE
                WHEN (start_date >= ? AND start_date <= ?)
                    THEN FLOOR(RAND(' . date('YmdH') . ') * 10) + 1
                ELSE
                    FLOOR(RAND(' . date('YmdH') . ') * 100) + 1
                END ASC', [
                    $nextSummerStart->toDateTimeString(),
                    $nextSummerEnd->toDateTimeString(),
                ]
            )->first();
            if ($resource) {
                $resources[] = $resource;
                $resourceIds[] = $resource->id;
            }
        }
        return $resources;
    }
}
