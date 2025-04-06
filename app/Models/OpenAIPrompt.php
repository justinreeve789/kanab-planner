<?php

namespace App\Models;

use App\Models\Blog\Post;
use App\Traits\InsertWithValues;
use App\Traits\SearchableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenAIPrompt extends Model
{
    use HasFactory;
    use InsertWithValues;
    use SearchableResource;

    protected $guarded = ['id'];
    protected $table = 'openai_prompts';

    public function blog_post()
    {
        return $this->belongsTo(Post::class, 'source_id', 'id')->where('source_table', 'blog_posts');
    }

    public function trail()
    {
        return $this->belongsTo(Trail::class, 'source_id', 'id')->where('source_table', 'trails');
    }
}
