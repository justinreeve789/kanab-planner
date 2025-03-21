<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class UpdateWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:weather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the weather forecast for the different areas';

    private $weatherService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->weatherService = new WeatherService();
        $areas = Area::all();
        foreach ($areas as $area) {
            $this->weatherService->updateForecastForArea($area->id);
        }
    }
}
