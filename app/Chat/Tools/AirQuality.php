<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Chat\Data\Geo;
use App\Chat\Live\AirNowClient;

class AirQuality extends BaseTool
{
    public function __construct(private AirNowClient $airNow)
    {
    }

    public function name(): string
    {
        return 'air_quality';
    }

    public function description(): string
    {
        return 'Air quality forecast (AQI and category, e.g. smoke from wildfires) near a place for a date, from EPA AirNow.';
    }

    public function parameters(): array
    {
        return self::object([
            'place' => self::object(Geo::placeSchema()),
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
        if (!$this->airNow->enabled()) {
            return ToolResult::unavailable('Air quality isn\'t connected here.', AirNowClient::PAGE);
        }
        $date = $this->date($args['date'] ?? null, $context)->toDateString();
        $forecast = $this->airNow->forecast($place['point']->lat, $place['point']->lng, $date);
        if ($forecast === null) {
            return ToolResult::unavailable('AirNow could not be reached right now.', AirNowClient::PAGE);
        }
        if (!$forecast) {
            return ToolResult::unavailable('AirNow has no forecast for this area and date.', AirNowClient::PAGE);
        }
        return ToolResult::ok(['place' => $place['label'], 'date' => $date, 'forecast' => $forecast],
            [ToolResult::source('EPA AirNow', AirNowClient::PAGE, ToolResult::LIVE, $date)]);
    }
}
