<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['resource_table', 'resource_id', 'created_at', 'updated_at'];
    protected $table = 'resource_data';
}
