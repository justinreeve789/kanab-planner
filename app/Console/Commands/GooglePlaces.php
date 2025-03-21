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
    protected $signature = 'import:googleplaces {--events} {--locations} {--restaurants} {--trails}';

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
        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->option('trails');

        $this->restaurantService = new RestaurantService();
        $areas = Area::all();

        foreach ($areas as $area) {
            if ($doLocations) {
                $types = ['aquarium', 'museum', 'tourist_attraction', 'zoo'];
                foreach ($types as $type) {
                    $items = $this->getPlacesForArea($area, $type);
                    foreach ($items as $item) {
                        $this->parseLocationResult($item);
                    }
                }
            }

            if ($doRestaurants) {
                $restaurants = $this->getRestaurantsForArea($area);
                foreach ($restaurants as $item) {
                    $this->parseRestaurantResult($item);
                }
            }
        }
    }

    protected function parseResult($result)
    {
        if (isset($result['business_status']) && $result['business_status'] !== 'OPERATIONAL') {
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

        return [
            'address' => $address,
            'city' => $city,
            'curbsidePickup' => $curbsidePickup,
            'description' => $description,
            'dineIn' => $dineIn,
            'email' => $email,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'openingHours' => $openingHours,
            'phone' => $phone,
            'photos' => $photos,
            'placeId' => $placeId,
            'priceLevel' => $priceLevel,
            'reservable' => $reservable,
            'types' => $types,
            'url' => $url,
        ];
    }

    protected function addLocation($data)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

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
                'url' => substr($url, 0, 255),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'raw_data' => json_encode($data),
            ]);
            $location->save();
        }
        return $location;
    }

    protected function addOpeningHours($location, $data)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
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
    }

    protected function addLocationTags($location, $data)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        foreach ($types as $type) {
            LocationTag::updateOrCreate(['location_id' => $location->id, 'tag' => $type]);
        }
    }

    protected function parseLocationResult($result)
    {
        $data = $this->parseResult($result);
        if ($data) {
            foreach ($data as $key => $value) {
                $$key = $value;
            }
            $location = $this->addLocation($data);
            $this->addLocationTags($location, $data);
            $this->addOpeningHours($location, $data);
        }
    }

    protected function parseRestaurantResult($result)
    {
        $data = $this->parseResult($result);
        $rawData = json_encode($data);
        if ($data) {
            foreach ($data as $key => $value) {
                $$key = $value;
            }
            $location = $this->addLocation($data);
            $this->addLocationTags($location, $data);
            $this->addOpeningHours($location, $data);

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
        }
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

    protected function getPlacesForArea($area, $type)
    {
        return $this->getPlacesForLatLng($area->latitude, $area->longitude, $type, 30000);
    }

    protected function getRestaurantsForArea($area)
    {
        return $this->getPlacesForLatLng($area->latitude, $area->longitude, 'restaurant', 30000);
    }

    protected function getPlacesForLatLng(float $lat, float $lng, string $type, $radiusMeters = 30000)
    {
        $location = $lat . ',' . $lng;

        // Set up the Google Places API endpoint
        $api_url =
            "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$location&radius=$radiusMeters&type=$type&fields=opening_hours&key=" . getenv('GOOGLE_API_KEY');

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
