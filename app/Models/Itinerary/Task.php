<?php

namespace App\Models\Itinerary;

use App\Models\Resource;
use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Resource
{
    use HasFactory;

    protected $appends = ['image_ids'];
    protected $guarded = [];

    public function getImageIdsAttribute()
    {
        return [];
    }
}
