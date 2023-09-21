<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ReturnAllProperties;

class Weather extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    const CLOUDY = 'Cloudy';
    const MOSTLY_CLEAR = 'Mostly Clear';
    const SUNNY = 'Sunny';

    protected $guarded = [];

    private $date;
    private $detailedForecast;
    private $high;
    private $low;
    private $precipitationChance;
    private $shortForecast;
}
