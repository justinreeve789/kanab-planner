<?php

namespace Tests\Data;

use App\Models\Area;
use App\Models\Trail;
use App\Services\TrailService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;

class DatasetIntegrityTest extends DataTestCase
{
    public function test_unusable_trails_arent_retrieved(): void
    {
        $usable = DB::table('trails')->where('is_usable', 1)->count();
        $this->assertLessThan(DB::table('trails')->count(), $usable, 'The dataset should include some unusable trails.');
        $this->assertSame($usable, Trail::count());
        $this->assertSame(0, Trail::where('is_usable', 0)->count());
    }

    public function test_merged_away_trails_are_unusable(): void
    {
        $mergedFrom = DB::table('resource_merges')->where('resource_table', 'trails')->pluck('merged_from');
        $this->assertSame(0, Trail::whereIn('id', $mergedFrom)->count());
    }

    public function test_every_area_has_hikeable_trails(): void
    {
        foreach (Area::all() as $area) {
            $this->assertGreaterThan(
                0,
                Trail::where('area_id', $area->id)->whereNotNull('distance_miles')->count(),
                "{$area->name} has no usable trails with a distance."
            );
        }
    }

    public function test_every_usable_trail_belongs_to_a_known_area(): void
    {
        $this->assertSame(0, Trail::whereNotIn('area_id', Area::pluck('id'))->count());
    }

    /**
     * Trail 444's stored GeoJSON was an empty FeatureCollection; update:geojson now refuses to store one.
     */
    public function test_every_stored_trail_path_has_coordinates(): void
    {
        $service = new TrailService();
        $empty = [];
        foreach (DB::table('trail_geojson')->get() as $row) {
            if (count($service->extractTrailPoints($row->geojson)) < 2) {
                $empty[] = $row->trail_id;
            }
        }
        $this->assertSame([], $empty);
    }

    /**
     * Every usable trail is scored (php artisan update:data --trails). Unscored trails would still be pickable
     * (sortByDeviation LEFT JOINs with defaults), but would rank on defaults instead of their own numbers.
     */
    public function test_every_hikeable_trail_has_resource_data(): void
    {
        $missing = DB::table('trails')
            ->where('is_usable', 1)->whereNotNull('distance_miles')
            ->whereNotExists(fn($q) => $q->from('resource_data')
                ->whereColumn('resource_data.resource_id', 'trails.id')
                ->where('resource_data.resource_table', 'trails'))
            ->count();
        $this->assertSame(0, $missing);
    }

    /**
     * LocationService::updateBusynessForLocation() skipped Sunday (array_search() returned 0, which failed a
     * truthiness check). Fixed in code; the stored data needs a re-scrape with a Google API key
     * (php artisan update:busy), so this stays a known issue until database/dumps/resource_busy.sql is refreshed.
     */
    #[Group('known-issues')]
    public function test_busyness_was_collected_for_sundays(): void
    {
        $this->assertGreaterThan(0, DB::table('resource_busy')->where('day_of_week', 0)->count());
    }
}
