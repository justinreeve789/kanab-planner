<?php

namespace App\Console\Commands;

use App\Jobs\ImportCitySparkJob;
use App\Services\CitySparkService;
use Illuminate\Console\Command;

class CitySpark extends Command
{
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        ImportCitySparkJob::dispatch();
        $citySparkService = new CitySparkService();
        $citySparkService->import();
    }
}
