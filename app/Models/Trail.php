<?php

namespace App\Models;

use App\Traits\ActivityData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trail extends Model
{
    use ActivityData;
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'area_id'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function data()
    {
        return $this->hasOne(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'trails');
    }
}
