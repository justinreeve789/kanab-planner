<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

//    protected $fillable = [
//        'source_type', 'source_id', 'name', 'description', 'free', 'price', 'location_id', 'start_date', 'start_time', 'end_date', 'end_time', 'raw_data'
//    ];

    protected $guarded = ['id'];
}
