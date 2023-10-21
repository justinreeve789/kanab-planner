<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\EventLink;
use App\Models\Location;
use App\Models\ResourceText;
use App\Services\ConversionService;
use App\Services\OCRService;
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
    protected $signature = 'import:cityspark';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import events from cityspark.com';

    private $client;
    private $conversionService;
    private $ocrService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->conversionService = new ConversionService();

        $this->client = new Client();
        $queries = $this->getQueries();
        foreach ($queries as $areaId => $query) {
            $response = $this->client->post('https://portal.cityspark.com/v1/events/CitySparkPortalHome', [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json; charset=UTF-8'],
                'json' => $query,
            ]);
            $events = json_decode($response->getBody()->getContents());
            $this->processEvents($events->Value, $areaId);
        }

        $events = Event::where('source_type', 'cityspark')->get();
        foreach ($events as $event) {
            $this->processEventPage($event->source_id);
        }
    }

    private function skippedEvents(): array {
        return [
            'Book Club',
            'Book Discussion Group',
            'Dixie High',
            'High School',
            'Meeting',
            'Moms \'n Tots',
            'Movie',
        ];
    }

    private function getProp($data, $prop)
    {
        return isset($data->{$prop}) && $data->{$prop} != '' ? $data->{$prop} : null;
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
            "distance" => 30,
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

        return [
            Area::KANAB => $kanab,
            Area::BRYCE => $bryce,
            Area::ZION => $zion,
            Area::NORTH_RIM => $northRim
        ];
    }

    protected function processEventPage(string $eventSourceId)
    {
        // https://portal.cityspark.com/#/details/_/{$eventSourceId}

        $response = $this->client->post('https://portal.cityspark.com/v1/event/CitySparkPortalHome', [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json; charset=UTF-8'],
            'json' => ['pid' => $eventSourceId],
        ]);
        $responseData = json_decode($response->getBody()->getContents());
        if ($responseData->Success != 1 || !isset($responseData->Value)) {
            return null;
        }
        $data = $responseData->Value;

        $event = Event::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $eventSourceId], [
            'email' => $this->getProp($data->ct, 'email'),
            'phone' => $this->getProp($data->ct, 'phone'),
            'raw_data' => json_encode($data),
        ]);

        EventDate::where('event_id', $event->id)->delete();
        foreach ($data->Occurances as $date) {
            // Skip dates in the past.
            if (strtotime($date->Start) >= time()) {
                $start = strtotime($date->Start);
                if (date('H:i:s', $start) === '00:00:00') {
                    $start = date('Y-m-d 08:00:00', $start);
                } else {
                    $start = date('Y-m-d H:i:s', $start);
                }

                $end = strtotime($date->End);
                if (!$end) {
                    $end = null;
                } else {
                    $end = date('Y-m-d H:i:s', $end);
                }
                $eventDate = new EventDate([
                    'event_id' => $event->id,
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
                $eventDate->save();
            }
        }
    }

    protected function processEvents(array $events, int $areaId = Area::KANAB)
    {
        foreach ($events as $event) {
            if (isset($event->Price)) {
                $price = $event->Price > 0 ? $event->Price : null;
                if ($event->PriceHigh > 0) {
                    $price .= ' - ' . $event->PriceHigh;
                }
            }

            foreach ($this->skippedEvents() as $skippedEvent) {
                if (stripos(strtolower($event->Name), strtolower($skippedEvent)) !== false) {
                    continue 2;
                }
            }

            $evtObj = Event::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $event->PId], [
                'name' => $event->Name,
                'description' => $event->Description,
                'free' => $event->Free,
                'price' => $price ?? null,
                'start_date' => date('Y-m-d H:i:s', strtotime($event->DateStart)),
                'end_date' => date('Y-m-d H:i:s', strtotime($event->DateEnd)),
                'raw_data' => json_encode($event),
            ]);

            $cityState = explode(',', $event->CityState);
            $locName = $this->getProp($event, 'Venue') ?? $this->getProp($event, 'Name');
            $locCity = isset($cityState[0]) ? trim($cityState[0]) : null;
            $locState = isset($event->cityState[1]) ? trim($cityState[1]) : null;
            $locAddress = $this->getProp($event, 'Address');
            $locZip = $this->getProp($event, 'Zip');
            $locPhone = $this->getProp($event, 'VenuePhone');
            $locLat = $this->getProp($event, 'latitude');
            $locLng = $this->getProp($event, 'longitude');

            $location = Location::search(['address' => $locAddress, 'zip' => $locZip]) ??
                Location::search(['address' => $locAddress, 'city' => $locCity]) ??
                Location::search(['name' => $locName, 'city' => $locCity]) ??
                Location::search(['name' => $locName, 'address' => $locAddress]);
            if ($location) {
                $evtObj->location_id = $location->id;
            } else {
                $location = new Location([
                    'name' => $locName,
                    'area_id' => $areaId,
                    'address' => $locAddress,
                    'city' => $locCity,
                    'state' => $locState,
                    'zip' => $locZip,
                    'phone' => $locPhone,
//                    'email' => $event->VenueEmail,
//                    'url' => $event->Url,
                    'latitude' => $locLat,
                    'longitude' => $locLng,
                ]);
                $location->save();
                $evtObj->location_id = $location->id;
            }
            $evtObj->save();

            $imageUrls = $this->conversionService->extractImagesFromHtml($event->Description);
            foreach ($imageUrls as $imageUrl) {
                $text = $this->conversionService->convertImageToText($imageUrl);
                if ($text) {
                    ResourceText::updateOrCreate([
                        'resource_table' => 'events',
                        'resource_id' => $evtObj->id,
                        'raw' => $text,
                    ]);
                }
            }
        }
    }
}
