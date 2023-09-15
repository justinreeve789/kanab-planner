<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Resource
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'area_id'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
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
