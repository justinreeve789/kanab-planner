<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailGeoJson extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'trail_geojson';
}
