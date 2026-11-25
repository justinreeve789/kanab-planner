<?php

namespace App\Chat\Live;

/**
 * Recreation.gov's RIDB API: federal facilities (campgrounds, trailheads, permit offices) and permit entrances.
 * Needs a free key (RIDB_API_KEY). Availability isn't in RIDB; answers link to the booking page instead.
 */
class RidbClient extends LiveClient
{
    public const BASE = 'https://ridb.recreation.gov/api/v1';

    public function enabled(): bool
    {
        return (bool) config('chat.sources.ridb_api_key');
    }

    private function get(string $endpoint, array $query): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        return $this->getJson(self::BASE . $endpoint, $query, self::minutes('permits'), ['apikey' => config('chat.sources.ridb_api_key')]);
    }

    /** Facilities near a point matching $query, e.g. "Angels Landing" or "campground". */
    public function facilities(string $query, float $lat, float $lng, int $radiusMiles = 40, int $limit = 8): ?array
    {
        $data = $this->get('/facilities', [
            'query' => $query, 'latitude' => $lat, 'longitude' => $lng, 'radius' => $radiusMiles, 'limit' => $limit,
        ]);
        if ($data === null) {
            return null;
        }
        return array_map(fn($facility) => [
            'id' => $facility['FacilityID'] ?? null,
            'name' => $facility['FacilityName'] ?? null,
            'type' => $facility['FacilityTypeDescription'] ?? null,
            'reservable' => $facility['Reservable'] ?? null,
            'summary' => mb_substr(strip_tags($facility['FacilityDescription'] ?? ''), 0, 500),
            'fees' => mb_substr(strip_tags($facility['FacilityUseFeeDescription'] ?? ''), 0, 300),
            'url' => isset($facility['FacilityID']) ? self::bookingUrl($facility) : null,
        ], $data['RECDATA'] ?? []);
    }

    private static function bookingUrl(array $facility): string
    {
        $type = strtolower($facility['FacilityTypeDescription'] ?? '');
        $path = str_contains($type, 'permit') ? 'permits' : (str_contains($type, 'ticket') || str_contains($type, 'tour') ? 'ticket/facility' : 'camping/campgrounds');
        return "https://www.recreation.gov/{$path}/{$facility['FacilityID']}";
    }
}
