<?php

/*
|--------------------------------------------------------------------------
| Drive times
|--------------------------------------------------------------------------
|
| With ROUTING_DRIVER=valhalla, drive times come from a Valhalla server (open source, OpenStreetMap data; see the
| valhalla service in docker-compose.yml). Otherwise, or whenever Valhalla can't answer, they're estimated from
| straight-line distance. Road events (closures, roadwork, incidents) come from the state 511 systems' free
| developer APIs and are refreshed by `php artisan update:road-events`.
|
*/

return [
    'driver' => env('ROUTING_DRIVER', 'estimate'),

    // Itinerary dates and times are local to the trip area.
    'timezone' => env('TRIP_TIMEZONE', 'America/Denver'),

    'valhalla' => [
        'url' => env('VALHALLA_URL', 'http://valhalla:8002'),
        'timeout' => (float) env('VALHALLA_TIMEOUT', 3),
    ],

    // Straight-line estimates: road distance is about 1.3 x straight-line distance here, at about 45 mph.
    'estimate' => [
        'road_factor' => 1.3,
        'mph' => 45,
    ],

    // Stops closer than this are treated as the same place (a café next to the trailhead).
    'walkable_miles' => 0.25,

    'cache_days' => (int) env('ROUTING_CACHE_DAYS', 30),

    // After a connection failure, use estimates for this long before trying the router again.
    'retry_after_seconds' => 300,

    'road_events' => [
        // A bounding box around the areas we plan for: [south, west, north, east].
        'bounds' => [35.5, -114.6, 38.6, -110.4],
        'sources' => [
            'udot' => [
                'url' => 'https://udottraffic.utah.gov/api/v2/get/event',
                'key' => env('UDOT_API_KEY'),
            ],
            'az511' => [
                'url' => 'https://az511.com/api/v2/get/event',
                'key' => env('AZ511_API_KEY'),
            ],
        ],
        // How close (miles) an event must be to a route to affect it.
        'near_route_miles' => 0.3,
        // Events with no end date are current incidents: only applied this many days ahead.
        'open_ended_days' => 2,
        // Extra minutes added to a drive that passes an event (full closures are routed around instead).
        'delay_minutes' => [
            'roadwork' => 10,
            'closures' => 15,
            'accidentsAndIncidents' => 15,
            'specialEvents' => 10,
        ],
    ],
];
