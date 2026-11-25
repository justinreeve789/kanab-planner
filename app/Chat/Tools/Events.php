<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Live\NpsClient;
use Illuminate\Support\Facades\DB;

class Events extends BaseTool
{
    public function __construct(private NpsClient $nps)
    {
    }

    public function name(): string
    {
        return 'events';
    }

    public function description(): string
    {
        return 'Events between two dates: festivals, markets, concerts, races, rodeos, star parties and park ranger '
            . 'programs, with times, place, price or free, and links. Filter by area and words.';
    }

    public function parameters(): array
    {
        return self::object([
            'from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
            'to' => ['type' => 'string', 'description' => 'YYYY-MM-DD, at most 31 days after from.'],
            'area_id' => ['type' => 'integer'],
            'query' => ['type' => 'string', 'description' => 'Words in the name or description, e.g. "astronomy", "race", "market".'],
            'free_only' => ['type' => 'boolean'],
            'limit' => ['type' => 'integer', 'description' => 'Default 12.'],
        ], ['from', 'to']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'area_id' => 'nullable|integer',
            'query' => 'nullable|string|max:80',
            'free_only' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:30',
        ]);
        $from = $this->date($args['from'], $context)->startOfDay();
        $to = min($this->date($args['to'], $context)->endOfDay(), $from->addDays(31)->endOfDay());

        $q = DB::table('event_dates')
            ->join('events', 'events.id', '=', 'event_dates.event_id')
            ->leftJoin('locations', 'locations.id', '=', 'events.location_id')
            ->where('events.is_usable', 1)
            ->where('event_dates.start_date', '<=', $to)
            ->where(fn($w) => $w->where('event_dates.end_date', '>=', $from)->orWhere(fn($n) => $n->whereNull('event_dates.end_date')->where('event_dates.start_date', '>=', $from)))
            ->orderBy('event_dates.start_date')
            ->select(['events.id', 'events.name', 'events.description', 'events.url', 'events.free', 'events.price',
                'event_dates.start_date', 'event_dates.end_date', 'locations.name as place', 'locations.city', 'locations.area_id']);
        if (!empty($args['area_id'])) {
            $q->where('locations.area_id', $args['area_id']);
        }
        if (!empty($args['query'])) {
            $q->where(fn($w) => $w->where('events.name', 'like', "%{$args['query']}%")->orWhere('events.description', 'like', "%{$args['query']}%"));
        }
        if (!empty($args['free_only'])) {
            $q->where('events.free', 1);
        }

        $events = $q->limit($args['limit'] ?? 12)->get()->map(fn($e) => array_filter([
            'event_id' => $e->id,
            'name' => html_entity_decode($e->name, ENT_QUOTES),
            'starts' => date('D M j, g:i A', strtotime($e->start_date)),
            'ends' => $e->end_date ? date('D M j, g:i A', strtotime($e->end_date)) : null,
            'place' => trim(implode(', ', array_filter([$e->place, $e->city]))) ?: null,
            'area_id' => $e->area_id,
            'free' => $e->free === null ? null : (bool) $e->free,
            'price' => $e->price,
            'url' => $e->url,
            'description' => self::clip($e->description, 220),
        ], fn($v) => $v !== null && $v !== ''))->values()->all();

        $sources = $events ? [ToolResult::source('Event listings', null, ToolResult::OUR_DATA)] : [];
        $ranger = null;
        $parks = !empty($args['area_id']) ? config("chat.areas.{$args['area_id']}.parks", []) : [];
        if ($parks && $this->nps->enabled()) {
            $ranger = $this->nps->events($parks[0], $from->toDateString(), $to->toDateString());
            if ($ranger) {
                $sources[] = ToolResult::source('National Park Service events', NpsClient::parkUrl($parks[0], 'planyourvisit/calendar.htm'), ToolResult::OFFICIAL);
            }
        }

        return ToolResult::ok([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'events' => $events,
            'park_programs' => $ranger,
            'note' => $events || $ranger ? null : 'No events found for those dates in our listings.',
        ], $sources);
    }
}
