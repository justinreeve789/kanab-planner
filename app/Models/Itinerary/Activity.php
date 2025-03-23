<?php

namespace App\Models\Itinerary;

use App\Models\ActivityType;
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
    protected $table = 'itinerary_activities';

    public $hash;
    public $data;
    public $activityType;   // hiking, offroading, etc.
    public $description;
    public $phrase = '';
    public $end_time;
    public $name;
    public $start_time;
    private $phraseService;

    public function __construct(array $attributes = [])
    {
        parent::__construct();

        $this->hash = substr(md5(microtime(true)), 0, 8);
        $this->data = $this->resource;
        $this->name = $this->resource->name;
        $this->description = $this->resource->description;
//        $this->activityType = $activityType;
//        $this->start_time = $startTime;
//        $this->end_time = $endTime;

        if (isset($attributes['resource'])) {
            $this->setResource($attributes['resource']);
        }

        if (isset($attributes['activity_type'])) {
            $this->activity_type_id = $attributes['activity_type']; // Assuming activity type is passed as ID
        }

        $this->phraseService = new PhraseService();
        $this->phrase = $this->phraseService->getPhraseForActivityResource($resource);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function getActivityTypeAttribute()
    {
        return $this->activityType;
    }

    /**
     * Dynamically load the related resource.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getResourceAttribute()
    {
        if (!$this->resource_table || !$this->resource_id) {
            return null;
        }

        $modelClass = '\\App\\Models\\' . ucfirst($this->resource_table);

        if (class_exists($modelClass)) {
            return $modelClass::find($this->resource_id);
        }

        return null;
    }

    /**
     * Set the related resource dynamically by setting resource_table and resource_id.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $resource
     * @return void
     */
    public function setResourceAttribute($resource)
    {
        if ($resource && $resource instanceof Model) {
            $this->resource_table = strtolower(class_basename($resource));
            $this->resource_id = $resource->id;
        }
    }
}
