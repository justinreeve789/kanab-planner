<?php

namespace App\Services\RoadEvents;

use App\Models\RoadEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Reads the "GET Events" endpoint that the state 511 systems share (UDOT Traffic, AZ511 and others run the same
 * platform): roadwork, closures, incidents and special events, with a location and usually start and end times.
 * Each needs a free developer key, and UDOT allows ten calls a minute.
 */
class Five11Feed
{
    private const TYPES = [
        'roadwork' => RoadEvent::ROADWORK,
        'closures' => RoadEvent::CLOSURES,
        'accidentsandincidents' => RoadEvent::INCIDENTS,
        'specialevents' => RoadEvent::SPECIAL_EVENTS,
    ];

    public function __construct(private string $source, private string $url, private string $key)
    {
    }

    /**
     * Events inside $bounds ([south, west, north, east]), as road_events rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $bounds): array
    {
        $events = Http::timeout(20)->acceptJson()
            ->get($this->url, ['key' => $this->key, 'format' => 'json'])
            ->throw()
            ->json();

        $rows = [];
        foreach (is_array($events) ? $events : [] as $event) {
            $row = $this->normalize($event);
            if ($row && self::inside($row, $bounds)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function normalize(array $event): ?array
    {
        $id = $event['ID'] ?? $event['Id'] ?? null;
        $lat = $event['Latitude'] ?? null;
        $lng = $event['Longitude'] ?? null;
        if ($id === null || !is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        $type = self::TYPES[strtolower((string) ($event['EventType'] ?? ''))] ?? RoadEvent::INCIDENTS;
        $secondaryLat = $event['LatitudeSecondary'] ?? null;
        $secondaryLng = $event['LongitudeSecondary'] ?? null;
        $hasSecondary = is_numeric($secondaryLat) && is_numeric($secondaryLng) && ((float) $secondaryLat !== 0.0 || (float) $secondaryLng !== 0.0);

        return [
            'source' => $this->source,
            'external_id' => (string) $id,
            'event_type' => $type,
            'is_full_closure' => filter_var($event['IsFullClosure'] ?? false, FILTER_VALIDATE_BOOL),
            'severity' => $event['Severity'] ?? null,
            'roadway' => self::clean($event['RoadwayName'] ?? null, 255),
            'description' => self::clean($event['Description'] ?? $event['Comment'] ?? null),
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'latitude_secondary' => $hasSecondary ? (float) $secondaryLat : null,
            'longitude_secondary' => $hasSecondary ? (float) $secondaryLng : null,
            'starts_at' => self::time($event['StartDate'] ?? $event['Reported'] ?? null),
            'ends_at' => self::time($event['PlannedEndDate'] ?? $event['EndDate'] ?? null),
        ];
    }

    private static function inside(array $row, array $bounds): bool
    {
        [$south, $west, $north, $east] = $bounds;
        return $row['latitude'] >= $south && $row['latitude'] <= $north && $row['longitude'] >= $west && $row['longitude'] <= $east;
    }

    /** The feeds give Unix seconds (UTC); a few platforms use ISO strings. */
    private static function time($value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        $time = is_numeric($value) ? CarbonImmutable::createFromTimestampUTC((int) $value) : CarbonImmutable::parse($value)->utc();
        return $time->format('Y-m-d H:i:s');
    }

    private static function clean(?string $text, ?int $max = null): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
        if ($text === '') {
            return null;
        }
        return $max ? mb_substr($text, 0, $max) : $text;
    }
}
