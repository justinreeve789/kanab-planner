<?php

namespace App\Spiders;

use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;

class CitySparkSpider extends BasicSpider
{
    const SOURCE_TYPE = 'cityspark';

    public array $startUrls = [
        'https://portal.cityspark.com/#/show?location=84741',
    ];

    public function parseList(Response $response): \Generator
    {
        $events = json_decode($response->text());
        foreach ($events->Value as $event) {
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

    /** @return Request[] */
    protected function initialRequests(): array
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
        $kanab = $bryce = $northRim = $zion = $lakePowell = $stGeorge = $pageAz = $jsonPayload;
        $bryce['lat'] =  37.82276;
        $bryce['lng'] = -112.43576;
        $bryce['distance'] = 25;
        $zion['lat'] =  37.2982022;
        $zion['lng'] = -113.0263005;
        $zion['distance'] = 25;
        $northRim['lat'] = 36.2134719;
        $northRim['lng'] = 112.0580965;
        $northRim['distance'] = 50;

        // Kanab
        $requests[] = new Request(
            'GET',
            "https://portal.cityspark.com/v1/events/CitySparkPortalHome",
            [$this, 'parseList'],
            [
                'json' => $kanab,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        // Bryce Canyon
        $requests[] = new Request(
            'GET',
            "https://portal.cityspark.com/v1/events/CitySparkPortalHome",
            [$this, 'parseList'],
            [
                'json' => $bryce,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        // Zion National Park
        $requests[] = new Request(
            'GET',
            "https://portal.cityspark.com/v1/events/CitySparkPortalHome",
            [$this, 'parseList'],
            [
                'json' => $zion,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        // North Rim
        $requests[] = new Request(
            'GET',
            "https://portal.cityspark.com/v1/events/CitySparkPortalHome",
            [$this, 'parseList'],
            [
                'json' => $northRim,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        return $requests;
    }

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];
    public array $spiderMiddleware = [
        //
    ];

    public array $itemProcessors = [
        //
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        // todo...
    }
}
