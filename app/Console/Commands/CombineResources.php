<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Location;
use App\Models\Scopes\UsableScope;
use App\Models\Trail;
use App\Services\ResourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

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
            $this->processTrails();
        }
    }

    private function calculateSimilarity($str1, $str2) {
        $str1 = str_replace(['Kanab', 'Restaurant', 'Trail'], '', $str1);
        $str2 = str_replace(['Kanab', 'Restaurant', 'Trail'], '', $str2);

        // Calculate the Levenshtein distance between the two strings
        $levenshteinDistance = levenshtein($str1, $str2);

        // Calculate the similarity score
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = 1 - ($levenshteinDistance / $maxLength);

        return round($similarity, 5);
    }

    protected function processEvents() {
        $this->processResources(Event::class, 'events');
    }

    protected function processLocations() {
        $this->processResources(Location::class, 'locations', 0.5, 0.5);
        $this->processResources(Location::class, 'locations', 0.05, 0.35);
    }

    protected function processTrails() {
        $this->processResources(Trail::class, 'trails', 5, 0.75);
        $this->processResources(Trail::class, 'trails', 0.1, 0.3);
    }

    protected function processResources($model, $table, $maxDistance, $minSimilarity)
    {
        do {
            $matches = [];

            if ($model === 'App\\Models\\Location' || $model === 'App\\Models\\Trail') {
                $resources = $model::withoutGlobalScope(UsableScope::class)
                    ->selectRaw(
                        $table . '.id, ' . $table . '.name, ' . $table . '.latitude, ' . $table .
                        '.longitude, other_resource.id AS other_id, other_resource.name AS other_name'
                    )
                    ->from($table)
                    ->crossJoin($table . ' as other_resource')
                    ->where($table . '.is_usable', 1)
                    ->where('other_resource.is_usable', 1)
                    ->whereRaw(Schema::connection('mysql')->hasColumn($table, 'source_type') ? $table . '.source_type != other_resource.source_type' : '1 = 1')
                    ->whereRaw($table . '.id != other_resource.id')
                    ->whereRaw(
                        '
                        (6371 * ACOS(
                            COS(RADIANS(' . $table .
                        '.latitude)) * COS(RADIANS(other_resource.latitude)) * COS(RADIANS(' . $table . '.longitude - other_resource.longitude)) +
                            SIN(RADIANS(' . $table . '.latitude)) * SIN(RADIANS(other_resource.latitude))
                        )) * 0.621371 <= ?',
                        [$maxDistance]
                    )
                    ->distinct()
                    ->get();

                foreach ($resources as $resource) {
                    $matches[] = (object)[
                        'id_1' => $resource->id,
                        'name_1' => $resource->name,
                        'id_2' => $resource->other_id,
                        'name_2' => $resource->other_name,
                        'similarity' => $this->calculateSimilarity($resource->name, $resource->other_name),
                    ];
                }
            }

            if ($model === 'App\\Models\\Location') {
                $resources = $model::withoutGlobalScope(UsableScope::class)
                    ->selectRaw(
                        $table . '.id, ' . $table . '.name, ' . $table .
                        '.address, other_resource.id AS other_id, other_resource.name AS other_name, other_resource.address AS other_address'
                    )
                    ->from($table)
                    ->crossJoin($table . ' as other_resource')
                    ->whereRaw(
                        'COALESCE(
                        (6371 * ACOS(
                            COS(RADIANS(' . $table .
                        '.latitude)) * COS(RADIANS(other_resource.latitude)) * COS(RADIANS(' . $table . '.longitude - other_resource.longitude)) +
                            SIN(RADIANS(' . $table . '.latitude)) * SIN(RADIANS(other_resource.latitude))
                        )) * 0.621371, 0) <= ?',
                        [$maxDistance]
                    )
                    ->where($table . '.is_usable', 1)
                    ->where('other_resource.is_usable', 1)
//                    ->whereRaw($table . '.source_type != other_resource.source_type')
                    ->whereRaw($table . '.id != other_resource.id')
                    ->whereRaw($table . '.name = other_resource.name')
                    ->distinct()
                    ->get();

                foreach ($resources as $resource) {
                    $matches[] = (object)[
                        'id_1' => $resource->id,
                        'name_1' => $resource->name,
                        'id_2' => $resource->other_id,
                        'name_2' => $resource->other_name,
                        'similarity' => $this->calculateSimilarity($resource->address, $resource->other_address),
                    ];
                }
            }

            usort($matches, function($a, $b) {
                return strnatcmp($b->similarity, $a->similarity);
            });

            $match = $matches[0];
            if ($match->similarity > $minSimilarity) {
                echo '<pre>'; print_r($match); echo '</pre>';
                $this->info('Combining ' . $match->id_1 . ' ' . $match->name_1 . ' | ' . $match->id_2 . ' ' . $match->name_2);
                $this->resourceService->combineResources($match->id_1, $match->id_2, $model);
            }
        } while ($matches[0]->similarity > $minSimilarity);
    }
}
