<?php

namespace App\Models\Itinerary;

use App\Services\PhraseService;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;

    protected $guarded = [];

    public $data;
    public $activityType;   // hiking, offroading, etc.
    public $description;
    public $phrase = '';
    public $endTime;
    public $name;
    public $startTime;
    private $phraseService;

    public function __construct($resource, $activityType = null, $startTime = null, $endTime = null)
    {
        $this->phraseService = new PhraseService();

        $this->data = $resource;
        $this->name = $resource->name;
        $this->description = $resource->description;
        $this->activityType = $activityType;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->phrase = $this->phraseService->getPhraseForActivityResource($resource);
    }
}
