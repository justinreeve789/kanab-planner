<?php

namespace App\Models\Itinerary;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

abstract class CachedModel extends Model
{
    use HasFactory, HasUuids;

    protected $cachePrefix = 'itinerary-';

//    /**
//     * Override getAttribute to automatically generate a UUID for "id" if not set.
//     *
//     * @param string $key
//     * @return mixed
//     */
//    public function getAttribute($key)
//    {
//        if ($key === 'id' && empty($this->attributes['id'])) {
//            $this->attributes['id'] = (string) Str::uuid();
//        }
//        return parent::getAttribute($key);
//    }
//
//    public function save(array $options = [])
//    {
//        // If the model doesn't have an id, set a random id before saving.
//        if (!$this->getKey()) {
//            $this->setAttribute($this->getKeyName(), (string) Str::uuid());
//        }
//        Cache::set(get_class($this) . '_' . $this->id, $this);
//        return true;
////        parent::save($options);
//    }
//
//    /**
//     * Override the create method to automatically assign a random id when a new model is created.
//     *
//     * @param array $attributes
//     * @return \Illuminate\Database\Eloquent\Model
//     */
//    public static function create(array $attributes = [])
//    {
//        // Check if an id is already set; if not, generate one.
//        if (!isset($attributes['id'])) {
//            $attributes['id'] = (string) Str::uuid();
//        }
//        return parent::create($attributes);
//    }
}
