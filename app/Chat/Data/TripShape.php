<?php

namespace App\Chat\Data;

use App\Models\Trail;

/**
 * How far and how much climbing a hike really is. Records and tracks don't agree on what "distance" means: a
 * point-to-point or out-and-back track usually covers one way, while the hike people do is there and back.
 */
final class TripShape
{
    public const LOOP_TYPES = ['loop', 'lollipop'];

    /** Typical moving pace (mph) and climbing penalty (hours per 1,000 ft), slower for young kids and beginners. */
    public const PACE = [
        'carried' => [1.5, 1.0], 'age_4_6' => [1.0, 1.0], 'age_7_9' => [1.3, 1.0], 'age_10_12' => [1.6, 0.8],
        'teen' => [2.0, 0.6], 'beginner' => [1.6, 0.8], 'moderate' => [2.0, 0.6], 'fit' => [2.5, 0.4], 'limited' => [1.0, 1.0],
    ];

    /**
     * @param string $plan 'round_trip' (default: back to the start) or 'one_way' (a shuttle)
     */
    public static function for(Trail $trail, ?array $profile, string $band = 'moderate', string $plan = 'round_trip'): array
    {
        $type = strtolower((string) $trail->distance_type);
        $recordMiles = $trail->distance_miles !== null ? (float) $trail->distance_miles : null;
        $trackMiles = $profile['track_miles'] ?? null;
        // Whole route drawn: a loop, or an out-and-back track that comes back to the trailhead.
        $complete = $profile ? $profile['returns_to_start'] : (in_array($type, self::LOOP_TYPES, true) || $type === 'out and back');

        if ($complete) {
            $miles = $recordMiles ?? $trackMiles;
            $oneWayMiles = in_array($type, self::LOOP_TYPES, true) || $miles === null ? null : $miles / 2;
            $gain = $profile['gain_ft'] ?? $trail->incline_ft;
            $plan = in_array($type, self::LOOP_TYPES, true) ? 'loop' : 'round_trip';
            $note = $plan === 'loop' ? 'Loop: total distance.' : 'Out and back: total distance there and back.';
        } else {
            // A one-way track (most "point to point" segments): there and back doubles it, unless there's a shuttle.
            $oneWayMiles = $trackMiles ?? $recordMiles;
            if ($plan === 'one_way') {
                $miles = $oneWayMiles;
                $gain = $profile['gain_ft'] ?? $trail->incline_ft;
                $note = 'One way, with a car or shuttle at the far end.';
            } else {
                $miles = $oneWayMiles !== null ? $oneWayMiles * 2 : null;
                $gain = $profile ? $profile['gain_ft'] + $profile['loss_ft'] : ($trail->incline_ft !== null ? $trail->incline_ft + (int) $trail->decline_ft : null);
                $note = 'There and back to the trailhead (' . ($oneWayMiles !== null ? round($oneWayMiles, 1) . ' miles each way' : 'distance unknown') . '). The listed distance is one way.';
            }
        }

        [$mph, $perThousand] = self::PACE[$band] ?? self::PACE['moderate'];
        $hours = $miles !== null ? $miles / $mph + ($gain ?? 0) / 1000 * $perThousand : null;

        return [
            'route_type' => $trail->distance_type ?: 'unknown',
            'plan' => $plan,
            'miles' => $miles !== null ? round($miles, 1) : null,
            'one_way_miles' => $oneWayMiles !== null ? round($oneWayMiles, 1) : null,
            'gain_ft' => $gain !== null ? (int) $gain : null,
            'hours' => $hours !== null ? round($hours, 1) : null,
            'distance_note' => $note,
        ];
    }
}
