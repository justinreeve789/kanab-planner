<?php

namespace App\Chat\Live;

/**
 * National Park Service API (developer.nps.gov): alerts, park basics (fees, hours), things to do and events. Needs a
 * free key (NPS_API_KEY).
 */
class NpsClient extends LiveClient
{
    public const BASE = 'https://developer.nps.gov/api/v1';

    public function enabled(): bool
    {
        return (bool) config('chat.sources.nps_api_key');
    }

    private function get(string $endpoint, array $query): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        return $this->getJson(self::BASE . $endpoint, $query, self::minutes('nps'), ['X-Api-Key' => config('chat.sources.nps_api_key')]);
    }

    public function alerts(array $parkCodes): ?array
    {
        $data = $this->get('/alerts', ['parkCode' => implode(',', $parkCodes), 'limit' => 50]);
        if ($data === null) {
            return null;
        }
        return array_map(fn($alert) => [
            'park' => $alert['parkCode'] ?? null,
            'category' => $alert['category'] ?? null,
            'title' => $alert['title'] ?? null,
            'description' => mb_substr(strip_tags($alert['description'] ?? ''), 0, 500),
            'url' => $alert['url'] ?: null,
            'updated' => $alert['lastIndexedDate'] ?? null,
        ], $data['data'] ?? []);
    }

    public function park(string $parkCode): ?array
    {
        $data = $this->get('/parks', ['parkCode' => $parkCode, 'fields' => 'entranceFees,entrancePasses,operatingHours']);
        $park = $data['data'][0] ?? null;
        if (!$park) {
            return null;
        }
        return [
            'name' => $park['fullName'] ?? null,
            'url' => $park['url'] ?? null,
            'fees' => array_map(fn($fee) => ['title' => $fee['title'] ?? null, 'cost' => $fee['cost'] ?? null, 'description' => mb_substr($fee['description'] ?? '', 0, 300)], $park['entranceFees'] ?? []),
            'passes' => array_map(fn($pass) => ['title' => $pass['title'] ?? null, 'cost' => $pass['cost'] ?? null], $park['entrancePasses'] ?? []),
            'hours' => array_map(fn($hours) => ['name' => $hours['name'] ?? null, 'description' => mb_substr($hours['description'] ?? '', 0, 300)], array_slice($park['operatingHours'] ?? [], 0, 3)),
            'weather_info' => mb_substr($park['weatherInfo'] ?? '', 0, 400),
        ];
    }

    public function thingsToDo(string $parkCode, string $query): ?array
    {
        $data = $this->get('/thingstodo', ['parkCode' => $parkCode, 'q' => $query, 'limit' => 5]);
        if ($data === null) {
            return null;
        }
        return array_map(fn($item) => [
            'title' => $item['title'] ?? null,
            'summary' => mb_substr(strip_tags($item['shortDescription'] ?? ''), 0, 400),
            'duration' => $item['duration'] ?? null,
            'pets' => $item['arePetsPermitted'] ?? null,
            'reservation' => $item['isReservationRequired'] ?? null,
            'fees' => $item['doFeesApply'] ?? null,
            'accessibility' => mb_substr(strip_tags($item['accessibilityInformation'] ?? ''), 0, 300),
            'url' => $item['url'] ?? null,
        ], $data['data'] ?? []);
    }

    public function events(string $parkCode, string $from, string $to): ?array
    {
        $data = $this->get('/events', ['parkCode' => $parkCode, 'dateStart' => $from, 'dateEnd' => $to, 'pageSize' => 30]);
        if ($data === null) {
            return null;
        }
        return array_map(fn($event) => [
            'title' => $event['title'] ?? null,
            'dates' => $event['dates'] ?? [],
            'times' => $event['times'] ?? [],
            'location' => $event['location'] ?? null,
            'free' => $event['isfree'] ?? null,
            'summary' => mb_substr(strip_tags($event['description'] ?? ''), 0, 300),
            'url' => $event['infourl'] ?? null,
        ], $data['data'] ?? []);
    }

    public static function parkUrl(string $parkCode, string $page = ''): string
    {
        return "https://www.nps.gov/{$parkCode}/" . ltrim($page, '/');
    }
}
