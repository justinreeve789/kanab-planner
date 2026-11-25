<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Data\Knowledge;
use App\Chat\Live\NpsClient;
use App\Chat\Live\NwsClient;
use App\Models\Area;
use App\Models\ResourceStatus;
use App\Models\RoadEvent;
use App\Services\Routing\Point;

class Status extends BaseTool
{
    public function __construct(private NpsClient $nps, private NwsClient $nws)
    {
    }

    public function name(): string
    {
        return 'status';
    }

    public function description(): string
    {
        return 'Is it open, and is anything wrong there? For a trail, place or area on a date: seasonal area closures, '
            . 'the record\'s own open/closed status, current National Park Service alerts (closures, rockfall, toxic '
            . 'algae, fire restrictions, shuttle changes), National Weather Service warnings at that spot, and road '
            . 'closures or roadwork nearby. Use before recommending anything for a specific date.';
    }

    public function parameters(): array
    {
        return self::object([
            'place' => self::object(Geo::placeSchema(false)),
            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default today.'],
        ], ['place']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['place' => 'required|array', 'date' => 'nullable|date']);
        $place = Geo::resolve($args['place'], $context);
        if (!$place) {
            return ToolResult::unavailable('Could not locate that place.');
        }
        $date = $this->date($args['date'] ?? null, $context)->toDateString();
        $area = $place['area_id'] ? Area::find($place['area_id']) : null;
        $areaConfig = $area ? config("chat.areas.{$area->id}", []) : [];
        $sources = [];
        $data = ['place' => $place['label'], 'date' => $date];

        if ($area) {
            $data['area'] = $area->name;
            $data['area_seasonally_closed'] = $area->isClosedOn($date);
            if ($description = $area->closureDescription()) {
                $data['area_season'] = "Closed every year {$description}.";
            }
        }

        foreach (['trail_id' => 'trails', 'location_id' => 'locations', 'restaurant_id' => 'restaurants'] as $key => $table) {
            if (!empty($args['place'][$key])) {
                $status = ResourceStatus::where(['resource_table' => $table, 'resource_id' => $args['place'][$key]])->latest('updated_at')->first();
                if ($status) {
                    $data['record_status'] = ['open' => (bool) $status->is_open, 'message' => $status->message, 'checked' => $status->updated_at?->toDateString()];
                    $sources[] = ToolResult::source('Status for ' . $place['label'], $status->info_url, ToolResult::OFFICIAL, $status->updated_at?->toDateString());
                }
            }
        }

        $parks = $areaConfig['parks'] ?? [];
        if ($parks) {
            $alerts = $this->nps->alerts($parks);
            if ($alerts === null) {
                $data['park_alerts'] = $this->nps->enabled() ? 'Could not reach the National Park Service right now.' : 'Park alerts are not connected (no NPS key).';
            } else {
                // Alerts naming this record come first.
                $names = Knowledge::searchNames($place['label']);
                usort($alerts, fn($a, $b) => self::mentions($b, $names) <=> self::mentions($a, $names));
                $data['park_alerts'] = array_map(fn($a) => $a + ['mentions_this_place' => self::mentions($a, $names)], array_slice($alerts, 0, 10));
                $sources[] = ToolResult::source('National Park Service alerts', $areaConfig['conditions_url'] ?? null, ToolResult::LIVE, $context->now->format('M j, g:i A'));
            }
            $data['official_conditions_page'] = $areaConfig['conditions_url'] ?? null;
        }

        $weatherAlerts = $this->nws->alerts($place['point']->lat, $place['point']->lng);
        $data['weather_alerts'] = $weatherAlerts ?? 'Could not reach the National Weather Service right now.';
        if ($weatherAlerts) {
            $sources[] = ToolResult::source('National Weather Service alerts', NwsClient::pageFor($place['point']->lat, $place['point']->lng), ToolResult::LIVE);
        }

        $p = $place['point'];
        $roads = RoadEvent::activeOn($date)->around($p, $p, 25)->limit(8)->get();
        $data['road_events_within_25_miles'] = $roads->map(fn(RoadEvent $e) => $e->summary())->all();
        if ($roads->isNotEmpty()) {
            $sources[] = ToolResult::source('UDOT and AZ511 road events', null, ToolResult::LIVE);
        }

        return ToolResult::ok($data, $sources);
    }

    private static function mentions(array $alert, array $names): bool
    {
        foreach ($names as $name) {
            if (mb_stripos(($alert['title'] ?? '') . ' ' . ($alert['description'] ?? ''), $name) !== false) {
                return true;
            }
        }
        return false;
    }
}
