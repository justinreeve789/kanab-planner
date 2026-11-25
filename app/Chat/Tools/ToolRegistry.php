<?php

namespace App\Chat\Tools;

use InvalidArgumentException;

final class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /** Every tool the assistant ships with, in the order they're offered to the model. */
    public const DEFAULT = [
        SearchTrails::class,
        TrailDetails::class,
        TrailProfileTool::class,
        AssessForHiker::class,
        TrailKnowledge::class,
        SimilarTrails::class,
        FindPlaces::class,
        FindRestaurants::class,
        FindLodging::class,
        Crowds::class,
        SunTimes::class,
        Forecast::class,
        Status::class,
        RiverFlow::class,
        Events::class,
        DriveTime::class,
        RoadEvents::class,
        OfficialInfo::class,
        Permits::class,
        AirQuality::class,
        GetItinerary::class,
        EditItinerary::class,
        OfferNewItinerary::class,
        ShowMap::class,
        PresentOptions::class,
    ];

    public function __construct(iterable $tools = [])
    {
        foreach ($tools as $tool) {
            $this->add($tool);
        }
    }

    public static function default(): self
    {
        return new self(array_map(fn($class) => app($class), self::DEFAULT));
    }

    public function add(Tool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): Tool
    {
        return $this->tools[$name] ?? throw new InvalidArgumentException("Unknown tool: {$name}");
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function names(): array
    {
        return array_keys($this->tools);
    }

    /** Definitions in the provider-neutral shape ChatModel expects. */
    public function definitions(): array
    {
        return array_values(array_map(fn(Tool $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'parameters' => $tool->parameters(),
        ], $this->tools));
    }
}
