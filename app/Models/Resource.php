<?php

namespace App\Models;

use App\Models\Blog\Post;
use App\Models\Builder\CustomQueryBuilder;
use App\ResourceEnum;
use App\Traits\ResourceScopes;
use App\Traits\SearchableResource;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class Resource extends Model
{
    use HasFactory;
    use ResourceScopes;
    use SearchableResource;

    const DEFAULT_AVERAGE_HOURS = 2;

    // TODO: Use $appends and reference it (e.g. via self::$appends)...with Laravel models this doesn't work easily.
    protected $resourceAppends = ['description_short', 'adventurousness', 'solitariness', 'strenuousness', 'type', 'image_ids'];
    protected $with = ['area'];
//    protected $with = ['data', 'images', 'text'];

    /**
     * @return \App\Models\Builder\CustomQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomQueryBuilder($query);
    }

    public function toSearchableArray()
    {
        $searchableArray = $this->toArray();
        foreach ($this->getAppends() as $append) {
            $searchableArray[$append] = $this->{$append};
        }
        $searchableArray['resource_table'] = $this->getTable();
        if (isset($this->area)) {
            $searchableArray['area_id'] = $this->area->id ?? null;
            $searchableArray['area_name'] = $this->area->name ?? '';
            $searchableArray['area_short_name'] = $this->area->short_name ?? '';
            $searchableArray['area_abbreviation'] = $this->area->abbreviation ?? '';
            $searchableArray['area_slug'] = $this->area->slug ?? '';
        }
        return $searchableArray;
    }

    public function getAppends()
    {
        return array_merge($this->appends, $this->resourceAppends);
    }

    public function busy()
    {
        return $this->hasMany(ResourceBusy::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    public function data()
    {
        return $this->hasMany(ResourceData::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    public function merges()
    {
        return $this->hasMany(ResourceMerge::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    public function reviews()
    {
        return $this->hasMany(ResourceReview::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    public function text()
    {
        return $this->hasMany(ResourceText::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    private function getASS($attr)
    {
        if (isset($this->data)) {
            $data = $this->data->keyBy('activity_id')->toArray();
            if ($data) {
                return reset($data)[$attr];
            }
        }
        return null;
    }

    public function getAdventurousnessAttribute()
    {
        return $this->getASS('adventurousness');
    }

    public function getDescriptionShortAttribute()
    {
        if (isset($this->text)) {
            $text = $this->text->keyBy('type');
            if (isset($text['short'])) {
                return $text['short']->clean;
            }
        }
    }

    public function getSolitarinessAttribute()
    {
        return $this->getASS('solitariness');
    }

    public function getStrenuousnessAttribute()
    {
        return $this->getASS('strenuousness');
    }

    public function getTypeAttribute()
    {
        return substr($this->getTable(), 0, -1);
    }

    /**
     * Order by how far each resource's adventurousness/solitariness/strenuousness are from the user's, as
     * sum(weight ^ |difference|), rounded to the nearest 0.25 with random tie-breaking.
     *
     * We want to get as close to all the properties as we can. For example, if adventurousness is 0.1 and strenuousness
     * is 0.9, we should weigh adv 0.2/str 0.8 higher than adv 0.5/str 0.5. We should evaluate the total deviation
     * rather than an average of the properties.
     *
     * resource_data is LEFT JOINed (one row per resource, for $activityTypeId when given), and a resource without a
     * row is scored with its activity type's defaults instead of being dropped. The INNER JOIN used here before hid
     * about a quarter of usable trails from itineraries.
     *
     * @param array $userPropValues e.g. ['adventurousness' => 0.5, 'solitariness' => 0.3, 'strenuousness' => 0.7]
     * @param array $weights e.g. ['adventurousness' => 12, 'solitariness' => 7, 'strenuousness' => 25]
     */
    public function scopeSortByDeviation(CustomQueryBuilder $builder, array $userPropValues, array $weights, ?int $activityTypeId = null) {
        $table = $this->getModel()->getTable();
        $defaults = $activityTypeId ? ActivityType::find($activityTypeId) : null;

        $terms = [];
        $bindings = [];
        foreach ($userPropValues as $prop => $value) {
            if (!in_array($prop, ResourceData::getRankingProps(), true) || $value === null) {
                continue;
            }
            $terms[] = "POW(?, ABS(COALESCE(rd.{$prop}, ?) - ?))";
            array_push($bindings, (float) ($weights[$prop] ?? 10), (float) ($defaults?->{$prop} ?? 0.5), (float) $value);
        }

        $query = $builder->select($table . '.*')->leftJoinSub(
            ResourceData::query()
                ->selectRaw('resource_id, AVG(adventurousness) AS adventurousness, AVG(solitariness) AS solitariness, AVG(strenuousness) AS strenuousness')
                ->where('resource_table', $table)
                ->when($activityTypeId, fn($q) => $q->where('activity_type_id', $activityTypeId))
                ->groupBy('resource_id'),
            'rd', 'rd.resource_id', '=', $table . '.id'
        );

        if ($terms) {
            $query->orderByRaw('ROUND(4 * (' . implode(' + ', $terms) . ')) / 4', $bindings)->inRandomOrder();
        }

        return $query;
    }

    public function calculateEndTime(string $date, string $startTime, array $params = [])
    {
        $hours = $this->getAverageHours($params + ['date' => $date, 'start_time' => $startTime]);
        return date('H:i:s', (int) (strtotime($date . ' ' . $startTime) + $hours * 60 * 60));
    }

    public function getAverageHours(array $params = [])
    {
        return self::DEFAULT_AVERAGE_HOURS;
    }

    public function isEvent() {
        return get_class($this) === ResourceEnum::EVENTS_CLASS;
    }

    public function isLocation() {
        return get_class($this) === ResourceEnum::LOCATIONS_CLASS;
    }

    public function isRestaurant() {
        return get_class($this) === ResourceEnum::RESTAURANTS_CLASS;
    }

    public function isTrail() {
        return get_class($this) === ResourceEnum::TRAILS_CLASS;
    }

    public static function getFeatured(array $areaIds, int $featuredCount = 3)
    {
        return null;
    }

    public static function getModelForResourceTable($resourceTable)
    {
        return match ($resourceTable) {
            ResourceEnum::ACCOMMODATIONS_TABLE => Accommodation::class,
            ResourceEnum::BLOG_POSTS_TABLE => Post::class,
            ResourceEnum::EVENTS_TABLE => Event::class,
            ResourceEnum::GUIDES_TABLE => Guide::class,
            ResourceEnum::LOCATIONS_TABLE => Location::class,
            ResourceEnum::RESTAURANTS_TABLE => Restaurant::class,
            ResourceEnum::TRAILS_TABLE => Trail::class,
            default => throw new Exception('Invalid table: ' . $resourceTable),
        };
    }

    public static function getTypeForResourceTable($resourceTable)
    {
        return match ($resourceTable) {
            ResourceEnum::ACCOMMODATIONS_TABLE => ResourceEnum::ACCOMMODATIONS_TYPE,
            ResourceEnum::EVENTS_TABLE => ResourceEnum::EVENTS_TYPE,
            ResourceEnum::LOCATIONS_TABLE => ResourceEnum::LOCATIONS_TYPE,
            ResourceEnum::RESTAURANTS_TABLE => ResourceEnum::RESTAURANTS_TYPE,
            ResourceEnum::TRAILS_TABLE => ResourceEnum::TRAILS_TYPE,
            default => throw new Exception('Invalid table: ' . $resourceTable),
        };
    }
}
