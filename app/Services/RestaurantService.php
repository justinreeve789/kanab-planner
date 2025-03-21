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

    public function calculateAveragePriceForPriceLevel(?int $priceLevel)
    {
        switch ($priceLevel) {
            case 0:
                $averagePrice = 8;
                break;
            case 1:
                $averagePrice = 10;
                break;
            case 2:
                $averagePrice = 15;
                break;
            case 3:
                $averagePrice = 30;
                break;
            case 4:
                $averagePrice = 50;
                break;
            default:
                $averagePrice = 15;
        }
        return $averagePrice;
    }
}
