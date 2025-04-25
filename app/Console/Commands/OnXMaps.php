<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OnXMaps extends Command
{
    const PER_PAGE = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:onxmaps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import trail data from OnXMaps.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $doGpx = $this->option('gpx');
//        $doTrails = $this->option('trails');
//        if ($doGpx) {
//            $this->downloadGpxFiles();
//            if (!$doTrails) {
//                return;
//            }
//        }

        $this->areaUrls = [
            '/offroad/trails/us/utah/' => 'https://www.onxmaps.com/api/v2/offroad-trails?area_id=62',
        ];
        $this->trailUrls = [];
        foreach ($this->areaUrls as $prefix => $areaUrl) {
            $this->parseArea($prefix, $areaUrl);
        }
    }

    private function parseArea(string $prefix, string $areaUrl)
    {
        $page = 1;
        $trailUrls = [];
        do {
            $url = $areaUrl . '&page=' . $page . '&per_page=' . self::PER_PAGE;
            $json = json_decode(file_get_contents($url));
            $urls = [];
            if ($json) {
                foreach ($json->data as $item) {
                    $urls[] = 'https://www.onxmaps.com' . $prefix . $item->slug;
                }
            }
            $trailUrls = array_merge($trailUrls, $urls);
        } while (!empty($urls) && $page++ < 100);
        echo '<pre>'; print_r($trailUrls); echo '</pre>';
   }
}
