<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\Image;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\LocationImage;
use App\Models\LocationTag;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Models\TrailTag;
use App\Services\ConversionService;
use App\Services\RestaurantService;
use App\Services\TrailService;
use Illuminate\Console\Command;

class GooglePlaces extends Command
{
    const SOURCE_TYPE = 'googleplaces';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:googleplaces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from Google';

    private $areaUrls;
    private $browser;
    private $conversionService;
    private $restaurantService;
    private $trailService;
    private $trailUrls;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->restaurantService = new RestaurantService();
        $areas = Area::all();
        foreach ($areas as $area) {
            $restaurants = $this->getRestaurantsForArea($area);
            foreach ($restaurants as $item) {
                $this->parseRestaurantResult($item);
            }
        }
    }

    protected function parseRestaurantResult($result)
    {
        if ($result['business_status'] !== 'OPERATIONAL') {
            return;
        }

        try {
            $latitude = $result['geometry']['location']['lat'];
            $longitude = $result['geometry']['location']['lng'];
            $name = $result['name'];
            $placeId = $result['place_id'];
            $photos = $result['photos'] ?? [];
            $priceLevel = $result['price_level'] ?? null;
            $types = $result['types'];
        } catch (\Exception $e) {
            print_r($result);
            print_r($e);
            die;
        }

        $details = $this->getDetailsForPlace($placeId);

        try {
            $address = $details['formatted_address'];
            $city = null;
            $curbsidePickup = $details['curbside_pickup'] ?? null;
            $description = isset($details['editorial_summary']) ? $details['editorial_summary']['overview'] : null;
            $dineIn = $details['dine_in'] ?? null;
            $email = null;
            $openingHours = $details['opening_hours'] ?? null;
            $phone = $details['formatted_phone_number'] ?? null;
            $reservable = $details['reservable'] ?? null;
            $url = $details['website'] ?? null;
        } catch (\Exception $e) {
            print_r($details);
            print_r($e);
            die;
        }

        $result['details'] = $details;
        $rawData = json_encode($result);

        $location = Location::search(['name' => $name, 'address' => $address]) ??
            Location::search(['address' => $address, 'city' => $city]) ??
            Location::search(['email' => $email]) ??
            Location::search(['phone' => $phone]) ??
            Location::search(['url' => $url]);
        if (!$location) {
            $location = new Location([
                'name' => $name,
                'description' => $description,
                'area_id' => ($latitude !== null && $longitude !== null) ? Area::getAreaForLatLng($latitude, $longitude)->id : 0,
                'address' => $address,
                'city' => null,
                'state' => null,
                'zip' => null,
                'phone' => $phone,
                'email' => $email,
                'url' => $url,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            $location->save();
        }

        if ($openingHours) {
            LocationHour::where('location_id', $location->id)->delete();
            try {
                foreach ($openingHours['periods'] as $period) {
                    if (!isset($period['open']) || !isset($period['close'])) {
                        continue;
                    }
                    $locationHour = new LocationHour([
                        'location_id' => $location->id,
                        'day_of_week' => $period['open']['day'],
                        'time_open' => date('H:i', strtotime($period['open']['time'])),
                        'time_close' => date('H:i', strtotime($period['close']['time'])),
                    ]);
                    $locationHour->save();
                }
            } catch (\Exception $e) {
                print_r($result);
                print_r($openingHours);
                die;
            }
        }

//        foreach ($photos as $photo) {
//            LocationImage::updateOrCreate(['location_id' => $location->id, 'url' => $rst->imgpath]);
//        }

        foreach ($types as $type) {
            LocationTag::updateOrCreate(['location_id' => $location->id, 'tag' => $type]);
        }

        Restaurant::updateOrCreate(['location_id' => $location->id], [
            'name' => $name,
            'source_type' => self::SOURCE_TYPE,
            'source_id' => $placeId,
            'description' => $description,
            'average_price' => $this->restaurantService->calculateAveragePriceForPriceLevel($priceLevel),
            'curbside_pickup' => $curbsidePickup,
            'dine_in' => $dineIn,
            'reservable' => $reservable,
            'raw_data' => $rawData,
        ]);

        $restaurant = new Restaurant([

        ]);
    }

    protected function getDetailsForPlace($placeId)
    {
        // Now, make a request to the Google Places Details API to get opening hours for this place.
        $detailsApiUrl = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$placeId&key=" . getenv('GOOGLE_API_KEY');
        $detailsResponse = file_get_contents($detailsApiUrl);

        if ($detailsResponse === false) {
            die('Error fetching place details from Google API');
        }

        $detailsData = json_decode($detailsResponse, true);

        if ($detailsData === null || $detailsData['status'] !== 'OK') {
            return; // Skip this place if there was an error or no opening hours data
        }

        return $detailsData['result'];
    }

    protected function getRestaurantsForArea($area)
    {
        $location = $area->latitude . ',' . $area->longitude;
        $radius = 30000; // 30 kilometers

        // Set up the Google Places API endpoint
        $api_url =
            "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$location&radius=$radius&type=restaurant&fields=opening_hours&key=" . getenv('GOOGLE_API_KEY');

        // Send a GET request to the Google Places API
        $response = file_get_contents($api_url);

        // Check if the request was successful
        if ($response === false) {
            die('Error fetching data from Google API');
        }

        // Parse the JSON response
        $data = json_decode($response, true);

        // Check if the JSON was parsed successfully
        if ($data === null) {
            die('Error decoding JSON');
        }

        // Check if the API returned any results
        if ($data['status'] === 'OK') {
            return $data['results'];
        } else {
            // Handle API error
            echo 'Google API returned an error: ' . $data['status'];
        }
        return [];
    }
}
