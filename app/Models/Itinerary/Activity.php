<?php

namespace App\Models\Itinerary;

use App\Models\ActivityType;
use App\Models\Resource;
use App\ResourceEnum;
use App\Services\PhraseService;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Activity extends Model
{
    use HasFactory;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;

    protected $guarded = [];
    protected $table = 'itinerary_activities';

//    public $hash;
    public $data;
//    public $activityType;   // hiking, offroading, etc.
//    public $description;
    public $phrase = '';
//    public $end_time;
//    public $name;
//    public $start_time;
    private $phraseService;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

//        $this->hash = substr(md5(microtime(true)), 0, 8);
//        $this->data = $this->resource;
//        $this->name = $this->resource->name;
//        $this->description = $this->resource->description;
//        $this->activityType = $activityType;
//        $this->start_time = $startTime;
//        $this->end_time = $endTime;

        if (isset($attributes['activity_type'])) {
            $this->activity_type_id = $attributes['activity_type'];
        }

        if (isset($attributes['resource'])) {
            $this->resource = $attributes['resource'];
        }

//        echo '<pre>'; print_r($attributes); echo '</pre>';
//        echo '<pre>'; print_r($this); echo '</pre>';

        $this->phraseService = new PhraseService();
        $this->phrase = $this->phraseService->getPhraseForActivityResource($this->resource);
    }

    public function activity_type()
    {
        return $this->belongsTo(ActivityType::class, 'id', 'activity_type_id');
    }

    protected function resource(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (is_integer($this->resource_id)) {
                    if ($this->resource_table === ResourceEnum::EVENTS_TABLE) {
                        $modelClass = ResourceEnum::EVENTS_CLASS;
                    } elseif ($this->resource_table === ResourceEnum::LOCATIONS_TABLE) {
                        $modelClass = ResourceEnum::LOCATIONS_CLASS;
                    } elseif ($this->resource_table === ResourceEnum::RESTAURANTS_TABLE) {
                        $modelClass = ResourceEnum::RESTAURANTS_CLASS;
                    } elseif ($this->resource_table === ResourceEnum::TASKS_TABLE) {
                        $modelClass = ResourceEnum::TASKS_CLASS;
                    } elseif ($this->resource_table === ResourceEnum::TRAILS_TABLE) {
                        $modelClass = ResourceEnum::TRAILS_CLASS;
                    }

                    if (isset($modelClass)) {
                        return $modelClass::find($this->resource_id);
                    }
                }
            },
            set: function($resource) {
                $this->resource_id = $resource->id;
                switch (get_class($resource)) {
                    case ResourceEnum::EVENTS_CLASS:
                        $this->resource_table = ResourceEnum::EVENTS_TABLE;
                        break;
                    case ResourceEnum::LOCATIONS_CLASS:
                        $this->resource_table = ResourceEnum::LOCATIONS_TABLE;
                        break;
                    case ResourceEnum::RESTAURANTS_CLASS:
                        $this->resource_table = ResourceEnum::RESTAURANTS_TABLE;
                        break;
                    case ResourceEnum::TASKS_CLASS:
                        $this->resource_table = ResourceEnum::TASKS_TABLE;
                        break;
                    case ResourceEnum::TRAILS_CLASS:
                        $this->resource_table = ResourceEnum::TRAILS_TABLE;
                        break;
                    default:
                        throw new InvalidArgumentException('Unsupported resource class.');
                }
            }
        );
    }

    /**
     * Dynamically load the related resource.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
//    public function getResourceAttribute()
//    {
//        if (!$this->resource_table || !$this->resource_id) {
//            return null;
//        }
//
//        $modelClass = '\\App\\Models\\' . ucfirst($this->resource_table);
//
//        if (class_exists($modelClass)) {
//            return $modelClass::find($this->resource_id);
//        }
//
//        return null;
//    }
//
//    /**
//     * Set the related resource dynamically by setting resource_table and resource_id.
//     *
//     * @param  \Illuminate\Database\Eloquent\Model  $resource
//     * @return void
//     */
//    public function setResourceAttribute($resource)
//    {
//        if ($resource && $resource instanceof Resource) {
//            $this->resource_table = strtolower(class_basename($resource));
//            $this->resource_id = $resource->id;
//            $this->resource = $resource;
//
////            switch (get_class($resource)) {
////                case ResourceEnum::EVENTS_CLASS:
////                    $this->resource_table = ResourceEnum::EVENTS_TABLE;
////                    break;
////                case ResourceEnum::LOCATIONS_CLASS:
////                    $this->resource_table = ResourceEnum::LOCATIONS_TABLE;
////                    break;
////                case ResourceEnum::RESTAURANTS_CLASS:
////                    $this->resource_table = ResourceEnum::RESTAURANTS_TABLE;
////                    break;
////                case ResourceEnum::TASKS_CLASS:
////                    $this->resource_table = ResourceEnum::TASKS_TABLE;
////                    break;
////                case ResourceEnum::TRAILS_CLASS:
////                    $this->resource_table = ResourceEnum::TRAILS_TABLE;
////                    break;
////                default:
////                    throw new InvalidArgumentException('Unsupported resource class.');
////            }
//
//        }
//    }
}
