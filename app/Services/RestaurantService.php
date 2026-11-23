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

    /**
     * Google Places price_level (0–4) to an average per-person meal price. An unknown level gets the middle price.
     * (This was a loose switch, so a null level matched `case 0` and every unpriced restaurant looked like the
     * cheapest kind.)
     */
    public function calculateAveragePriceForPriceLevel(?int $priceLevel)
    {
        return match ($priceLevel) {
            0 => 8,
            1 => 10,
            2 => 15,
            3 => 30,
            4 => 50,
            default => 15,
        };
    }
}
