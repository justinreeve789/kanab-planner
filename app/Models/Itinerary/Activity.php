<?php

namespace App\Models\Itinerary;

use App\Models\Accommodation;
use App\Models\ActivityType;
use App\Models\Event;
use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;
use App\ResourceEnum;
use App\Services\DataService;
use App\Services\PhraseService;
use App\Traits\ActivityData;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Activity extends CachedModel
{
    use ActivityData;

    const DEFAULT_ADVENTUROUSNESS = 0.3;
    const DEFAULT_SOLITARINESS = 0.3;
    const DEFAULT_STRENUOUSNESS = 0.3;

    protected $appends = ['adventurousness', 'solitariness', 'strenuousness', 'resource'];
    protected $casts = ['id' => 'string', 'period_id' => 'string'];
    protected $guarded = [];
    protected $table = 'itinerary_activities';
    protected $with = ['activity_type'];

    public $data;
    public $phrase = '';
    private $dataService;
    private $phraseService;

    public function __construct(array $attributes = [])
    {
        $this->id = Str::uuid();
        $this->dataService = new DataService();
        $this->phraseService = new PhraseService();

        parent::__construct($attributes);

//        if (isset($attributes['activity_type'])) {
//            // TODO: It's not efficient to call the database each time. Fix this, or use just activity_type or activity_type_id, not both.
//            if (!isset($this->activity_type_id)) {
//                $this->activity_type_id = ActivityType::where('name', $attributes['activity_type'])->pluck('id')->first();
//            }
//        }

//        if (isset($attributes['activity_type_id'])) {
//            // TODO: It's not efficient to call the database each time. Fix this, or use just activity_type or activity_type_id, not both.
//            if (!isset($this->activity_type)) {
//                $this->activity_type = ActivityType::where('id', $attributes['activity_type_id'])->first();
//            }
//        }

        if (isset($attributes['phrase'])) {
            $this->phrase = $attributes['phrase'];
        }

        if (isset($attributes['resource'])) {
            $this->resource = $attributes['resource'];
        }
    }

    public function activity_type()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id', 'id');
    }

//    protected function activity_type(): Attribute
//    {
//        return Attribute::make(
//            get: function() {
//                if (isset($this->activity_type_id)) {
//                    return ActivityType::find($this->activity_type_id)->name;
//                }
//                return null;
//            },
//        );
//    }

