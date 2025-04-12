<?php

namespace App;

use App\Models\Accommodation;
use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\Guide;
use App\Models\Itinerary\Task;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;

enum ResourceEnum
{
    const ACCOMMODATIONS_CLASS = Accommodation::class;
    const ACCOMMODATIONS_TABLE = 'accommodations';
    const BLOG_POSTS_CLASS = Post::class;
    const BLOG_POSTS_TABLE = 'blog_posts';
    const EVENTS_CLASS = Event::class;
    const EVENTS_TABLE = 'events';
    const GUIDES_CLASS = Guide::class;
    const GUIDES_TABLE = 'guides';
    const LOCATIONS_CLASS = Location::class;
    const LOCATIONS_TABLE = 'locations';
    const RESTAURANTS_CLASS = Restaurant::class;
    const RESTAURANTS_TABLE = 'restaurants';
    const TASKS_CLASS = Task::class;
    const TASKS_TABLE = 'tasks';
    const TRAILS_CLASS = Trail::class;
    const TRAILS_TABLE = 'trails';
}
