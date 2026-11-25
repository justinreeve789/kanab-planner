<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\Hours;
use App\Chat\Data\TrailProfiler;
use App\Models\Location;

class FindPlaces extends BaseTool
{
    /** Google place types in location_tags that travelers ask for. */
    public const TAGS = [
        'gas_station', 'grocery_or_supermarket', 'supermarket', 'convenience_store', 'store', 'bicycle_store',
        'car_rental', 'museum', 'art_gallery', 'park', 'tourist_attraction', 'landmark', 'campground', 'rv_park',
        'lodging', 'travel_agency', 'cafe', 'bakery', 'bar', 'restaurant', 'meal_takeaway', 'spa', 'zoo',
        'pharmacy', 'hospital', 'doctor', 'pet_store', 'veterinary_care', 'clothing_store', 'shopping_mall', 'atm', 'bank',
    ];

    public function name(): string
    {
        return 'find_places';
    }

    public function description(): string
    {
        return 'Find places that aren\'t trails: gas stations, groceries, outfitters and rental shops, museums, '
            . 'attractions, viewpoints, tour companies (travel_agency), campgrounds, pharmacies, vets and more. Filter by '
            . 'name or words in the description, Google place tags, area, distance from a place, and open at a time.';
    }

    public function parameters(): array
    {
        return self::object([
            'query' => ['type' => 'string', 'description' => 'Words in the name or description, e.g. "canyoneering", "e-bike", "dinosaur".'],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::TAGS]],
            'area_id' => ['type' => 'integer'],
            'near' => self::object(Geo::placeSchema()),
            'within_miles' => ['type' => 'number', 'description' => 'With near, default 25.'],
            'open_at' => ['type' => 'string', 'description' => 'Local date and time, e.g. 2026-12-02 06:30.'],
            'limit' => ['type' => 'integer', 'description' => 'Default 8.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'query' => 'nullable|string|max:80',
            'tags' => 'nullable|array|max:6',
            'tags.*' => 'string|in:' . implode(',', self::TAGS),
            'area_id' => 'nullable|integer',
            'near' => 'nullable|array',
            'within_miles' => 'nullable|numeric|min:1|max:200',
            'open_at' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q = Location::query()->where('is_usable', 1);
        if (!empty($args['tags'])) {
            $q->whereExists(fn($t) => $t->from('location_tags')->whereColumn('location_tags.location_id', 'locations.id')->whereIn('tag', $args['tags']));
        }
        if (!empty($args['query'])) {
            $words = $args['query'];
            $q->where(fn($w) => $w->where('name', 'like', "%{$words}%")->orWhere('description', 'like', "%{$words}%"));
        }
        if (!empty($args['area_id']) && empty($args['near'])) {
            $q->where('area_id', $args['area_id']);
        }
        $near = !empty($args['near']) ? Geo::resolve($args['near'], $context) : null;
        if ($near) {
            $q->whereProximity($near['point']->lat, $near['point']->lng, $args['within_miles'] ?? 25, true);
        }
        if (!empty($args['open_at'])) {
            $q->whereIsOpen($this->date($args['open_at'], $context)->format('Y-m-d H:i:s'));
        }

        $places = $q->limit($args['limit'] ?? 8)->get();
        $day = $this->date($args['open_at'] ?? null, $context);
        $hours = Hours::forDay($places->pluck('id')->all(), $day);
        $tags = \DB::table('location_tags')->whereIn('location_id', $places->pluck('id'))->get()->groupBy('location_id');

        $results = $places->map(fn(Location $place) => array_filter([
            'location_id' => $place->id,
            'name' => $place->name,
            'area_id' => $place->area_id,
            'address' => trim(implode(', ', array_filter([$place->address, $place->city]))) ?: null,
            'phone' => $place->phone,
            'url' => $place->url,
            'tags' => isset($tags[$place->id]) ? $tags[$place->id]->pluck('tag')->diff(['point_of_interest', 'establishment'])->values()->all() : null,
            'hours_' . $day->format('l') => $hours[$place->id] ?? 'no hours on record',
            'miles_away' => $near && $place->latitude ? round(TrailProfiler::miles($near['point']->lat, $near['point']->lng, (float) $place->latitude, (float) $place->longitude), 1) : null,
            'description' => self::clip($place->description, 250),
        ], fn($v) => $v !== null && $v !== []))->values()->all();

        return ToolResult::ok([
            'places' => $results,
            'near' => $near['label'] ?? null,
            'note' => $results ? null : 'Nothing in our places data matched. Say so; don\'t invent businesses.',
        ], $results ? [ToolResult::source('Places data', null, ToolResult::OUR_DATA)] : []);
    }
}
