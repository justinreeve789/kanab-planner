<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceText extends Model
{
    use HasFactory;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $table = 'resource_text';

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::getModelForResourceTable($this->getTable()), 'id', 'resource_id');
    }
}
