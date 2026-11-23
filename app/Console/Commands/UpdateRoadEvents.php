<?php

namespace App\Console\Commands;

use App\Models\RoadEvent;
use App\Services\RoadEvents\Five11Feed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Refresh road_events from each configured 511 feed (config/routing.php). A feed that fails keeps its last events;
 * one that answers replaces them, so events that have ended disappear.
 */
class UpdateRoadEvents extends Command
{
    protected $signature = 'update:road-events';

    protected $description = 'Fetch road closures, roadwork and incidents from the UDOT and AZ511 feeds';

    public function handle(): int
    {
        $bounds = config('routing.road_events.bounds');
        $configured = array_filter(config('routing.road_events.sources'), fn($source) => filled($source['key']));
        if (!$configured) {
            $this->info('No road event feeds are configured (set UDOT_API_KEY and/or AZ511_API_KEY).');
            return self::SUCCESS;
        }

        $failed = false;
        foreach ($configured as $name => $source) {
            try {
                $rows = (new Five11Feed($name, $source['url'], $source['key']))->fetch($bounds);
            } catch (\Throwable $e) {
                $this->error("{$name}: {$e->getMessage()}");
                $failed = true;
                continue;
            }

            DB::transaction(function() use ($name, $rows) {
                $now = now();
                foreach (array_chunk($rows, 200) as $chunk) {
                    RoadEvent::upsert(
                        array_map(fn($row) => $row + ['created_at' => $now, 'updated_at' => $now], $chunk),
                        ['source', 'external_id'],
                        [...array_values(array_diff(array_keys($chunk[0]), ['source', 'external_id'])), 'updated_at'],
                    );
                }
                RoadEvent::where('source', $name)->whereNotIn('external_id', array_column($rows, 'external_id'))->delete();
            });
            $this->info(sprintf('%s: %d events in the region.', $name, count($rows)));
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
