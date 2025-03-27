<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Blog\Blog;
use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Location;
use App\Models\ResourceText;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlogService
{
    private $client;
    private $conversionService;


    public function __construct()
    {
        $this->client = new Client();
        $this->conversionService = new ConversionService();
    }

    public function import($blogId)
    {
        $blog = Blog::find($blogId);
        if ($blog) {
            $blog_posts = $this->savePostsFromBlog($blog);
            if (!empty($blog_posts)) {
//                foreach ($blog_posts as $post) {
//                    Post::create([
//                        'blog_id' => $blogId,
//                        'title' => '',
//                        'content' => '',
//                        'raw_data' => $post,
//                        'posted_at' => '',
//                    ]);
//                }
            }
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
                    'post_id' => $post['id']
                ]);
            }
            $page++;
            $allPosts = array_merge($allPosts, $posts);
        }
        return $allPosts;
    }
}
