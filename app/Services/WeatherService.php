<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AreaWeather;
use App\Models\Itinerary\Period;
use App\Models\Trail;

class WeatherService
{
    const NWS_API_URL = 'https://api.weather.gov/points/';
    const SOURCE_TYPE = 'nws';

    private $nwsAreas = [];
    private $nwsService;

    public function __construct()
    {
    }

    public function updateForecastForArea(int $areaId)
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Accept-language: en\r\n" .
                    "User-Agent: (dev.visitkanab.org)\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($this->getNwsAreaForecastUrls()[$areaId]['forecast_hourly'], false, $context);
        $data = json_decode($response);
        $this->saveAreaWeather($areaId, $data);
    }

    public function saveAreaWeather(int $areaId, $data)
    {
        AreaWeather::where('area_id', $areaId)->delete();

        foreach ($data->properties->periods as $period) {
            $areaWeather = new AreaWeather([
                'area_id' => $areaId,
                'source_type' => self::SOURCE_TYPE,
                'start' => date('Y-m-d H:i:s', strtotime($period->startTime)),
                'end' => date('Y-m-d H:i:s', strtotime($period->endTime)),
                'temperature' => $period->temperature,
                'temperature_unit' => $period->temperatureUnit,
                'temperature_trend' => $period->temperatureTrend,
                'probability_of_precipitation_pt' => round($period->probabilityOfPrecipitation->value / 100, 2),
                'dewpoint' => $period->dewpoint->value,
                'relative_humidity_pt' => round($period->relativeHumidity->value / 100, 2),
                'wind_speed' => $period->windSpeed,
                'wind_direction' => $period->windDirection,
                'icon' => $period->icon,
                'short_forecast' > $period->shortForecast,
                'detailed_forecast' => $period->detailedForecast,
            ]);
            $areaWeather->save();
        }
    }

    public function getNwsAreaForecastUrls()
    {
        return [
            Area::BRYCE => [
                'forecast' => 'https://api.weather.gov/gridpoints/SLC/72,35/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/SLC/72,35/forecast/hourly',
            ],
            Area::NORTH_RIM => [
                'forecast' => 'https://api.weather.gov/gridpoints/FGZ/65,136/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/FGZ/65,136/forecast/hourly',
            ],
            Area::KANAB => [
                'forecast' => 'https://api.weather.gov/gridpoints/SLC/57,12/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/SLC/57,12/forecast/hourly',
            ],
            Area::LAKE_POWELL => [
                'forecast' => 'https://api.weather.gov/gridpoints/FGZ/90,165/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/FGZ/90,165/forecast/hourly',
            ],
            Area::PAGE_AZ => [
                'forecast' => 'https://api.weather.gov/gridpoints/FGZ/90,164/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/FGZ/90,164/forecast/hourly',
            ],
            Area::ST_GEORGE => [
                'forecast' => 'https://api.weather.gov/gridpoints/SLC/20,19/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/SLC/20,19/forecast/hourly',
            ],
            Area::ZION => [
                'forecast' => 'https://api.weather.gov/gridpoints/SLC/41,25/forecast',
                'forecast_hourly' => 'https://api.weather.gov/gridpoints/SLC/41,25/forecast/hourly',
            ],
        ];
    }

    public function getNwsArea($areaId)
    {
        $area = Area::where('id', $areaId)->first();
    }

    public function getForecastForLatLng($latitude, $longitude, $date = null)
    {
    }
}