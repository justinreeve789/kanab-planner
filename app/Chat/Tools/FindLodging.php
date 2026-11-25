<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\TrailProfiler;
use App\Chat\Live\RidbClient;
use Illuminate\Support\Facades\DB;

class FindLodging extends BaseTool
{
    public function __construct(private RidbClient $ridb)
    {
    }

    public function name(): string
    {
        return 'find_lodging';
    }

    public function description(): string
    {
        return 'Find hotels, motels, rentals, campgrounds and RV parks by area or near a place, with check-in and '
            . 'check-out times and links. Federal campgrounds from Recreation.gov come with whether they are reservable '
            . 'and a booking link (availability itself is on that page).';
    }

    public function parameters(): array
    {
        return self::object([
            'kind' => ['type' => 'string', 'enum' => ['any', 'hotel', 'campground', 'rv']],
            'query' => ['type' => 'string', 'description' => 'Name or words like "showers", "hookups".'],
            'area_id' => ['type' => 'integer'],
            'near' => self::object(Geo::placeSchema()),
            'within_miles' => ['type' => 'number', 'description' => 'With near, default 25.'],
            'limit' => ['type' => 'integer', 'description' => 'Default 8.'],
        ]);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'kind' => 'nullable|in:any,hotel,campground,rv',
            'query' => 'nullable|string|max:80',
            'area_id' => 'nullable|integer',
            'near' => 'nullable|array',
            'within_miles' => 'nullable|numeric|min:1|max:200',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);
        $kind = $args['kind'] ?? 'any';
        $tags = match ($kind) {
            'campground' => ['campground'],
            'rv' => ['rv_park', 'campground'],
            'hotel' => ['lodging'],
            default => ['lodging', 'campground', 'rv_park'],
        };

        $q = DB::table('locations')
            ->leftJoin('accommodations', 'accommodations.location_id', '=', 'locations.id')
            ->leftJoin('accommodation_types', 'accommodation_types.id', '=', 'accommodations.accommodation_type_id')
            ->where('locations.is_usable', 1)
            ->where(fn($w) => $w->whereNotNull('accommodations.id')
                ->orWhereExists(fn($t) => $t->from('location_tags')->whereColumn('location_tags.location_id', 'locations.id')->whereIn('tag', $tags)))
            ->select(['locations.id as location_id', 'locations.name', 'locations.area_id', 'locations.address', 'locations.city',
                'locations.url', 'locations.phone', 'locations.latitude', 'locations.longitude', 'locations.description',
                'accommodations.id as accommodation_id', 'accommodation_types.name as type', 'accommodations.check_in', 'accommodations.check_out']);
        if ($kind !== 'any') {
            $q->whereExists(fn($t) => $t->from('location_tags')->whereColumn('location_tags.location_id', 'locations.id')->whereIn('tag', $tags));
        }
        if (!empty($args['query'])) {
            $q->where(fn($w) => $w->where('locations.name', 'like', "%{$args['query']}%")->orWhere('locations.description', 'like', "%{$args['query']}%"));
        }
        $near = !empty($args['near']) ? Geo::resolve($args['near'], $context) : null;
        if ($near) {
            $p = $near['point'];
            $q->whereRaw('3959 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(locations.latitude)) * COS(RADIANS(locations.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(locations.latitude)))) <= ?', [$p->lat, $p->lng, $p->lat, $args['within_miles'] ?? 25]);
        } elseif (!empty($args['area_id'])) {
            $q->where('locations.area_id', $args['area_id']);
        }

        $rows = $q->limit(($args['limit'] ?? 8) * 2)->get()->unique('location_id')->take($args['limit'] ?? 8);
        $results = $rows->map(fn($row) => array_filter([
            'location_id' => $row->location_id,
            'accommodation_id' => $row->accommodation_id,
            'name' => $row->name,
            'type' => $row->type,
            'area_id' => $row->area_id,
            'address' => trim(implode(', ', array_filter([$row->address, $row->city]))) ?: null,
            'check_in' => $row->check_in ? date('g:i A', strtotime($row->check_in)) : null,
            'check_out' => $row->check_out ? date('g:i A', strtotime($row->check_out)) : null,
            'miles_away' => $near && $row->latitude ? round(TrailProfiler::miles($near['point']->lat, $near['point']->lng, (float) $row->latitude, (float) $row->longitude), 1) : null,
            'url' => $row->url,
            'phone' => $row->phone,
            'description' => self::clip($row->description, 200),
        ], fn($v) => $v !== null && $v !== ''))->values()->all();

        $sources = $results ? [ToolResult::source('Lodging records', null, ToolResult::OUR_DATA)] : [];
        $federal = null;
        if (in_array($kind, ['any', 'campground', 'rv'], true) && $this->ridb->enabled()) {
            $center = $near['point'] ?? (Geo::resolve(['area_id' => $args['area_id'] ?? 0])['point'] ?? null);
            if ($center) {
                $federal = $this->ridb->facilities($args['query'] ?? 'campground', $center->lat, $center->lng, (int) ($args['within_miles'] ?? 30), 6);
                if ($federal) {
                    $sources[] = ToolResult::source('Recreation.gov', 'https://www.recreation.gov/', ToolResult::OFFICIAL);
                }
            }
        }

        return ToolResult::ok([
            'lodging' => $results,
            'federal_campgrounds' => $federal,
            'near' => $near['label'] ?? null,
            'note' => 'Prices and availability are not in our data; link to the booking page.',
        ], $sources);
    }
}
