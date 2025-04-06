<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SearchableResource;

class ResourceText extends Model
{
    use HasFactory;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $table = 'resource_text';
}
