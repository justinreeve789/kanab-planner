<?php

namespace App\Models;

use App\Traits\InsertWithValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailImage extends Model
{
    use InsertWithValues;
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['url'];
}
