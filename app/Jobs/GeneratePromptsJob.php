<?php

namespace App\Jobs;

use App\Models\Blog\Post;
use App\Models\OpenAIPrompt;
use App\Services\BlogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class GeneratePromptsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    private $blogIds = [];

    /**
     * Create a new job instance.
     */
    public function __construct(array $blogIds)
    {
        $this->blogIds = $blogIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $blogService = new BlogService();
        $posts = Post::with('openai_prompts')->get();
        foreach ($posts as $post) {
            if (count($post->openai_prompts) <= 15) {
                $prompts = $blogService->extractPromptsFromPost($post);
                foreach ($prompts as $prompt) {
                    OpenAIPrompt::create([
                        'blog_post_id' => $post->id,
                        'prompt' => $prompt->prompt,
                        'answer' => $prompt->answer,
                    ]);
                }
            }
        }
    }
}
