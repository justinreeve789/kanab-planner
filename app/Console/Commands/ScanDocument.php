<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Event;
use App\Models\Location;
use App\Models\LocationImage;
use App\Models\ResourceText;
use App\Models\Trail;
use App\Models\TrailImage;
use App\Services\ConversionService;
use App\Services\OpenAIService;
use App\Services\ImageService;
use Illuminate\Console\Command;

class ScanDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:document {--area=} {--event=} {--location=} {--restaurant=} {--trail=} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan a document associated with an area and import it to resource_text';

    private $conversionService;
    private $imageService;
    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->conversionService = new ConversionService();
        $this->imageService = new ImageService();
        $this->openAIService= new OpenAIService();

        $areaId = (int)$this->option('area');
        $eventId = (int)$this->option('event');
        $locationId = (int)$this->option('location');
        $restaurantId = (int)$this->option('restaurant');
        $trailId = (int)$this->option('trail');

        if ($areaId + $eventId + $locationId + $restaurantId + $trailId === 0) {
            throw new \Exception('Must have a resource associated.');
        }

        $file = $this->argument('file');
        $resourceText = ResourceText::where(['source_url' => $file])->first();
        if ($resourceText) {
            $this->info('Document already imported.');
            exit(1);
        }

        if ($areaId !== 0) {
            $resourceTable = 'areas';
            $resourceId = $areaId;
        }
        if ($eventId !== 0) {
            $resourceTable = 'events';
            $resourceId = $eventId;
        }
        if ($locationId !== 0) {
            $resourceTable = 'locations';
            $resourceId = $locationId;
        }
        if ($restaurantId !== 0) {
            $resourceTable = 'restaurants';
            $resourceId = $restaurantId;
        }
        if ($trailId !== 0) {
            $resourceTable = 'trails';
            $resourceId = $trailId;
        }

        $text = $this->conversionService->convertPdfToText($file);

        $resourceText = new ResourceText([
            'resource_table' => $resourceTable,
            'resource_id' => $resourceId,
            'type' => 'document',
            'source_url' => $file,
            'raw' => $text,
        ]);
        try {
            $resourceText->save();
        } catch (\Throwable $e) {
            print_r($e);
            exit(1);
        }

        $endTime = microtime(true);
        $this->info('Document imported in ' . ($endTime - $startTime) . ' seconds.');
    }
}
