<?php

namespace App\Jobs;

use App\Services\BlogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ImportBlogsJob implements ShouldQueue
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
        foreach ($this->blogIds as $blogId) {
            $blogService->import($blogId);
        }
    }
}
