<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    public $activityType;   // hiking, off-roading, etc.
}
