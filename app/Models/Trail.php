<?php

namespace App\Models;

use App\Models\Resource;
use App\Traits\ActivityData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trail extends Resource
{
    use ActivityData;
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'area_id'];

    public function __construct()
    {
//        $this->setActivityType(self::DEFAULT_ACTIVITY_TYPE);
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function data()
    {
        return $this->hasMany(ResourceData::class, 'resource_id', 'id')->where('resource_table', 'trails');
    }

    public function isEasy()
    {
        return strpos(strtolower($this->difficulty), 'easy') !== false;
    }

    public function isIntermediate()
    {
        return strpos(strtolower($this->difficulty), 'intermediate') !== false;
    }

    public function isDifficult()
    {
        return strpos(strtolower($this->difficulty), 'difficult') !== false;
    }
}
