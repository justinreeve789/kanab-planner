<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Trailforks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:trailforks {--gpx} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import trails from trailforks.com';

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
    }
}
