<?php

namespace App\Console\Commands;

use App\Laz\Writing\Services\Resource;
use App\Models\Event;
use App\Models\Location;
use App\Models\Scopes\UsableScope;
use App\Models\Trail;
use App\Services\ResourceService;
use Illuminate\Console\Command;

class CombineResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combine:resources {--events} {--locations} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combine resources that are the same from different sources.';

    private $resourceService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->resourceService = new ResourceService();

        $doEvents = $this->option('events');
        $doLocations = $this->option('locations');
        $doTrails = $this->option('trails');

        if ($doEvents !== false) {
            $this->processEvents();
        }

        if ($doLocations !== false) {
            $this->processLocations();
        }

        if ($doTrails !== false) {
            $this->processTrails(Trail::class, 'trails');
        }
    }

    private function calculateSimilarity($str1, $str2) {
        // Calculate the Levenshtein distance between the two strings
        $levenshteinDistance = levenshtein($str1, $str2);

        // Calculate the similarity score
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = 1 - ($levenshteinDistance / $maxLength);

        return round($similarity, 5);
    }

    protected function processTrails($model, $table)
    {
        $distance = 0.1;
        do {
            $resources = $model::withoutGlobalScope(UsableScope::class)
                ->selectRaw($table . '.id, ' . $table . '.source_type AS source, ' . $table . '.source_id, ' . $table . '.name, ' . $table . '.latitude, ' . $table . '.longitude, other_resource.id AS other_id, other_resource.source_type AS other_source_type, other_resource.name AS other_name')
                ->from($table)
                ->crossJoin($table . ' as other_resource')
                ->where($table . '.is_usable', 1)
                ->where('other_resource.is_usable', 1)
                ->whereRaw($table . '.id != other_resource.id')
                ->whereRaw($table . '.source_type != other_resource.source_type')
                ->whereRaw($table . '.source_id != other_resource.source_id')
                ->whereRaw('
                    (6371 * ACOS(
                        COS(RADIANS(' . $table . '.latitude)) * COS(RADIANS(other_resource.latitude)) * COS(RADIANS(' . $table . '.longitude - other_resource.longitude)) +
                        SIN(RADIANS(' . $table . '.latitude)) * SIN(RADIANS(other_resource.latitude))
                    )) * 0.621371 <= ?', [$distance])
                ->distinct()
                ->get();

            $matches = [];
            foreach ($resources as $resource) {
                $matches[] = (object) [
                    'id_1' => $resource->id,
                    'name_1' => $resource->name,
                    'id_2' => $resource->other_id,
                    'name_2' => $resource->other_name,
                    'similarity' => $this->calculateSimilarity(str_replace('Trail', '', $resource->name), str_replace('Trail', '', $resource->other_name)),
                ];
            }
            usort($matches, function($a, $b) {
                return strnatcmp($b->similarity, $a->similarity);
            });

            $match = $matches[0];
            if ($match->similarity > 0.3) {
                $this->info('Combining ' . $match->id_1 . ' ' . $match->name_1 . ' | ' . $match->id_2 . ' ' . $match->name_2);
                $this->resourceService->combineTrails($match->id_1, $match->id_2);
            }
        } while ($matches[0]->similarity > 0.3);
    }
}
