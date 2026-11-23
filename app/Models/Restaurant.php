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

    private $hours = [];

    public function toSearchableArray()
    {
        $searchableArray = parent::toSearchableArray();
        $searchableArray['area_id'] = $this->area->id;
        return $searchableArray;
    }

    public function area()
    {
        return $this->hasOneThrough(Area::class, Location::class, 'id', 'id', 'location_id', 'area_id');
    }

    public function hours()
    {
        return $this->hasMany(LocationHour::class, 'location_id', 'location_id');
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

//    public function getImagesAttribute()
//    {
//        return $this->location->images;
//    }

    public function getImageIdsAttribute()
    {
        return $this->images->pluck('id')->all();
    }

    public const MIN_AVERAGE_HOURS = 0.5;
    public const MAX_AVERAGE_HOURS = 3.5;

    /**
     * How long a meal here takes, scaled by how busy the restaurant usually is at that hour.
     *
     * Popular-times data is collected per *location* (LocationService::updateBusynessForLocation writes
     * resource_table = 'locations', resource_id = location id), with day_of_week 0–6 and a 0–100 percentage.
     * 0% busy → half the base time, 50% → the base time, 100% → 1.5×; clamped to 0.5–3.5 hours.
     *
     * $params: date, start_time, and optionally base_hours (defaults to DEFAULT_AVERAGE_HOURS).
     */
    public function getAverageHours(array $params = [])
    {
        $base = (float) ($params['base_hours'] ?? self::DEFAULT_AVERAGE_HOURS);
        if (isset($params['date']) && isset($params['start_time']) && $this->location_id) {
            $resourceBusy = ResourceBusy::where([
                'resource_table' => 'locations',
                'resource_id' => $this->location_id,
                'hour' => (int) date('G', strtotime($params['start_time'])),
                'day_of_week' => (int) date('w', strtotime($params['date'])),
            ])->orderBy('collected_date', 'DESC')->first();
            if ($resourceBusy) {
                $hours = $base * (0.5 + $resourceBusy->percentage / 100);
                return max(self::MIN_AVERAGE_HOURS, min(self::MAX_AVERAGE_HOURS, $hours));
            }
        }
        return $base;
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }

    public static function getFeatured(array $areaIds, int $featuredCount = 3)
    {
        return self::whereHas('images')
            ->whereHas('area', function($q) use ($areaIds) {
                $q->whereIn('areas.id', $areaIds);
            })
            ->where('description', '!=', '')
            ->inRandomOrder(date('YmdH'))
            ->take($featuredCount)->get();
    }
}
