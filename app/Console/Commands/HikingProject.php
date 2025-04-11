<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Scopes\UsableScope;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Services\ConversionService;
use App\Services\TrailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use IvoPetkov\HTML5DOMDocument;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Symfony\Component\Panther\Client;

class HikingProject extends Command
{
    const COOKIE = 'GCLB=CMWrhfG39LvNOg; photosTitle=Nearby%20Photos; emailSentTo=eyJpdiI6IjJzTTcvWW8xcUhvUEZ0S2hiTVI1enc9PSIsInZhbHVlIjoiM2w3RHJsZ2VEaU56azlhN3A5NGxHcTZpSHYrSHJ3UkJnalNlVjBSOGVxL081MFovaGhHWHRoaklCQThhUzc3M3NRYkJGcmZSMk9vK0l5anIzUW1GS2c9PSIsIm1hYyI6ImM1ZDlkM2ZhYmNlNjY0MjAyZjcyYWU3N2M0NjIxMjU4MmE5NjU2MGUwMjU4MzRiZDRlMTcwNzQ5OTBjNjlkNTUiLCJ0YWciOiIifQ%3D%3D; user=eyJpdiI6IjU2ZDRoTUlPU3ZhZGQveTJtQUEzdGc9PSIsInZhbHVlIjoiZDdNb3VwRUN0bTQ0Rk9CNUNiSWVDdDRhdTVXYXRENS9mWnJYaG9nR2JEeWZCUDREMzhUNzIvcmRkd2hjaXVzQlZZai80QktXYS9xNWFEcUpUdlVFaVdwM01mdTNuNjErRUM0eThIbjdDeUdKbDQ1amdTaEZmRGtyOTdjYmtheXlwcXFZdjI5N0dzSnU4eldiU1R5Vzd3WDlXeW9FbjdFNDRqNmphZnUzVGFRPSIsIm1hYyI6ImI5N2EyNjM2ZTgyNzAwYjQzODEyZjg1Mjk0ZjFjMTkzZmEzNWUzNmEwZGZhMmVjMjJmZWQ3NzcxZDBiMDJiZTAiLCJ0YWciOiIifQ%3D%3D; ids=eyJpdiI6IkVyWFowUWxQVDIrVS9yMWFLY1lFVXc9PSIsInZhbHVlIjoiOC85akZyMXpCZlhraU11WG1VcnRlWSt1ZlhSOHk2eGFlWnV4SGpkUFN6eTVhS3hNdlpTN1hBc3hsQmRnUUg3THh3OThhY2FCN2NaWi9LY2lGYjZrQUE9PSIsIm1hYyI6ImMwZGJjNTQ5YTk5ZWNiZmU3ZDg5ZjcwMTZkNGU3NGNmZmVhMTQ2Njg5OGQ1NDlmZjQyMjkyNGIwNzk3OTc0ZDEiLCJ0YWciOiIifQ%3D%3D; photoIds=eyJpdiI6InQzZXVDSGRvZGs2MEQrVS9GUlBNV0E9PSIsInZhbHVlIjoiWkEzam1vWElia0k0dm5TM0dIdFlUK2dtNURaSzRVNFNWdlF3TWtubVRrNVphcmVJaUplanRGaG5SeFhwblFhRmxYVGFVdEFjN0Q2OHFBVXZ5RlducEVteFhEcXB6MWpzZjFiZ05RZFowa1FEbkx6VFRRQkVVcEJKRWNFcU1NNDhwSEZQNEEzOFFyb2dBN0RwdHk0QXNrRnNXbHJpbFRNWFVBODgvOWVUUElhcUYvVXVCV2Q2czNNem5DMVltemNUIiwibWFjIjoiZTgzMDhjMGQ1YmNjZWI0OWVjZDBmZGQ5NDE3MGY3YmI3MDViZmE5ZDE2ZWUwMzRjZTIwMjhiZTllYjkwZTE4OCIsInRhZyI6IiJ9; prefs=%5BcommentSort%7Coldest%5D%5BmapX%7C-11918277%5D%5BmapY%7C4707011%5D%5BmapZoom%7C12.4%5D; XSRF-TOKEN=eyJpdiI6ImlpUCtRZDF0dFpTbmp1V2NCNEZQMkE9PSIsInZhbHVlIjoiT1hYbDNiaVZTWkdheEhBUStTWUpMdjRLdnBmWndZc0RtdDdLK3NKcE5IVkVVc1I5ejh6SXBBcWtuUzlQdjhYdVorMGw3dDZjdzZvQlhMbzgyZlY4c3JhK000YnpUOW9pMFpydVc4b2lKY2hCbU5xeDBGaU5WcXJTVDAzTkV3UFYiLCJtYWMiOiJlMmZhZGMxMTJlNTA5ZTMxYzcwMDliNTgyZGQ3ZDU3ZTBiMTI4OTQyZGM4MDNiYjBhNGJkZmZkZWYxNDc5MzY0IiwidGFnIjoiIn0%3D; ap_session=eyJpdiI6ImE1d1krbjBIQlo1MVRvM3N4bVc2aFE9PSIsInZhbHVlIjoibTVjNWFvcmp4TmhHZWhjb1JLUTBSWlU2b0htaDExWk8rVVZKUS9VQ2x4UEVJWnhwalZ3RnhvNFdFTE90aThTaHo5cEZub0FhYzFMNEtDeHhDWHhkYk16blI3R3ZvVUtSekh1VlZIQzcwb0orUmYweml6c1BaRDNqdE9LalBkZHYiLCJtYWMiOiI4ZjIwZWEwYzFhOGUxMjMzYzUyZWM5NGYzMzA0YjMwMzFkODdkOTBjNTcxYjJlZTFkZTg5ZWFlMTE2YjFmYTE5IiwidGFnIjoiIn0%3D; pageCount=eyJpdiI6Ik1QQ3I2dzVHaXpnR1lwZm9JN0dsYnc9PSIsInZhbHVlIjoieko2Z0FlUW9OczF2aWM0WnVvZXl1L0k4RXVGcGpJUWRLTG8vaWxtNHRuai8rY3AyMTJnbkIxVFZLTWcvTGt4SCIsIm1hYyI6ImE3ZTExNTlhNzIzODA4ZTQ3M2JkMWU3ZTYyODU4YzU3NmY0ZTQ2YjE2OWU5OWJiODI5NjljNzUzMTk2NjlhMzUiLCJ0YWciOiIifQ%3D%3D';
    const SOURCE_TYPE = 'hikingproject';
    const TOGEOJSON_PATH = 'node_modules/@mapbox/togeojson/togeojson';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:hikingproject {--gpx} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import trails from hikingproject.com';

