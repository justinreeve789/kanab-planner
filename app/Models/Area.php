<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    const BRYCE = 1;
    const NORTH_RIM = 2;
    const KANAB = 3;
    const LAKE_POWELL = 4;
    const PAGE_AZ = 5;
    const ST_GEORGE = 6;
    const ZION = 7;
}
