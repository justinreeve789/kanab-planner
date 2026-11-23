<?php

namespace Tests\Support;

use App\Models\Area;

/**
 * Helpers for poking at the JSON GET /api/v1/itinerary returns.
 */
trait ItineraryAssertions
{
    protected function generateItinerary(array $params = []): array
    {
        $params += ['start_date' => '2027-06-01', 'duration_days' => 5];
        $response = $this->getJson('/api/v1/itinerary?' . http_build_query($params));
        $response->assertSuccessful();
        return $response->json();
    }

    /**
     * @return array<int, array{day: array, period: int, activity: array}>
     */
    protected function activitiesOf(array $itinerary): array
    {
        $rows = [];
        foreach ($itinerary['days'] as $day) {
            foreach ($day['periods'] as $period) {
                foreach ($period['activities'] as $activity) {
                    $rows[] = ['day' => $day, 'period' => (int) $period['type'], 'activity' => $activity];
                }
            }
        }
        return $rows;
    }

    protected function activitiesOfDay(array $day): array
    {
        $rows = [];
        foreach ($day['periods'] as $period) {
            foreach ($period['activities'] as $activity) {
                $rows[] = ['period' => (int) $period['type'], 'activity' => $activity];
            }
        }
        return $rows;
    }

    protected function typeOf(array $activity): ?string
    {
        return $activity['activity_type']['name'] ?? null;
    }

    protected function resourceIdsOfTable(array $itinerary, string $table): array
    {
        $ids = [];
        foreach ($this->activitiesOf($itinerary) as $row) {
            if (($row['activity']['resource_table'] ?? null) === $table) {
                $ids[] = $row['activity']['resource_id'];
            }
        }
        return $ids;
    }

    /** Days that are neither the arrival (check-in) nor departure (check-out) day. */
    protected function fullDays(array $itinerary): array
    {
        return array_values(array_filter($itinerary['days'], function($day) {
            foreach ($this->activitiesOfDay($day) as $row) {
                if (in_array($this->typeOf($row['activity']), ['checking-in', 'checking-out'], true)) {
                    return false;
                }
            }
            return true;
        }));
    }

    protected function milesBetweenAreas(int $fromAreaId, int $toAreaId): float
    {
        $from = Area::findOrFail($fromAreaId);
        $to = Area::findOrFail($toAreaId);
        // Same great-circle formula CustomQueryBuilder::whereProximity() uses.
        [$lat1, $lng1, $lat2, $lng2] = array_map('deg2rad', [$from->latitude, $from->longitude, $to->latitude, $to->longitude]);
        $cos = cos($lat1) * cos($lat2) * cos($lng2 - $lng1) + sin($lat1) * sin($lat2);
        return 3959 * acos(min(1, max(-1, $cos)));
    }
}
