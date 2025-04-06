<?php

namespace App\Console\Commands;

use App\Models\Blog\Blog;
use App\Services\BlogService;
use Illuminate\Console\Command;

class ImportBlogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:blogs {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the blog content with the newest posts and images.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        if ($id) {
            $blogs = [Blog::find($id)];
        } else {
            $blogs = Blog::all();
        }
        $blogService = new BlogService();
        foreach ($blogs as $blog) {
            $blogService->import($blog->id);
        }
    }
}
