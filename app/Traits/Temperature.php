<?php

namespace App\Traits;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Itinerary\Period;
use App\Models\Location;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;

trait Temperature
{
    public function isTooCold($day, $period, $activityType) {
        if ($activityType === 'hiking') {
            if (!Area::where('id', $day->area_id)->isHotterThan(0, Period::getStartTimeForActivities($period))->exists()) {
                return true;
            }
        }
        return false;
    }

    public function isTooHot($day, $period, $activityType) {
        if ($activityType === 'hiking') {
            if (!Area::where('id', $day->area_id)->isColderThan(100, Period::getStartTimeForActivities($period))->exists()) {
                return true;
            }
        }
        return false;
    }
}
