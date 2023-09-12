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
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;

class HikingProject extends Command
{
    const SOURCE_TYPE = 'hikingproject';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:hikingproject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import trails from hikingproject.com';

    private $areaUrls;
    private $browser;
    private $conversionService;
    private $trailUrls;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->conversionService = new ConversionService();

        $parks = [
            'brca' => ['name' => 'Bryce Canyon National Park', 'area_id' => Area::BRYCE],
            'cebr' => ['name' => 'Cedar Breaks National Monument', 'area_id' => Area::ZION],
            'glca' => ['name' => 'Glen Canyon Recreation Area', 'area_id' => Area::LAKE_POWELL],
            'grca' => ['name' => 'Grand Canyon National Park', 'area_id' => Area::NORTH_RIM],
            'rabr' => ['name' => 'Rainbow Bridge National Monument', 'area_id' => Area::LAKE_POWELL],
            'zion' => ['name' => 'Zion National Park', 'area_id' => Area::ZION]
        ];

        $this->areaUrls = [
            'https://www.hikingproject.com/directory/8015386/beaver',
            'https://www.hikingproject.com/directory/8010573/brian-head-resort',
            'https://www.hikingproject.com/directory/8010545/bryce-canyon-national-park',
            'https://www.hikingproject.com/directory/8019058/canaan-mountain-wilderness-study-area',
            'https://www.hikingproject.com/directory/8016909/cannonville',
            'https://www.hikingproject.com/directory/8012827/cedar-breaks-national-monument',
            'https://www.hikingproject.com/directory/8010542/cedar-city',
            'https://www.hikingproject.com/directory/8013435/dixie-national-forest',
            'https://www.hikingproject.com/directory/8013360/enoch',
            'https://www.hikingproject.com/directory/8014261/escalante',
            'https://www.hikingproject.com/directory/8010547/grand-staircase-escalante-national-monument',
            'https://www.hikingproject.com/directory/8012285/hurricane-and-la-verkin',
            'https://www.hikingproject.com/directory/8018421/junction',
            'https://www.hikingproject.com/directory/8019059/kanab',
            'https://www.hikingproject.com/directory/8025122/kanarraville',
            'https://www.hikingproject.com/directory/8013634/loa',
            'https://www.hikingproject.com/directory/8020067/paria-canyon-vermilion-cliffs-wilderness-area',
            'https://www.hikingproject.com/directory/8011584/red-cliffs-national-conservation-area-and-desert-reserve',
            'https://www.hikingproject.com/directory/8022977/red-mountain-wilderness-study-area',
            'https://www.hikingproject.com/directory/8015770/rockville',
            'https://www.hikingproject.com/directory/8010543/st-george',
            'https://www.hikingproject.com/directory/8025601/upper-zion-trail-system',
            'https://www.hikingproject.com/directory/8012304/virgin',
            'https://www.hikingproject.com/directory/8010544/zion-national-park',
        ];
        $this->trailUrls = [];
        $this->conversionService->deleteFiles(storage_path('temp/trailurls.txt'));
        if (empty($this->areaUrls)) {
            $this->parseHome();
        }

        $this->initBrowser();
        foreach ($this->areaUrls as $areaUrl) {
            $areaId = $this->getAreaIdFromUrl($areaUrl);
            $this->trailUrls[$areaId] = $this->getTrailUrlsForArea($areaId);
        }

        file_put_contents(storage_path('temp/hikingproject.json'), json_encode($this->trailUrls));

        die;

        foreach ($trailUrls as $trailUrl) {
            file_put_contents(storage_path('temp/trailurls.txt'), $trailUrl . '
', FILE_APPEND);
        }

        $this->browser->close();
    }

    private function initBrowser()
    {
        $this->browser = (new Puppeteer)->launch([
            'args' => ['--no-sandbox', '--disable-setuid-sandbox'],
            'headless' => 'new',
        ]);
        $this->browser->page = $this->browser->newPage();
    }

    private function page()
    {
        if (!isset($this->browser->page)) {
            $this->browser->page = $this->browser->newPage();
        }
        return $this->browser->page;
    }

//    private function parseHome()
//    {
//        $this->page()->goto('https://www.hikingproject.com/directory/8010541/southwestern-utah');
//        $areas = $this->page()->querySelectorAll('#subareas .area a');
//        $this->areaLinks = [];
//        foreach ($areas as $area) {
//            $attribute = JSFunction::createWithParameters(['element' => $area])->body('return element.getAttribute("href");');
//            $this->areaLinks[] = ($area->evaluate($attribute));
//        }
//    }

    private function getAreaIdFromUrl($areaUrl) {
        $parts = explode('/', $areaUrl);
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                return $part;
            }
        }
    }

    private function getTrailUrlsForArea($areaId) {
        $trailUrls = [];
        $page = -3; // For some reason, the first 10 trails are only retrieved if we start at -3.
        do {
            $url = 'https://www.hikingproject.com/ajax/area/' . $areaId . '/trails?idx=' . $page;
            $json = json_decode(file_get_contents($url));
            $urls = [];
            if ($json) {
                if ($json->success === true) {
                    $urls = $this->conversionService->extractUrlsFromHtml($json->markup);
                    if (!empty($urls)) {
                        $trailUrls = array_merge($trailUrls, $urls);
                    }
                }
            }
        } while (!empty($urls) && $page++ < 100);
        return array_values(array_unique($trailUrls));
    }

    private function parseTrailPage($trailUrl) {
        $this->page()->goto($trailUrl);
        $name = $this->page()->querySelectorEval('#trail-title', JSFunction::createWithParameters(['element'])->body('return body.textContent;'));
        echo $name;
        die;
    }

    private function parseAreaPage($areaUrl)
    {
        $this->page()->goto($areaUrl);
        $loadMore = $this->page()->querySelector('body');
        $lm = $loadMore->evaluate(
            JSFunction::createWithParameters(['element' => $loadMore])->body('return element.innerHTML;')
        );
        $trails = $this->page()->querySelectorAll('#trail-detail-cards-container a');
        foreach ($trails as $trail) {
            $trailUrl = $trail->evaluate(
                JSFunction::createWithParameters(['element' => $trail])->body('return element.getAttribute("href");')
            );
            echo $trailUrl . '
';
            $this->trailUrls[] = $trailUrl;
            file_put_contents(storage_path('temp/trailurls.txt'), $trailUrl . '
', FILE_APPEND);
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
            $itemPetsAllowed = (bool) $this->getProp($item, 'arePetsPermitted');
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
}
