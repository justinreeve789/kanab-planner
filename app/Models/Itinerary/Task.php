<?php

namespace App\Models\Itinerary;

use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    protected $guarded = [];
}
