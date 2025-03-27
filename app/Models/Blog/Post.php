<?php

namespace App\Models\Blog;

use App\Models\OpenAIPrompt;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];
    protected $table = 'blog_posts';

    public function openai_prompts()
    {
        return $this->hasMany(OpenAIPrompt::class, 'source_id', 'id')->where('openai_prompts.source_table', $this->getTable());
    }
}
