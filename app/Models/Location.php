<?php

namespace App\Models;

use App\Traits\Proximity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SearchableResource;

class Location extends Resource
{
    use HasFactory;
    use Proximity;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data'];

    public function toSearchableArray()
    {
        $searchableArray = $this->toArray();
        $searchableArray['location_id'] = $this->id;
        $searchableArray['is_restaurant'] = $this->restaurant ? 1 : 0;
        return $searchableArray;
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'location_id', 'id');
    }

    public function hours()
    {
        return $this->hasMany(LocationHour::class, 'location_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(LocationImage::class, 'location_id', 'id');
    }

    public function isOpen($dateTime)
    {
        $dayOfWeek = date('w', strtotime($dateTime));
        $time = date('H:i:s', strtotime($dateTime));
    }

    public function openai_prompts()
    {
        return $this->hasMany(OpenAIPrompt::class, 'source_id', 'id')->where('openai_prompts.source_table', $this->getTable());
    }

    public function restaurant()
    {
        return $this->hasOne(Restaurant::class, 'location_id', 'id');
    }

    public function getSourceIdAttribute()
    {
        // Return the Location's source_id or fallback to the Restaurant's source_id if available
        return $this->source_id ?? optional($this->restaurant)->source_id;
    }
}
