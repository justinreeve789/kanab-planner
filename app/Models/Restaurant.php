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

    /**
     * TODO: Test this calculation.
     */
    public function getAverageHours(array $params = [])
    {
        if (isset($params['date']) && isset($params['start_time'])) {
            $resourceBusy = ResourceBusy::where([
                'resource_table' => $this->getTable(),
                'resource_id' => $this->id,
                'hour' => date('H', strtotime($params['start_time'])),
                'day' => date('w', strtotime($params['date'])),
            ])->orderBy('collected_date', 'DESC')->first();
            if ($resourceBusy) {
                return min(0.5, max(3.5, self::DEFAULT_AVERAGE_HOURS * ($resourceBusy->percentage * 2)));
            }
        }
        return parent::DEFAULT_AVERAGE_HOURS;
    }

    public function getAveragePrice()
    {
        return $this->average_price;
    }

    public function hasBreakfast(string $date)
    {
        $dayOfWeek = date('N', strtotime($date));
        if (!isset($this->hours[$date][$dayOfWeek])) {
            $this->hours[$date][$dayOfWeek] = $this->hours()->where(['location_id' => $this->location_id, 'day_of_week'])->first();
        }
        $hours = $this->hours[$date][$dayOfWeek];
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
