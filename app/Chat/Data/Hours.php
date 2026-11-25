<?php

namespace App\Chat\Data;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Opening hours from location_hours (day_of_week 0 = Sunday; a close before the open means after midnight). */
final class Hours
{
    /** @return array<int, string> location id => "7:00 AM–9:00 PM" (or "closed") for $date's weekday */
    public static function forDay(array $locationIds, CarbonInterface $date): array
    {
        if (!$locationIds) {
            return [];
        }
        $rows = DB::table('location_hours')->whereIn('location_id', $locationIds)->get();
        $byLocation = $rows->groupBy('location_id');
        $weekday = (int) $date->format('w');
        $out = [];
        foreach ($locationIds as $id) {
            if (!isset($byLocation[$id])) {
                continue;
            }
            $today = $byLocation[$id]->where('day_of_week', $weekday);
            $out[$id] = $today->isEmpty()
                ? 'closed'
                : $today->map(fn($h) => self::time($h->time_open) . '–' . self::time($h->time_close))->implode(', ');
        }
        return $out;
    }

    private static function time(string $time): string
    {
        return date('g:i A', strtotime($time));
    }
}
