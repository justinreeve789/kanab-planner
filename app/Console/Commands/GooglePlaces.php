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
                $types = [
                    "art_gallery",
                    "bicycle_store",
                    "campground",
                    "car_rental",
                    "gas_station",
                    "landmark",
                    "lodging",
                    "museum",
                    "rv_park",
                    "tourist_attraction",
                    "travel_agency",
                ];
                foreach ($types as $type) {
                    $items = $this->getPlacesForArea($area, $type);
                    foreach ($items as $item) {
                        $this->parseLocationResult($item);
                    }
                    sleep(5);
                }
            }

            if ($doRestaurants) {
                $types = ['bar', 'bakery', 'cafe', 'restaurant'];
                foreach ($types as $type) {
                    $restaurants = $this->getRestaurantsForArea($area, $type);
                    foreach ($restaurants as $item) {
                        $this->parseRestaurantResult($item);
                    }
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

        $location = Location::search($name)->where('name', $name)->options(['rankingScoreThreshold' => 0.9])->first() ??
            Location::whereRaw('LOWER(TRIM(address)) = ?', [strtolower(trim($address))])->first() ??
            Location::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])->first() ??
            Location::whereRaw('LOWER(TRIM(phone)) = ?', [strtolower(trim($phone))])->first() ??
            Location::whereRaw('LOWER(TRIM(url)) = ?', [strtolower(trim($url))])->first();
        if (!$location) {
            $location = new Location([
                'name' => $name,
                'source_type' => self::SOURCE_TYPE,
                'source_id' => $placeId,
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
        // Get opening hours for this place.
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

    protected function getRestaurantsForArea($area, $type)
    {
        return $this->getPlacesForLatLng($area->latitude, $area->longitude, $type, 30000);
    }

    protected function getPlacesForLatLng(float $lat, float $lng, string $type, $radiusMeters = 50000)
    {
        $location = $lat . ',' . $lng;

        $api_url =
            "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$location&radius=$radiusMeters&type=$type&fields=opening_hours&key=" . getenv('GOOGLE_API_KEY');

        $response = file_get_contents($api_url);

        if ($response === false) {
            die('Error fetching data from Google API');
        }

        $data = json_decode($response, true);

        if ($data === null) {
            die('Error decoding JSON');
        }

        if ($data['status'] === 'OK') {
            return $data['results'];
        } else {
            echo 'Google API returned an error: ' . $data['status'];
            echo '<pre>'; print_r($data); echo '</pre>';
        }
        return [];
    }
}
