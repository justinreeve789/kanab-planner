<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Console\Command;

class UpdatePrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates price information';

    private $restaurantService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->options('trails');

        $this->restaurantService = new RestaurantService();

        if ($doRestaurants) {
            $this->processRestaurants();
        }
    }

    protected function processRestaurants()
    {
        $restaurants = Restaurant::whereIsNull('average_price')->get();
        foreach ($restaurants as $item) {
        }
    }
}
