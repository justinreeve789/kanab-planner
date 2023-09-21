<?php

namespace App\Models;

use App\Models\LocationHour;
use App\Traits\Proximity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Resource
{
    use HasFactory;
    use Proximity;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'area_id'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function data()
    {
        return $this->hasMany(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'locations');
    }

    public function hours()
    {
        return $this->hasMany(LocationHour::class, 'location_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(LocationImage::class, 'location_id', 'id');
    }

    public function text()
    {
        return $this->hasOne(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'locations');
    }

    public function isOpen($dateTime)
    {
        $dayOfWeek = date('w', strtotime($dateTime));
        $time = date('H:i:s', strtotime($dateTime));
    }

    static public function search($params = []): ?Location {
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (!$value || trim($value) == '') {
                    return null;
                }
                if (!isset($loc)) {
                    $loc = Location::where($key, $value);
                }
                $loc = $loc->where($key, $value);
            }
            $location = $loc->first();
            if ($location) {
                return $location;
            }
        }
        return null;
    }
}
