<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResourceData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'resource_data';

    public function activity_type(): HasOne
    {
        return $this->hasOne(ActivityType::class, 'id', 'activity_type_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::getModelForResourceTable($this->getTable()), 'id', 'resource_id');
    }

    public static function getRankingProps(): array
    {
        return [
            'adventurousness',
            'solitariness',
            'strenuousness',
        ];
    }
}
