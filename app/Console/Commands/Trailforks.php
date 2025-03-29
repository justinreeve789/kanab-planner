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
    }
}
