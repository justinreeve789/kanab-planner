<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One short claim about a trail, restaurant or place, pulled from a blog post or review
 * (`php artisan chat:extract-facts`), e.g. topic "gear": "Bring water shoes; most of the hike is in the creek."
 */
class ResourceFact extends Model
{
    protected $guarded = [];
    protected $casts = ['source_date' => 'date', 'confidence' => 'float'];
}
