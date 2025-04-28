<?php

namespace App\Models;

use App\Services\TrailService;
use App\Traits\Proximity;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trail extends Resource
{
    const AVERAGE_HIKING_SPEED_MPH = 1.3;

    use HasFactory;
    use Proximity;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $hidden = ['source_type', 'source_id', 'raw_data'];

    public $trailService;

    public function __construct()
    {
        parent::__construct();
        $this->trailService = new TrailService();
    }

    public function toSearchableArray()
    {
        return parent::toSearchableArray();
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function geoJson()
    {
        return $this->hasOne(TrailGeoJson::class, 'trail_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(TrailImage::class, 'trail_id', 'id');
    }

    public function merged_trails(): HasManyThrough
    {
        return $this->hasManyThrough(Trail::class, TrailMerge::class, 'merged_from', 'id', 'id', 'merged_to');
    }

    public function openai_prompts()
    {
        return $this->hasMany(OpenAIPrompt::class, 'source_id', 'id')->where('openai_prompts.source_table', $this->getTable());
    }

    public function getAverageHours()
    {
        if (isset($this->average_hours)) {
            return $this->average_hours;
        }
        $this->average_hours = $this->trailService->calculateHikingTime($this->distance_miles, null, $this->difficulty);
        $this->save();
        return $this->average_hours;
    }

    public function getEstimatedHours(TrailService $trailService, float $averageSpeed, string $activityType = ActivityType::HIKING, $roundPrecision = 1)
    {
        $stats = $trailService->analyzeTrail($this->geoJson()->where('trail_id', $this->id)->first(), $averageSpeed, $activityType, $roundPrecision);
        return $stats['total_hours'];
    }

    public function getGeoJsonData()
    {
        $file = storage_path('geojson/' . $this->id . '-' . $this->source_id . '.geojson');
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    public function getGpxData()
    {
        $file = storage_path('gpx/' . $this->id . '-' . $this->source_id . '.gpx');
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $xml = simplexml_load_string($data);
            return json_decode(json_encode($xml));
        }
        return null;
    }

    public function isEasy()
    {
        return strpos(strtolower($this->difficulty), 'easy') !== false;
    }

    public function isIntermediate()
    {
        return strpos(strtolower($this->difficulty), 'intermediate') !== false;
    }

    public function isDifficult()
    {
        return strpos(strtolower($this->difficulty), 'difficult') !== false;
    }
}
