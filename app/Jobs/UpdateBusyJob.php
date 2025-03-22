<?php

namespace App\Jobs;

use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBusyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, QueueAble, SerializesModels;

    private $locationService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->locationService = new LocationService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $locations = Location::where('source_type', 'googleplaces')->where('is_usable', 1)->get();
        foreach ($locations as $location) {
            $this->locationService->updateBusynessForLocation($location);
        }
    }
}
