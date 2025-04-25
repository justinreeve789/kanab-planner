<?php

namespace App\Models;

use App\Models\OpenAIPrompt;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SearchableResource;

class Guide extends Model
{
    use SearchableResource;

    protected $guarded = [];
    protected $table = 'guides';

    public function openai_prompts()
    {
        return $this->hasMany(OpenAIPrompt::class, 'source_id', 'id')->where('openai_prompts.source_table', $this->getTable());
    }

    public function text()
    {
        return $this->hasMany(ResourceText::class, 'resource_id', 'id')->where('resource_table', $this->getTable());
    }
}
