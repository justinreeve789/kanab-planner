<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    const BICYCLING = 'bicycling';
    const DINING = 'dining';
    const HIKING = 'hiking';

    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'activity_types';
}
