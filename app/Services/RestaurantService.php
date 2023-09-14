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
        // Initialize the Places API Service
                $placesService = new Google_Service_Places($client);

        // Restaurant name or Place ID
                $restaurantName = 'Restaurant Name'; // You can also use a Place ID    }

    private function getPlace()
    {}
}
