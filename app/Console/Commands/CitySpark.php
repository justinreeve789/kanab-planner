<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CitySpark extends Command
{
    const SOURCE_TYPE = 'cityspark';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:cityspark';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve events from cityspark.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client();
        $queries = $this->getQueries();
        foreach ($queries as $query) {
            $response = $client->post('https://portal.cityspark.com/v1/events/CitySparkPortalHome', [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json; charset=UTF-8'],
                'json' => $query,
            ]);
            $events = json_decode($response->getBody()->getContents());
            $this->processEvents($events->Value);
        }
    }

    protected function getQueries(): array
    {
        $today = date('Y-m-d');
        $nextYear = date('Y-m-d', strtotime('+1 year'));

        $jsonPayload = [
            "ppid" => 8206,
            "start" => $today . 'T00:00',
            "end" => $nextYear . 'T00:00',
            "labels" => [],
            "pick" => false,
            "tps" => null,
            "sparks" => false,
            "sort" => "Popularity",
            "category" => [],
            "distance" => 50,
            "lat" => 37.1052853,
            "lng" => -112.1430215,
            "search" => "",
            "skip" => 0,
            "defFilter" => "all",
        ];

        $kanab = $bryce = $northRim = $zion = $jsonPayload;
        $bryce['lat'] =  37.82276;
        $bryce['lng'] = -112.43576;
        $bryce['distance'] = 25;
        $zion['lat'] =  37.2982022;
        $zion['lng'] = -113.0263005;
        $zion['distance'] = 25;
        $northRim['lat'] = 36.2134719;
        $northRim['lng'] = 112.0580965;
        $northRim['distance'] = 50;

        return [$kanab, $bryce, $zion, $northRim];
    }

    protected function processEvents(array $events)
    {
        foreach ($events as $event) {
            if (isset($event->Price)) {
                $price = $event->Price > 0 ? $event->Price : null;
                if ($event->PriceHigh > 0) {
                    $price .= ' - ' . $event->PriceHigh;
                }
            }

            $evtObj = Event::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $event->PId], [
                'name' => $event->Name,
                'description' => $event->Description,
                'free' => $event->Free,
                'price' => $price,
                'start_date' => date('Y-m-d', strtotime($event->DateStart)),
                'start_time' => date('H:i', strtotime($event->DateStart)),
                'end_date' => date('Y-m-d', strtotime($event->DateEnd)),
                'end_time' => date('H:i', strtotime($event->DateEnd)),
                'raw_data' => json_encode($event),
            ]);

            $cityState = explode(',', $event->CityState);
            $locName = isset($event->Venue) ? $event->Venue : null;
            $locAddress = isset($event->Address) ? $event->Address : null;
            $locCity = isset($cityState[0]) ? trim($cityState[0]) : null;
            $locState = isset($event->cityState[1]) ? trim($cityState[1]) : null;
            $locZip = isset($event->Zip) ? $event->Zip : null;

            $location = Location::search(['address' => $locAddress, 'zip' => $locZip]) ??
                Location::search(['address' => $locAddress, 'city' => $locCity]) ??
                Location::search(['name' => $locName, 'city' => $locCity, 'state' => $locState]) ??
                Location::search(['name' => $locName, 'address' => $locAddress]);
            if ($location) {
                $evtObj->location_id = $location->id;
            } else {
                $location = new Location([
                    'name' => $event->Venue,
                    'area_id' => Area::KANAB,
                    'address' => $locAddress,
                    'city' => $locCity,
                    'state' => $locState,
                    'zip' => $locZip,
                    'phone' => $event->VenuePhone,
//                    'email' => $event->VenueEmail,
//                    'url' => $event->Url,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                ]);
                $location->save();
                $evtObj->location_id = $location->id;
            }
            $evtObj->save();
        }
    }
}
