<?php

namespace App\Models;

use App\Models\Builder\CustomQueryBuilder;
use App\ResourceEnum;
use App\Traits\ResourceScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class Resource extends Model
{
    use HasFactory;
    use ResourceScopes;

    protected $appends = ['description_short', 'adventurousness', 'solitariness', 'strenuousness'];
    protected $with = ['data', 'images', 'text'];

    /**
     * @return \App\Models\Builder\CustomQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomQueryBuilder($query);
    }

    public function busy()
    {
        return $this->hasMany(ResourceBusy::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }

    public function data()
    {
        return $this->hasMany(ResourceData::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
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

    /**
     * We want to get as close to all the properties as we can. For example, if adventurousness is 0.1 and strenuousness
     * is 0.9, we should weigh adv 0.2/str 0.8 higher than adv 0.5/str 0.5. We should evaluate the total deviation
     * rather than an average of the properties.
     * TODO: We may want to make this logarithmic, so preferences with higher values weigh heavier than lower values.
     * If a user has set a high adventurousness, it would make sense they'd want to see more events like this, even
     * if the deviation of the other properties skews the total out of place.
     *
     * @param array $userPropValues This contains adventurousness/solitariness/strenuousness as the key, followed by the user's settings for that property, e.g. ['adventurousness' => 0.5, 'solitariness' => 0.3, 'strenuousness' => 0.7]
     * @param array $weightValues This contains adventurousness/solitariness/strenuousness as the key, followed by a relative numerical weight for that property, e.g. ['adventurousness' => 12, 'solitariness' => 7, 'strenuousness' => 25]
     * @param int $activityTypeId Right now this corresponds to App\Models\Activity, but this will be renamed to App\Models\ActivityType in the future.
     */
    public function scopeSortByDeviation(CustomQueryBuilder $builder, array $userPropValues, array $weights, int $activityTypeId = null) {
        $table = $this->getModel()->getTable();

        $order = '';
        $index = 0;
        foreach ($userPropValues as $prop => $value) {
            $order .= 'POW(' . $weights[$prop] . ', ABS(COALESCE(resource_data.' . $prop . ', 0) - ' . $value . '))';
            if ($index < count($userPropValues) - 1) {
                $order .= ' + ';
            }
            $index++;
        }

        // Round to the nearest 0.25.
        $order = '(ROUND(4 * ' . $order . ') / 4), RAND()';

        $query = $this->select($table . '.*')
            ->join('resource_data', function($join) use ($table) {
                $join->on('resource_data.resource_table', '=', \DB::raw("'" . $table . "'"))->on($table . '.id', '=', 'resource_data.resource_id');
            });

        if ($order != '') {
            $query->orderByRaw($order);
        }

        return $query;
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

    public function isTask() {
        return get_class($this) === ResourceEnum::TASKS_CLASS;
    }

    public function isTrail() {
        return get_class($this) === ResourceEnum::TRAILS_CLASS;
    }
}