//    protected function activityType(): Attribute
//    {
//        return Attribute::make(
//            get: function() {
//                if (isset($this->activity_type)) {
//                    return $this->activity_type;
//                }
//
//                if (! $this->relationLoaded('activity_type_id') && $this->activity_type_id) {
//                    $this->load('activityType');
//                }
//                return $this->getRelation('activity_type');
//            },
//            set: function(?ActivityType $activityType) {
//                return ['activity_type_id' => $activityType ? $activityType->id : null];
//            },
//        )->withoutObjectCaching();
//    }
//
//    protected function activityTypeId(): Attribute
//    {
//        return Attribute::make(
//            get: fn ($value) => $value, // No special getter logic needed
//            set: function (?int $activityTypeId) {
//                $this->attributes['activity_type_id'] = $activityTypeId;
//
//                if ($activityTypeId) {
//                    $this->load('activity_type');
//                } else {
//                    $this->activityType()->dissociate();
//                    $this->activityType = null;
//                }
//
//                return $activityTypeId;
//            },
//        );
//    }

    /**
     * TODO: Use a cache.
     */
    protected function adventurousness(): Attribute
    {
        return Attribute::get(function() {
            if ($this->shouldHaveAttribute('adventurousness')) {
                $resourceData = ResourceData::where([
                    'resource_table', $this->resource->getTable(),
                    'resource_id' => $this->resource->id,
                    'activity_type_id' => $this->activity_type_id,
                ]);
                if (!$resourceData || !isset($resourceData->adventurousness)) {
                    $attr = $this->dataService->calculateAdventurousness($this->resource, $this->activity_type_id);
                    ResourceData::updateOrInsert([
                        'resource_table' => $this->resource->getTable(),
                        'resource_id' => $this->resource->id,
                        'activity_type_id' => $this->activity_type_id,
                    ], [
                        'adventurousness' => $attr,
                    ]);
                } else {
                    $attr = $resourceData->adventurousness;
                }
                return $attr;
            }
            return null;
        });
    }

    /**
     * TODO: Use a cache.
     */
    protected function solitariness(): Attribute
    {
        return Attribute::get(function() {
            if ($this->shouldHaveAttribute('solitariness')) {
                $resourceData = ResourceData::where([
                    'resource_table', $this->resource->getTable(),
                    'resource_id' => $this->resource->id,
                    'activity_type_id' => $this->activity_type_id,
                ]);
                if (!$resourceData || !isset($resourceData->solitariness)) {
                    $attr = $this->dataService->calculateSolitariness($this->resource, $this->activity_type_id);
                    ResourceData::updateOrInsert([
                        'resource_table' => $this->resource->getTable(),
                        'resource_id' => $this->resource->id,
                        'activity_type_id' => $this->activity_type_id,
                    ], [
                        'solitariness' => $attr,
                    ]);
                } else {
                    $attr = $resourceData->solitariness;
                }
                return $attr;
            }
            return null;
        });
    }

    /**
     * TODO: Use a cache.
     */
    protected function strenuousness(): Attribute
    {
        return Attribute::get(function() {
            if ($this->shouldHaveAttribute('strenuousness')) {
                $resourceData = ResourceData::where([
                    'resource_table', $this->resource->getTable(),
                    'resource_id' => $this->resource->id,
                    'activity_type_id' => $this->activity_type_id,
                ]);
                if (!$resourceData || !isset($resourceData->strenuousness)) {
                    $attr = $this->dataService->calculateStrenuousness($this->resource, $this->activity_type_id);
                    ResourceData::updateOrInsert([
                        'resource_table' => $this->resource->getTable(),
                        'resource_id' => $this->resource->id,
                        'activity_type_id' => $this->activity_type_id,
                    ], [
                        'strenuousness' => $attr,
                    ]);
                } else {
                    $attr = $resourceData->strenuousness;
                }
                return $attr;
            }
            return null;
        });
    }

    protected function phrase(): Attribute
    {
        return Attribute::make(
            get: function() {
                return $this->phrase = $this->phraseService->getPhraseForActivityResource($this->resource);
            }
        );
    }

    protected function resource(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (is_integer($this->resource_id)) {
                    if ($this->resource_table === ResourceEnum::ACCOMMODATIONS_TABLE) {
                        $modelClass = Accommodation::class;
                    } elseif ($this->resource_table === ResourceEnum::EVENTS_TABLE) {
                        $modelClass = Event::class;
                    } elseif ($this->resource_table === ResourceEnum::LOCATIONS_TABLE) {
                        $modelClass = Location::class;
                    } elseif ($this->resource_table === ResourceEnum::RESTAURANTS_TABLE) {
                        $modelClass = Restaurant::class;
                    } elseif ($this->resource_table === ResourceEnum::TRAILS_TABLE) {
                        $modelClass = Trail::class;
                    }

                    if (isset($modelClass)) {
                        return $modelClass::find($this->resource_id);
                    }
                }
            },
            set: function($resource) {
                if ($resource === null) {
                    return collect();
                }
                $this->resource_id = $resource->id;
                switch (get_class($resource)) {
                    case Accommodation::class:
                        $this->resource_table = ResourceEnum::ACCOMMODATIONS_TABLE;
                        break;
                    case Event::class:
                        $this->resource_table = ResourceEnum::EVENTS_TABLE;
                        break;
                    case Location::class:
                        $this->resource_table = ResourceEnum::LOCATIONS_TABLE;
                        break;
                    case Restaurant::class:
                        $this->resource_table = ResourceEnum::RESTAURANTS_TABLE;
                        break;
                    case Trail::class:
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

    public static function resolveRequest(Request $request)
    {
        $resourceTable = $request->get('resource_table');
        $resourceId = $request->get('resource_id');
    }
}
