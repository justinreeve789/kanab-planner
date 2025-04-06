<?php

namespace App\Services;

use App\Models\Blog\Blog;
use App\Models\Blog\Post;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BlogService
{
    private $client;
    private $conversionService;
    private $openAIService;


    public function __construct()
    {
        $this->client = new Client();
        $this->conversionService = new ConversionService();
        $this->openAIService = new OpenAIService();
    }

    public function extractPromptsFromPost($blogPost)
    {
        if (is_integer($blogPost)) {
            $blogPost = Post::find($blogPost);
        }

        if ($blogPost) {
            $prompts = $this->openAIService->generatePromptsWIthOpenAI($blogPost->content);
            return json_decode($prompts);
        }
    }

    public function import($blogId)
    {
        $blog = Blog::find($blogId);
        if ($blog) {
            $this->savePostsFromBlog($blog);
        }
    }

    public function savePostsFromBlog(Blog $blog, int $perPage = 50) {
        $page = 1;
        $allPosts = [];

        while (true) {
            $apiUrl = $blog->url . '/wp-json/wp/v2/posts/?page=' . $page . '&per_page=' . $perPage;
            $response = Http::get($apiUrl);
            if ($response->failed()) {
                break;
            }
            $posts = $response->json();
            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                Post::updateOrCreate([
                    'title' => $post['title']['rendered'],
                    'content' => $post['content']['rendered'],
                    'raw_data' => json_encode($post),
                    'posted_at' => date('Y-m-d H:i:s', strtotime($post['date'])),
                ], [
                    'blog_id' => $blog->id,
                    'post_id' => $post['id'],
                ]);
            }
            $page++;
            $allPosts = array_merge($allPosts, $posts);
        }
        return $allPosts;
    }
}
