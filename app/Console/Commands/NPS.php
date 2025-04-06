<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\Image;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\LocationTag;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\TrailTag;
use App\Services\ConversionService;
use App\Services\NPSService;
use Illuminate\Console\Command;

class NPS extends Command
{
    const SOURCE_TYPE = 'nps';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:nps {--events} {--restaurants} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import events for nps.gov';

    private $conversionService;
    private $npsService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->options('trails');

        $this->conversionService = new ConversionService();
        $this->npsService = new NPSService();

        $parks = [
            'brca' => ['name' => 'Bryce Canyon National Park', 'area_id' => Area::BRYCE],
            'cebr' => ['name' => 'Cedar Breaks National Monument', 'area_id' => Area::ZION],
            'glca' => ['name' => 'Glen Canyon Recreation Area', 'area_id' => Area::LAKE_POWELL],
            'grca' => ['name' => 'Grand Canyon National Park', 'area_id' => Area::NORTH_RIM],
            'rabr' => ['name' => 'Rainbow Bridge National Monument', 'area_id' => Area::LAKE_POWELL],
            'zion' => ['name' => 'Zion National Park', 'area_id' => Area::ZION]
        ];

        if ($doEvents !== false) {
            foreach ($parks as $parkCode => $parkData) {
                $events = $this->npsService->getEvents(['parkCode' => $parkCode]);
                $this->processEvents($events, $parkData);
            }
        }

        if ($doTrails !== false) {
            foreach ($parks as $parkCode => $parkData) {
                $trails = $this->npsService->getTrails(['parkCode' => $parkCode]);
                $this->processTrails($trails, $parkData);
            }
        }
    }

    private function getProp($data, $prop)
    {
        return isset($data->{$prop}) && $data->{$prop} != '' ? $data->{$prop} : null;
    }

    protected function processTrails(array $trails, array $parkData)
    {
        foreach ($trails as $item) {
            $itemName = $this->getProp($item, 'title');
            $itemNameAlt = $this->getProp($item, 'location');
            $itemDescription = trim(html_entity_decode(strip_tags($this->getProp($item, 'locationDescription'))));
            $itemDescription .= '\r\n\r\n' . $this->getProp($item, 'activityDescription');
            $itemDescriptionAlt = trim(html_entity_decode(strip_tags($this->getProp($item, 'longDescription'))));
            $itemAccessibility = trim(html_entity_decode(strip_tags($this->getProp($item, 'accessibilityInformation'))));
            $itemDirections = $this->getProp($item, 'directions');
            $itemDuration = $this->getProp($item, 'duration');
            $itemPetsAllowed = $this->getProp($item, 'arePetsPermitted') ? true : false;
            $itemLat = $this->getProp($item, 'latitude');
            $itemLng = $this->getProp($item, 'longitude');
            $itemUrl = $this->getProp($item, 'url');
            $itemSeason = $this->getProp($item, 'seasonDescription');
            $itemReservation = $this->getProp($item, 'isReservationRequired') ? true : false;
            $itemFee = $this->getProp($item, 'doFeesApply') ? true : false;

            $trail = Trail::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $item->id], [
                'name' => $itemName,
                'name_alt' => $itemNameAlt,
                'area_id' => $parkData['area_id'],
                'description' => $itemDescription,
                'description_alt' => $itemDescriptionAlt,
                'directions' => $itemDirections,
                'accessibility' => $itemAccessibility,
                'duration' => $itemDuration,
                'pets_allowed' => $itemPetsAllowed,
                'latitude' => $itemLat,
                'longitude' => $itemLng,
                'url' => $itemUrl,
                'season '=> $itemSeason,
                'reservation' => $itemReservation,
                'fee' => $itemFee,
                'raw_data' => json_encode($item),
            ]);
        }
    }

    protected function processEvents(array $events, array $parkData)
    {
        foreach ($events as $evt) {
            // Skip past events.
//            if (max(isset($event->acf->end_date) ? strtotime($event->acf->end_date) : 0, strtotime($event->acf->start_datetime)) < time()) {
//                continue;
//            }

            $evtName = $this->getProp($evt, 'title');
            $evtDescription = html_entity_decode(trim(strip_tags($this->getProp($evt, 'description'))));
            $evtDescription .= '\r\n\r\n' . $evt->location;
            $evtEmail = $this->getProp($evt, 'contactemailaddress');
            $evtPhone = $this->getProp($evt, 'phone');
            $evtUrl = $this->getProp($evt, 'infourl');
            $evtLat = $this->getProp($evt, 'latitude');
            $evtLng = $this->getProp($evt, 'longitude');
            $evtStartDate = $this->getProp($evt, 'datestart');
            $evtEndDate = $this->getProp($evt, 'dateend');
            $evtStartTime = !empty($evt->times) ? $evt->times[0]->timestart : null;
            $evtEndTime = !empty($evt->times) ? $evt->times[0]->timeend : null;

            $evtObj = Event::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $evt->id], [
                'name' => $evtName,
                'description' => $evtDescription,
                'email' => $evtEmail,
                'phone' => $evtPhone,
                'url' => $evtUrl,
                'latitude' => $evtLat,
                'longitude' => $evtLng,
                'start_date' => date('Y-m-d H:i:s', strtotime($evtStartDate)),
                'end_date' => date('Y-m-d H:i:s', strtotime($evtEndDate)),
                'raw_data' => json_encode($evt),
            ]);

            $location = \App\Services\LocationService::search(['name' => $parkData['name']]);
            if ($location) {
                $evtObj->location_id = $location->id;
            } else {
                $location = new Location([
                    'name' => $parkData['name'],
                    'area_id' => $parkData['area_id'],
                ]);
                $location->save();
                $evtObj->location_id = $location->id;
            }
            $evtObj->save();

//            $imageUrls = $this->conversionService->extractImagesFromHtml($event->acf->information_block);
//            foreach ($imageUrls as $imageUrl) {
//                $text = $this->ocrService->scanImageUrl($imageUrl);
//                if ($text) {
//                    ResourceText::updateOrCreate([
//                        'resource_table' => 'events',
//                        'resource_id' => $evtObj->id,
//                        'raw' => $text,
//                    ]);
//                }
//            }
//
//            $pdfUrls = $this->conversionService->extractPdfsFromHtml($event->acf->information_block);
//            foreach ($pdfUrls as $pdfUrl) {
//                echo $pdfUrl . ' ';
//                $text = $this->conversionService->extractTextFromPdfUrl($pdfUrl);
//                if ($text) {
//                    ResourceText::updateOrCreate([
//                        'resource_table' => 'events',
//                        'resource_id' => $evtObj->id,
//                        'raw' => $text,
//                    ]);
//                }
//            }
        }
    }
}
