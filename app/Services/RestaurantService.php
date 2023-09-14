<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Restaurant;
use Google\Client;

class RestaurantService
{
    public function __construct()
    {
    }

    public function getClient() {
        if (!isset($this->googleClient)) {
            $this->googleClient = new Client();
        }
        return $this->googleClient;
    }

    public function getAveragePriceForRestaurant($restaurantId)
    {
        $restaurant = Restaurant::with('location')->where('id', $restaurantId)->first();
    }

    private function getPlace()
    {}
}
