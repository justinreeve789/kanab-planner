<?php

namespace App\Models;

use App\Traits\InsertWithValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationImage extends Model
{
    use InsertWithValues;
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'accommodation_images';

    public function accommodation(): BelongsTo {
        return $this->belongsTo(Accommodation::class, 'accommodation_id', 'id');
    }
}
