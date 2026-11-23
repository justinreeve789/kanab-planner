<?php

namespace App\Models;

use App\Services\TrailService;
use App\Traits\Proximity;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trail extends Resource
{
    const AVERAGE_HIKING_SPEED_MPH = 2;

    use HasFactory;
    use Proximity;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $hidden = ['source_type', 'source_id', 'raw_data'];

    public $trailService;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->trailService = new TrailService();
    }

    public function toSearchableArray()
    {
        return parent::toSearchableArray();
    }

//    public function getAppends()
//    {
//        return array_merge(parent::getAppends(), $this->appends);
//    }

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

    public function getImageIdsAttribute()
    {
        return $this->images->pluck('id')->all();
    }

    public function getAverageHours(array $params = [])
    {
        if (isset($this->average_hours)) {
            return $this->average_hours;
        }
        return $this->getEstimatedHours($this->trailService, $params['average_hiking_speed'] ?? self::AVERAGE_HIKING_SPEED_MPH);
    }

    public function getPeriodsToStart(TrailService $trailService, string $activityType = ActivityType::HIKING)
    {
    }

    public function getEstimatedHours(TrailService $trailService, float $averageSpeed, string $activityType = ActivityType::HIKING, $roundPrecision = 1)
    {
        $geoJson = $this->geoJson()->first();
        if (!$geoJson) {
            return self::DEFAULT_AVERAGE_HOURS;
        }
        try {
            $stats = $trailService->analyzeTrail($geoJson->geojson, $averageSpeed, $activityType, $roundPrecision);
        } catch (\InvalidArgumentException $e) {
            return self::DEFAULT_AVERAGE_HOURS;
        }
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

    public static function getFeatured(array $areaIds, int $featuredCount = 3)
    {
        $index = 0;
        $resources = self::whereHas('images')->where('description', '!=', '')->where('unique', 1)->whereIn('area_id', $areaIds)->inRandomOrder(date('YmdH'))->take($featuredCount)->get();
        $resourceIds = [];
        foreach ($resources as $resource) {
            $resourceIds[] = $resource->id;
            $index++;
        }
        while ($index++ < $featuredCount) {
            $resource = self::whereHas('images')->where('description', '!=', '')->whereIn('area_id', $areaIds)->whereNotIn('id', $resourceIds)->inRandomOrder(date('YmdH'))->first();
            if ($resource) {
                $resources[] = $resource;
                $resourceIds[] = $resource->id;
            }
        }
        return $resources;
    }
}
