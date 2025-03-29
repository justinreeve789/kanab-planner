<?php

namespace App;

use App\Models\Blog\Post;

enum ResourceEnum
{
    const BLOG_POSTS_CLASS = Post::class;
    const BLOG_POSTS_TABLE = 'blog_posts';
    const EVENTS_CLASS = 'App\\Models\\Event';
    const EVENTS_TABLE = 'events';
    const LOCATIONS_CLASS = 'App\\Models\\Location';
    const LOCATIONS_TABLE = 'locations';
    const RESTAURANTS_CLASS = 'App\\Models\\Restaurant';
    const RESTAURANTS_TABLE = 'restaurants';
    const TASKS_CLASS = 'App\\Models\\Itinerary\\Task';
    const TASKS_TABLE = 'tasks';
    const TRAILS_CLASS = 'App\\Models\\Trail';
    const TRAILS_TABLE = 'trails';
}
