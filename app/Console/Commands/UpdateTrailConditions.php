<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateTrailConditions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:conditions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update trail conditions from various sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Trailforks has user-submitted data on trail conditions, e.g. "Recent Trail Reports": https://www.trailforks.com/region/zion-national-park-28210/?activitytype=6
    }
}