    private $areaUrls;
    private $browser;
    private $conversionService;
    private $trailService;
    private $trailUrls;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doGpx = $this->option('gpx');
        $doTrails = $this->option('trails');
        if ($doGpx) {
            $this->downloadGpxFiles();
            if (!$doTrails) {
                return;
            }
        }

        $this->conversionService = new ConversionService();
        $this->trailService = new TrailService();

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
            'https://www.hikingproject.com/directory/8013242/north-rim',
            'https://www.hikingproject.com/directory/8011899/glen-canyon-national-recreation-area',
            'https://www.hikingproject.com/directory/8010574/glen-canyon-national-recreation-area',
        ];
        $this->trailUrls = [];
        if (empty($this->areaUrls)) {
            $this->parseHome();
        }

        $jsonFile = storage_path('temp/hikingproject.json');

        // Create the JSON file if it doesn't exist.
        if (!file_exists($jsonFile)) {
            foreach ($this->areaUrls as $areaUrl) {
                $areaId = $this->getAreaIdFromUrl($areaUrl);
                $this->trailUrls[$areaId] = $this->getTrailUrlsForArea($areaId);
            }
            file_put_contents($jsonFile, json_encode($this->trailUrls));
        }

        $json = json_decode(file_get_contents($jsonFile));
        foreach ($json as $areaId => $trailUrls) {
            foreach ($trailUrls as $trailUrl) {
                $this->parseTrailPage($trailUrl, $areaId);
            }
        }
    }

    public function downloadGpxFiles()
    {
        $trails = Trail::where('source_type', self::SOURCE_TYPE)->whereNotNull('gpx_url')->get();
        $client = Client::createChromeClient(base_path("drivers/chromedriver"), [
            "--no-sandbox",
            "--headless",
            "--disable-gpu",
            "--disable-dev-shm-usage",
        ]);

        foreach ($trails as $trail) {
            if (file_exists(storage_path('gpx/' . $trail->id . '-' . $trail->source_id . '.gpx'))) {
                continue;
            }
            $url = 'https://www.hikingproject.com/trail/' . $trail->source_id;
            $crawler = $client->request('GET', $url);
            $client->waitFor('#user');
            if (trim($crawler->filter('#user')->text() == 'Sign In')) {
                $client->clickLink('Sign In');
                $client->waitForVisibility('.login-signup-block');
                $crawler->filter('.login-signup-block input[type="email"]')->sendKeys(getenv('HIKING_PROJECT_USERNAME'));
                $crawler->filter('.login-signup-block input[type="password"]')->sendKeys(getenv('HIKING_PROJECT_PASSWORD'));
                $client->executeScript("document.querySelector('.login-signup-block button').click()");
                $crawler = $client->request('GET', $url);
                $crawler = $client->refreshCrawler();
            }
            $client->waitFor('#user');
            $client->waitFor('#toolbox');
            $client->executeScript("document.querySelector('#toolbox a.mt-threequarter.btn.btn-xs.btn-secondary.width100.require-user').click()");
            $this->moveGpxFile(storage_path('gpx/' . $trail->id . '-' . $trail->source_id . '.gpx'));
        }
    }

    private function moveGpxFile($toFile)
    {
        // Wait for the download to complete (check for file presence)
        $timeout = 15;
        $startTime = time();

        while (time() - $startTime < $timeout) {
            // Check if the file exists in the download directory
            $matchingFiles = glob(Config::get('services.chromedriver.downloads_path') . '/*.gpx');
            if ($matchingFiles !== false) {
                foreach($matchingFiles as $index => $fromFile) {
                    if (count($matchingFiles) > 1) {
                        $parts = pathinfo($fromFile);
                        $toFile .= basename($parts['dirname'] . '/' . $parts['filename'] . '-' . $index . '.' . $parts['extension']);
                    }
                    if (!rename($fromFile, $toFile)) {
                        echo 'Could not move file ' . $toFile . '
';
                    } else {
                        echo $toFile . '
';
                        return;
                    }
                }
            }

            // Sleep for a short period to avoid high CPU usage
            sleep(1);
        }
    }

    private function fixSymbols($str): string
    {
        $str = str_replace("html5-dom-document-internal-entity2-39-end", "'", $str);
        $str = str_replace("html5-dom-document-internal-entity1-deg-end", "&deg;", $str);
        $str = str_replace("html5-dom-document-internal-entity1-middot-end", "/", $str);
        return $str;
    }

    private function parseTrailPage($trailUrl, $areaId)
    {
        echo 'Processing ' . $trailUrl . '
';
        $trailHtml = file_get_contents($trailUrl);
        $dom = new HTML5DOMDocument();
        $dom->loadHTML($trailHtml, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
        $name = trim($dom->querySelector('#trail-title')->textContent);
        $name = str_replace("html5-dom-document-internal-entity2-039-end", "'", $name);
        $difficulty = trim($dom->querySelector('.difficulty-text')->textContent);
        $distanceMiles = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(0)->querySelector('.imperial')->textContent);
        $distanceType = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(0)->querySelectorAll('h3')->item(2)->textContent);
        $elevationHigh = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(1)->querySelectorAll('h3 .imperial')->item(0)->textContent);
        $elevationLow = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(1)->querySelectorAll('h3 .imperial')->item(1)->textContent);
        $incline = html_entity_decode(trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(2)->querySelectorAll('h3 .imperial')->item(0)->textContent));
        $decline = html_entity_decode(trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(2)->querySelectorAll('h3 .imperial')->item(1)->textContent));
        // $avgGrade = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(3)->querySelectorAll('h3')->item(0)->textContent);
        // $maxGrade = trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(3)->querySelectorAll('h3')->item(1)->textContent);
        $grade = html_entity_decode(trim($dom->querySelectorAll('#trail-stats-bar .stat-block')->item(3)->textContent));
        $gpxUrl = trim($dom->querySelector('#toolbox a.mt-threequarter.require-user')->getAttribute('href'));
        try {
            $pets = trim($dom->querySelectorAll('#trail-text .mb-1')->item(1)->querySelectorAll('h3 span')->item(0)->textContent);
            $features = trim($dom->querySelectorAll('#trail-text .mb-1')->item(1)->querySelectorAll('h3 span')->item(1)->textContent);
            $description = trim($dom->querySelectorAll('#trail-text .mb-1')->item(2)->textContent);
            $contactName = trim($dom->querySelector('#trail-text .mb-1 .trail-contacts a')->getAttribute('href'));
            $contactUrl = trim($dom->querySelector('#trail-text .mb-1 .trail-contacts a')->textContent);
        } catch (\Throwable $e) {
            $pets = $features = $description = $contactName = $contactUrl = null;
        }
        $json = $dom->querySelectorAll('script[type="application/ld+json"]')->item(1)->textContent;
        $json = str_replace('<![CDATA[-html5-dom-document-internal-cdata', '', $json);
        $json = str_replace('-html5-dom-document-internal-cdata]]>', '', $json);
        $json = json_decode(trim($json));
        if (isset($json->description)) {
            $descriptionAlt = trim($json->description);
        }
        if (isset($json->geo)) {
            $latitude = trim($json->geo->latitude);
            $longitude = trim($json->geo->longitude);
            $areaId = Area::getAreaForLatLng($latitude, $longitude)->id;
        }

        $distanceMiles = str_replace(' Miles', '', $distanceMiles);
        $features = $this->fixSymbols($features);
        $elevationHigh = $this->fixSymbols($elevationHigh);
        $elevationLow = $this->fixSymbols($elevationLow);
        $incline = $this->fixSymbols($incline);
        $decline = $this->fixSymbols($decline);
        $avgGrade = null;
        $maxGrade = null;
        $elevationHigh = preg_replace("/[^0-9.]/", "", $elevationHigh);
        $elevationLow = preg_replace("/[^0-9.]/", "", $elevationLow);
        $incline = preg_replace("/[^0-9.]/", "", $incline);
        $decline = preg_replace("/[^0-9.]/", "", $decline);
        $gradeParts = explode(' ', $grade);
        for ($g = 0; $g < count($gradeParts); $g++) {
            if ($gradeParts[$g] == 'Avg') {
                $avgGrade = floatval($gradeParts[$g - 1]) / 100.00;
            }
            if ($gradeParts[$g] == 'Max') {
                $maxGrade = floatval($gradeParts[$g - 1]) / 100.00;
            }
        }

        $imageUrls = [];
        try {
            $images = $dom->querySelector('#photo-carousel')->innerHTML;
            $urls = $this->conversionService->extractUrlsFromHtml($images);
            foreach ($urls as $url) {
                if (strpos($url, 'hikingproject.com/assets') !== false) {
                    $imageUrls[] = $url;
                }
            }
        } catch (\Exception $e) {}

        $sourceId = $this->getTrailIdFromUrl($trailUrl);
        try {
            $trail = Trail::withoutGlobalScope(UsableScope::class)->updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $sourceId], [
                'name' => $name,
                'name_alt' => $areaId,
                'area_id' => $areaId ?? 0,
                'description' => $description,
                'description_alt' => $descriptionAlt ?? null,
                'directions' => null,
                'accessibility' => null,
                'duration' => null,
                'pets_allowed' => $pets,
                'latitude' => $latitude ?? null,
                'longitude' => $longitude ?? null,
                'contact_url' => $contactUrl,
                'contact_name' => $contactName,
                'season ' => null,
                'reservation' => null,
                'fee' => null,
                'gpx_url' => $gpxUrl,
                'features' => $features,
                'difficulty' => $difficulty,
                'distance_miles' => $distanceMiles,
                'distance_type' => $distanceType,
                'elevation_high_ft' => $elevationHigh,
                'elevation_low_ft' => $elevationLow,
                'incline_ft' => $incline,
                'decline_ft' => $decline,
                'average_grade_pt' => $avgGrade,
                'max_grade_pt' => $maxGrade,
                'average_hours' => $this->trailService->calculateHikingTime($distanceMiles, null, $difficulty),
                'raw_data' => $areaId . ' | ' . $trailUrl,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $trail = Trail::withoutGlobalScope(UsableScope::class)->updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $sourceId], [
                'name' => $name,
                'name_alt' => $areaId,
                'area_id' => 0,
                'distance_miles' => $distanceMiles,
                'distance_type' => $distanceType,
                'elevation_high_ft' => $elevationHigh,
                'elevation_low_ft' => $elevationLow,
                'incline_ft' => $incline,
                'decline_ft' => $decline,
                'grade' => $grade,
                'average_grade_pt' => $avgGrade,
                'max_grade_pt' => $maxGrade,
                'raw_data' => $areaId . ' | ' . $trailUrl,
            ]);
        }

        foreach ($imageUrls as $imageUrl) {
            TrailImage::updateOrCreate(['trail_id' => $trail->id, 'url' => $imageUrl]);
        }
    }

    private function initBrowser()
    {
        $this->browser = (new Puppeteer)->launch([
            'args' => ['--no-sandbox', '--disable-setuid-sandbox'],
            'headless' => 'new',
        ]);
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

    public function getTrailIdFromUrl($trailUrl) {
        $parts = explode('/', $trailUrl);
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

            $trail = Trail::withoutGlobalScope(UsableScope::class)->updateOrCreate(['source_type' => self::SOURCE_TYPE, 'source_id' => $item->id], [
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
