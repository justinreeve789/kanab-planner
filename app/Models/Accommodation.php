<?php

namespace App\Models;

use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accommodation extends Resource
{
    use HasFactory;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $table = 'accommodations';

    public function toSearchableArray()
    {
        $searchableArray = $this->getAttributes();
//        $searchableArray['area_id'] = $this->location->area_id;
        $searchableArray['type'] = $this->type;
        return $searchableArray;
    }

    public function images(): HasMany {
        return $this->hasMany(AccommodationImage::class, 'accommodation_id', 'id');
    }
}
