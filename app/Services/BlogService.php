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
            // $prompts = $this->openAIService->generatePromptsWithOpenAI($blogPost->content);
            // return json_decode($prompts);
        }
    }

    public function import($blogId, $storeData = false)
    {
        $blog = Blog::find($blogId);
        if ($blog) {
            $this->savePostsFromBlog($blog, $storeData);
        }
    }

    /**
     * @param Blog $blog
     * @param bool $storeData If true, the content and raw_data is stored if there's no resource, otherwise it's omitted.
     * @param int $perPage
     * @return array
     */
    public function savePostsFromBlog(Blog $blog, bool $storeData = false, int $perPage = 25) {
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
                $keepData = $storeData;
                if (!$storeData) {
                    $postObj = Post::where(['blog_id' => $blog->id, 'post_id' => $post['id']])->whereNotNull('resource_table')->whereNotNull('resource_id')->first();
                    if ($postObj) {
                        $keepData = true;
                    }
                }

                $title = str_replace('&#8217;', "'", $post['title']['rendered']);
                $title = str_replace('&#8211;', "--", $title);
                $title = str_replace('&#038;', "&", $title);
                $content = str_replace('&#8217;', "'", $post['content']['rendered']);
                $content = str_replace('&#8211;', "--", $content);

                Post::updateOrCreate([
                    'blog_id' => $blog->id,
                    'post_id' => $post['id'],
                ], [
                    'title' => $title,
                    'content' => $keepData ? $content : null,
                    'raw_data' => $keepData ? json_encode($post) : null,
                    'posted_at' => date('Y-m-d H:i:s', strtotime($post['date'])),
                ]);
            }
            $page++;
            $allPosts = array_merge($allPosts, $posts);
        }
        return $allPosts;
    }
}
