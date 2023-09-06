<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\LocationTag;
use Illuminate\Console\Command;

class VisitSouthernUtah extends Command
{
    const SOURCE_TYPE = 'vsu';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vsu {--events} {--restaurants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import events for visitsouthernutah.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');

        if ($doEvents !== false) {
            $json = file_get_contents('https://visitsouthernutah.com/wp-json/wp/v2/events?per_page=100&page=1');
            $events = json_decode($json);
            $this->processEvents($events);
        }

        if ($doRestaurants !== false) {
            $json = file_get_contents('https://visitsouthernutah.com/wp-json/dininglodging/v1/data?pagenum=1&listingtype=restaurants');
            $restaurants = json_decode($json);
            $this->processRestaurants($restaurants->listings->listing);
        }
    }

    private function getProp($data, $prop)
    {
        return isset($data->{$prop}) ? $data->{$prop} : null;
    }

    protected function processTrails(array $trails)
    {
    }

    protected function processRestaurants(array $restaurants)
    {
        foreach ($restaurants as $rst) {
            $locName = $this->getProp($rst, 'company');
            $locDescription = $this->getProp($rst, 'description');
            $locAddress = $this->getProp($rst, 'addr1');
            $locCity = $this->getProp($rst, 'city');
            $locState = $this->getProp($rst, 'state');
            $locZip = $this->getProp($rst, 'zip');
            $locEmail = $this->getProp($rst, 'email');
            $locPhone = $this->getProp($rst, 'phone');
            $locUrl = $this->getProp($rst, 'weburl');
            $locLat = $this->getProp($rst, 'latitude');
            $locLng = $this->getProp($rst, 'longitude');

            $location = Location::search(['name' => $locName, 'address' => $locAddress]) ??
                Location::search(['address' => $locAddress, 'city' => $locCity]) ??
                Location::search(['email' => $locEmail]) ??
                Location::search(['phone' => $locPhone]) ??
                Location::search(['url' => $locUrl]);
            if (!$location) {
                $location = new Location([
                    'name' => $locName,
                    'description' => $locDescription,
                    'area_id' => Area::KANAB,
                    'address' => $locAddress,
                    'city' => $locCity,
                    'state' => $locState,
                    'zip' => $locZip,
                    'phone' => $locPhone,
                    'email' => $locEmail,
                    'url' => $locUrl,
                    'latitude' => $locLat,
                    'longitude' => $locLng,
                ]);
                $location->save();
            }

            if ($rst->imgpath) {
                LocationImage::updateOrCreate(['location_id' => $location->id, 'url' => $rst->imgpath]);
            }

            if ($rst->subcatname) {
                $locationTag = new LocationTag([
                    'location_id' => $location->id,
                    'tag' => $rst->subcatname,
                ]);
                $locationTag->save();
            }
        }
    }

    protected function processEvents(array $events)
    {
        foreach ($events as $event) {
            // Skip past events.
            if (max(isset($event->acf->end_date) ? strtotime($event->acf->end_date) : 0, strtotime($event->acf->start_datetime)) < time()) {
                continue;
            }

            // Skip events without a location.
            if (!isset($event->acf->location_name) || trim($event->acf->location_name) == '') {
                continue;
            }

            echo $event->title->rendered . ' | ';

            $evtObj = Event::updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $event->id], [
                'name' => $event->title->rendered,
                'description' => trim(html_entity_decode(htmlspecialchars(strip_tags($event->acf->information_block)))),
                'start_date' => date('Y-m-d', strtotime($event->acf->start_datetime)),
                'start_time' => date('H:i', strtotime($event->acf->start_datetime)),
                'raw_data' => json_encode($event),
            ]);

            if (isset($event->acf->end_date)) {
                $evtObj->end_date = date('Y-m-d', strtotime($event->acf->end_date));
                $evtObj->end_time = date('H:i', strtotime($event->acf->end_date));
            }

            $locName = isset($event->acf->location_name) ? $event->acf->location_name : null;
            $locAddress = isset($event->acf->address) ? $event->acf->address : null;

            $location = Location::search(['name' => $locName, 'address' => $locAddress]);
            if ($location) {
                $evtObj->location_id = $location->id;
            } else {
                $location = new Location([
                    'name' => $locName,
                    'area_id' => Area::KANAB,
                    'address' => $locAddress,
//                    'city' => $locCity,
//                    'state' => $locState,
//                    'zip' => $locZip,
//                    'phone' => $event->acf->contact_number,
//                    'email' => $event->VenueEmail,
//                    'url' => $event->Url,
//                    'latitude' => $event->latitude,
//                    'longitude' => $event->longitude,
                ]);
                $location->save();
                $evtObj->location_id = $location->id;
            }
            $evtObj->save();
        }
    }
}
