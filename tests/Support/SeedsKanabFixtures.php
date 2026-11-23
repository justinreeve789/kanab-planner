<?php

namespace Tests\Support;

use App\Models\ActivityType;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

/**
 * Small, deterministic slice of the Kanab dataset layered on the reference rows the migrations seed (areas,
 * activity types, accommodation #1): a ladder of trails per area (short/easy to long/very difficult), restaurants
 * with opening hours, driving times, and a few trails that must never show up in an itinerary (unusable,
 * merged-away).
 *
 * Inserts go through the query builder so model events (Scout indexing, etc.) never fire.
 */
trait SeedsKanabFixtures
{
    /** @var array<int, array<int>> area_id => trail ids, ordered short → long */
    protected array $fixtureTrailIds = [];

    /** @var array<int, array<int>> area_id => restaurant ids */
    protected array $fixtureRestaurantIds = [];

    protected int $unusableTrailId;
    protected int $mergedAwayTrailId;

    /** Mirrors the rows the areas migration seeds: id => [name, lat, lng]. */
    public const FIXTURE_AREAS = [
        Area::BRYCE => ['Bryce Canyon', 37.593, -112.187],
        Area::NORTH_RIM => ['Grand Canyon (North Rim)', 36.2116, -112.058],
        Area::KANAB => ['Kanab, Utah', 37.0475, -112.526],
        Area::LAKE_POWELL => ['Lake Powell', 36.9269, -111.475],
        Area::PAGE_AZ => ['Page, Arizona', 36.9147, -111.455],
        Area::ST_GEORGE => ['St. George, Utah', 37.0965, -113.568],
        Area::ZION => ['Zion', 37.2982, -113.026],
    ];

    public const FIXTURE_RESTAURANTS_PER_AREA = 6;

    /** [distance_miles, average_hours, difficulty, adventurousness, solitariness, strenuousness] */
    public const FIXTURE_TRAIL_LADDER = [
        [0.8, 0.5, 'Easy', 0.30, 0.30, 0.20],
        [2.5, 1.5, 'Easy/Intermediate', 0.45, 0.45, 0.40],
        [4.0, 2.5, 'Intermediate', 0.55, 0.50, 0.55],
        [7.0, 4.0, 'Difficult', 0.70, 0.60, 0.75],
        [14.0, 8.0, 'Very Difficult', 0.90, 0.80, 0.95],
    ];

    protected function seedKanabFixtures(): void
    {
        $now = now();
        $hikingId = (int) ActivityType::where('name', ActivityType::HIKING)->value('id');
        $diningId = (int) ActivityType::where('name', ActivityType::DINING)->value('id');

        // Areas, activity types, accommodation type #1 and accommodation #1 come from the migrations themselves.

        // Driving times back to Kanab, used when deciding whether dinner at home is realistic.
        foreach ([Area::BRYCE => 1.3, Area::NORTH_RIM => 1.5, Area::LAKE_POWELL => 1.3, Area::PAGE_AZ => 1.2,
                     Area::ST_GEORGE => 1.4, Area::ZION => 0.8] as $from => $hours) {
            DB::table('area_distances')->insert([
                'from_area_id' => $from, 'to_area_id' => Area::KANAB,
                'driving_hours' => $hours, 'driving_miles' => $hours * 50,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::FIXTURE_AREAS as $areaId => [$areaName, $lat, $lng]) {
            foreach (self::FIXTURE_TRAIL_LADDER as $i => [$miles, $hours, $difficulty, $adv, $sol, $str]) {
                $trailId = $this->insertTrail([
                    'name' => "{$areaName} Trail " . ($i + 1),
                    'area_id' => $areaId,
                    'latitude' => $lat + 0.01 * ($i + 1),
                    'longitude' => $lng + 0.01 * ($i + 1),
                    'difficulty' => $difficulty,
                    'distance_miles' => $miles,
                    'average_hours' => $hours,
                    'url' => "https://example.test/trails/{$areaId}-{$i}",
                ]);
                $this->insertResourceData('trails', $trailId, $hikingId, $adv, $sol, $str);
                $this->fixtureTrailIds[$areaId][] = $trailId;
            }

            // Enough restaurants per area for lunch + dinner on three away days, each open 07:00–22:00 daily.
            for ($r = 1; $r <= self::FIXTURE_RESTAURANTS_PER_AREA; $r++) {
                $locationId = DB::table('locations')->insertGetId([
                    'name' => "{$areaName} Diner {$r}", 'area_id' => $areaId, 'latitude' => $lat, 'longitude' => $lng,
                    'is_usable' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
                for ($dow = 0; $dow <= 6; $dow++) {
                    DB::table('location_hours')->insert([
                        'location_id' => $locationId, 'day_of_week' => $dow,
                        'time_open' => '07:00:00', 'time_close' => '22:00:00',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $restaurantId = DB::table('restaurants')->insertGetId([
                    'name' => "{$areaName} Diner {$r}", 'source_type' => 'fixture', 'source_id' => "{$areaId}-{$r}",
                    'location_id' => $locationId, 'average_price' => 10 + 5 * $r, 'is_usable' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $this->insertResourceData('restaurants', $restaurantId, $diningId, 0.1, 0.2, 0.0);
                $this->fixtureRestaurantIds[$areaId][] = $restaurantId;
            }
        }

        // Rows that must never be scheduled.
        $this->unusableTrailId = $this->insertTrail([
            'name' => 'Closed Kanab Trail', 'area_id' => Area::KANAB, 'difficulty' => 'Easy',
            'distance_miles' => 1.0, 'average_hours' => 0.5, 'is_usable' => 0,
        ]);
        $this->insertResourceData('trails', $this->unusableTrailId, $hikingId, 0.5, 0.5, 0.5);

        $this->mergedAwayTrailId = $this->insertTrail([
            'name' => 'Duplicate Zion Trail 1', 'area_id' => Area::ZION, 'difficulty' => 'Easy',
            'distance_miles' => 0.8, 'average_hours' => 0.5, 'is_usable' => 0,
        ]);
        $this->insertResourceData('trails', $this->mergedAwayTrailId, $hikingId, 0.3, 0.3, 0.2);
        DB::table('resource_merges')->insert([
            'resource_table' => 'trails', 'merged_from' => $this->mergedAwayTrailId,
            'merged_to' => $this->fixtureTrailIds[Area::ZION][0], 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    protected function insertTrail(array $attributes): int
    {
        $now = now();
        return DB::table('trails')->insertGetId(array_merge([
            'source_type' => 'fixture',
            'source_id' => uniqid('', true),
            'unique' => 0,
            'is_usable' => 1,
            'is_merged' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }

    protected function insertResourceData(string $table, int $id, int $activityTypeId, float $adv, float $sol, float $str): void
    {
        DB::table('resource_data')->insert([
            'resource_table' => $table, 'resource_id' => $id, 'activity_type_id' => $activityTypeId,
            'adventurousness' => $adv, 'solitariness' => $sol, 'strenuousness' => $str,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** All usable fixture trail ids. */
    protected function usableTrailIds(): array
    {
        return array_merge(...array_values($this->fixtureTrailIds));
    }
}
