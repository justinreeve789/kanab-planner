<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\Trail;

class WeatherService
{
    const NWS_API_URL = 'https://api.weather.gov/points/';

    private $nwsAreas = [];

    public function getNwsArea($areaId)
    {
        if (!isset($this->nwsService))
    }
    public function getForecastForLatLng($latitude, $longitude, $date = null)
    {
    }
}