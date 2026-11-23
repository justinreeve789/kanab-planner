<?php

namespace App\Models\Itinerary;

use App\Models\Accommodation;
use App\Models\ActivityType;
use App\Models\Event;
use App\Models\Location;
use App\Models\Resource;
use App\Models\Restaurant;
use App\Models\Trail;
use App\ResourceEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One scheduled thing in an itinerary period: a resource (trail, restaurant, event, location, accommodation) or a
 * task with just a name and description (breakfast at home, pack a lunch).
 *
 * adventurousness / solitariness / strenuousness are read from the resource's resource_data row. They used to be
 * computed and written back to resource_data every time an activity was serialized.
 */
class Activity extends CachedModel
{
    public const DEFAULT_ADVENTUROUSNESS = 0.3;
    public const DEFAULT_SOLITARINESS = 0.3;
    public const DEFAULT_STRENUOUSNESS = 0.3;

    public const RESOURCE_CLASSES = [
        ResourceEnum::ACCOMMODATIONS_TABLE => Accommodation::class,
        ResourceEnum::EVENTS_TABLE => Event::class,
        ResourceEnum::LOCATIONS_TABLE => Location::class,
        ResourceEnum::RESTAURANTS_TABLE => Restaurant::class,
        ResourceEnum::TRAILS_TABLE => Trail::class,
    ];

    /** Relations sent along with an activity's resource, e.g. a restaurant's website lives on its location. */
    private const RESOURCE_RELATIONS = [
        ResourceEnum::RESTAURANTS_TABLE => ['location'],
    ];

    protected $appends = ['adventurousness', 'solitariness', 'strenuousness', 'resource'];
    protected $casts = [
        'id' => 'string', 'period_id' => 'string',
        'travel_minutes' => 'integer', 'travel_miles' => 'float', 'travel_notes' => 'json',
    ];
    protected $guarded = [];
    protected $table = 'itinerary_activities';
    protected $with = ['activity_type'];

    public function __construct(array $attributes = [])
    {
        $this->id = Str::uuid();
        parent::__construct($attributes);
    }

    public function activity_type()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id', 'id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id', 'id');
    }

    protected function resource(): Attribute
    {
        return Attribute::make(
            get: function() {
                $class = self::RESOURCE_CLASSES[$this->resource_table] ?? null;
                if (!$class || !$this->resource_id) {
                    return null;
                }
                return $class::with(self::RESOURCE_RELATIONS[$this->resource_table] ?? [])->find($this->resource_id);
            },
            // Return the columns to write; assignments made inside the closure would be discarded by Eloquent.
            set: function(?Resource $resource) {
                if ($resource === null) {
                    return ['resource_table' => null, 'resource_id' => null];
                }
                $table = array_search(get_class($resource), self::RESOURCE_CLASSES, true);
                if ($table === false) {
                    throw new InvalidArgumentException('Unsupported resource class: ' . get_class($resource));
                }
                return ['resource_table' => $table, 'resource_id' => $resource->id];
            }
        );
    }

    protected function adventurousness(): Attribute
    {
        return Attribute::get(fn() => $this->resourceData('adventurousness'));
    }

    protected function solitariness(): Attribute
    {
        return Attribute::get(fn() => $this->resourceData('solitariness'));
    }

    protected function strenuousness(): Attribute
    {
        return Attribute::get(fn() => $this->resourceData('strenuousness'));
    }

    /**
     * The resource_data value for this activity's type, falling back to the resource's first row.
     */
    private function resourceData(string $prop): ?float
    {
        $resource = $this->resource;
        if (!$resource || !isset($resource->data) || $resource->data->isEmpty()) {
            return null;
        }
        $row = $resource->data->firstWhere('activity_type_id', $this->activity_type_id) ?? $resource->data->first();
        return $row->{$prop} === null ? null : (float) $row->{$prop};
    }

    public function isTask(): bool
    {
        return $this->resource_table === null;
    }

    public function durationMinutes(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        return max(0, Timeline::toMinutes($this->end_time) - Timeline::toMinutes($this->start_time));
    }
}
