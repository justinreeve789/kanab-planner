<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\Hours;
use App\Chat\Data\Knowledge;
use App\Chat\Data\TrailProfiler;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FindRestaurants extends BaseTool
{
    public function name(): string
    {
        return 'find_restaurants';
    }

    public function description(): string
    {
        return 'Find restaurants and cafes by area or distance from a place, open at a given time, under a price, and '
            . 'matching words like a cuisine, diet (vegetarian, vegan, gluten-free), "kids" or "coffee". Word matches '
            . 'come with the review or description excerpt that mentions them, so you can say how sure you are. Also '
            . 'returns whether they take reservations, today\'s hours and the usual busyness at that hour.';
    }

    public function parameters(): array
    {
        return self::object([
            'query' => ['type' => 'string', 'description' => 'Name, cuisine, diet or other words, e.g. "vegan", "pizza", "kids menu".'],
            'restaurant_id' => ['type' => 'integer', 'description' => 'Look up one restaurant.'],
            'area_id' => ['type' => 'integer'],
            'near' => self::object(Geo::placeSchema()),
            'within_miles' => ['type' => 'number', 'description' => 'With near, default 20.'],
            'open_at' => ['type' => 'string', 'description' => 'Local date and time, e.g. 2026-12-06 20:30.'],
            'max_price' => ['type' => 'number', 'description' => 'Average price per person, USD.'],
            'limit' => ['type' => 'integer', 'description' => 'Default 6.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'query' => 'nullable|string|max:80',
            'restaurant_id' => 'nullable|integer',
            'area_id' => 'nullable|integer',
            'near' => 'nullable|array',
            'within_miles' => 'nullable|numeric|min:1|max:200',
            'open_at' => 'nullable|date',
            'max_price' => 'nullable|numeric|min:0',
            'limit' => 'nullable|integer|min:1|max:15',
        ]);

        // Restaurants and locations both carry an unqualified is_usable scope, so filter locations in a subquery
        // rather than joining them.
        $q = Restaurant::query()->with('location')->whereNotNull('restaurants.location_id');
        if (!empty($args['restaurant_id'])) {
            $q->where('restaurants.id', $args['restaurant_id']);
        }
        $near = !empty($args['near']) ? Geo::resolve($args['near'], $context) : null;
        if ($near) {
            $q->whereIn('restaurants.location_id', Location::query()
                ->whereProximity($near['point']->lat, $near['point']->lng, $args['within_miles'] ?? 20)->select('id'));
        } elseif (!empty($args['area_id'])) {
            $q->whereIn('restaurants.location_id', Location::query()->where('area_id', $args['area_id'])->select('id'));
        }
        if (!empty($args['open_at'])) {
            $q->whereIsOpen($this->date($args['open_at'], $context)->format('Y-m-d H:i:s'));
        }
        if (isset($args['max_price'])) {
            $q->where(fn($w) => $w->whereNull('restaurants.average_price')->orWhere('restaurants.average_price', '<=', $args['max_price']));
        }

        $words = self::words($args['query'] ?? '');
        $candidates = $q->limit($words ? 200 : ($args['limit'] ?? 6))->get();

        // Word matching against name, description and reviews; keep the excerpt as evidence.
        $evidence = [];
        if ($words) {
            $reviews = DB::table('resource_reviews')
                ->where('is_usable', 1)
                ->where(fn($w) => $w->where(fn($r) => $r->where('resource_table', 'restaurants')->whereIn('resource_id', $candidates->pluck('id')))
                    ->orWhere(fn($r) => $r->where('resource_table', 'locations')->whereIn('resource_id', $candidates->pluck('location_id'))))
                ->get(['resource_table', 'resource_id', 'comment', 'posted_at']);
            $candidates = $candidates->filter(function(Restaurant $r) use ($words, $reviews, &$evidence) {
                $texts = [['our record', $r->name . '. ' . $r->description . '. ' . $r->location?->description]];
                foreach ($reviews as $review) {
                    if (($review->resource_table === 'restaurants' && $review->resource_id == $r->id)
                        || ($review->resource_table === 'locations' && $review->resource_id == $r->location_id)) {
                        $texts[] = ['review ' . substr((string) $review->posted_at, 0, 10), $review->comment];
                    }
                }
                foreach ($texts as [$from, $text]) {
                    foreach (Knowledge::sentences(Knowledge::plain((string) $text)) as $sentence) {
                        foreach ($words as $word) {
                            if (preg_match('/\b' . preg_quote($word, '/') . '/i', $sentence)) {
                                $evidence[$r->id][] = ['from' => $from, 'text' => Str::limit($sentence, 200)];
                                break 2;
                            }
                        }
                    }
                }
                return !empty($evidence[$r->id]);
            })->take($args['limit'] ?? 6);
        }

        $day = $this->date($args['open_at'] ?? null, $context);
        $hours = Hours::forDay($candidates->pluck('location_id')->filter()->all(), $day);
        $hour = (int) $day->format('G');
        $busy = DB::table('resource_busy')->where('resource_table', 'locations')
            ->whereIn('resource_id', $candidates->pluck('location_id'))
            ->where('day_of_week', (int) $day->format('w'))->where('hour', $hour)
            ->orderByDesc('collected_date')->get()->unique('resource_id')->keyBy('resource_id');

        if ($near) {
            $candidates = $candidates->sortBy(fn(Restaurant $r) => $r->location?->latitude
                ? TrailProfiler::miles($near['point']->lat, $near['point']->lng, (float) $r->location->latitude, (float) $r->location->longitude) : 999)->values();
        }
        $results = $candidates->map(fn(Restaurant $r) => array_filter([
            'restaurant_id' => $r->id,
            'name' => $r->name,
            'area_id' => $r->location?->area_id,
            'address' => $r->location ? trim(implode(', ', array_filter([$r->location->address, $r->location->city]))) : null,
            'average_price_usd' => $r->average_price !== null ? (float) $r->average_price : null,
            'takes_reservations' => $r->reservable === null ? null : (bool) $r->reservable,
            'dine_in' => $r->dine_in === null ? null : (bool) $r->dine_in,
            'hours_' . $day->format('l') => $hours[$r->location_id] ?? 'no hours on record',
            'usual_busyness_at_' . $day->format('ga') => !empty($args['open_at']) && isset($busy[$r->location_id]) ? $busy[$r->location_id]->percentage . '% of peak' : null,
            'miles_away' => $near && $r->location?->latitude ? round(TrailProfiler::miles($near['point']->lat, $near['point']->lng, (float) $r->location->latitude, (float) $r->location->longitude), 1) : null,
            'url' => $r->location?->url,
            'phone' => $r->location?->phone,
            'matches_because' => $evidence[$r->id] ?? null,
            'description' => self::clip($r->description, 200),
        ], fn($v) => $v !== null && $v !== ''))->values()->all();

        return ToolResult::ok([
            'restaurants' => $results,
            'near' => $near['label'] ?? null,
            'note' => $results ? null : 'No restaurants in our data matched. Say so, and offer to widen the search.',
        ], $results ? [ToolResult::source('Restaurant records and reviews', null, ToolResult::OUR_DATA)] : []);
    }

    /** Meaningful words from the query, with diet spellings normalized. */
    private static function words(string $query): array
    {
        $query = mb_strtolower($query);
        $query = str_replace(['gluten free', 'gluten-free'], 'gluten', $query);
        $words = preg_split('/[^a-z0-9]+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['the', 'and', 'with', 'for', 'food', 'restaurant', 'restaurants', 'options', 'place', 'places', 'good', 'best', 'near', 'friendly'];
        return array_values(array_filter($words, fn($w) => strlen($w) >= 3 && !in_array($w, $stop, true)));
    }
}
