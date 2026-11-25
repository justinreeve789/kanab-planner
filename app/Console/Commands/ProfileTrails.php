<?php

namespace App\Console\Commands;

use App\Chat\Data\TrailProfiler;
use App\Models\TrailGeoJson;
use App\Models\TrailProfile;
use Illuminate\Console\Command;

/**
 * Computes trail_profiles (grades, steep sections, total climb) from every trail's GPS track. Tracks that haven't
 * changed are skipped unless --force.
 */
class ProfileTrails extends Command
{
    protected $signature = 'trails:profile {--trail=* : Only these trail ids} {--force : Recompute unchanged tracks}';
    protected $description = 'Compute steepness profiles for trails from their GPS tracks';

    public function handle(): int
    {
        $profiler = TrailProfiler::fromConfig();
        $query = TrailGeoJson::query()->select(['trail_id', 'geojson']);
        if ($ids = $this->option('trail')) {
            $query->whereIn('trail_id', $ids);
        }

        $counts = ['computed' => 0, 'unchanged' => 0, 'unusable' => 0];
        $query->orderBy('trail_id')->chunk(50, function($rows) use ($profiler, &$counts) {
            $existing = TrailProfile::whereIn('trail_id', $rows->pluck('trail_id'))->pluck('source_hash', 'trail_id');
            foreach ($rows as $row) {
                if (!$this->option('force') && ($existing[$row->trail_id] ?? null) === hash('sha256', $row->geojson)) {
                    $counts['unchanged']++;
                    continue;
                }
                if (TrailProfile::store($row->trail_id, $row->geojson, $profiler)) {
                    $counts['computed']++;
                } else {
                    $counts['unusable']++;
                    $this->line("Trail {$row->trail_id}: track has no usable elevation.");
                }
            }
        });

        $this->info(sprintf('%d computed, %d unchanged, %d without usable elevation.', $counts['computed'], $counts['unchanged'], $counts['unusable']));
        return self::SUCCESS;
    }
}
