<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceActivity extends Model
{
    use HasFactory;

    protected $fillable = ['*'];
    protected $table = 'resource_activities';
}
