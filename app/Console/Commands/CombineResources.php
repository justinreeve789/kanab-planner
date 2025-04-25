<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Location;
use App\Models\Scopes\DataScope;
use App\Models\Trail;
use App\ResourceEnum;
use App\Services\DataService;
use App\Services\ResourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use function Laravel\Prompts\confirm;

class CombineResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combine:resources {--events} {--locations} {--trails=}';

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
        $ids = null;
        if ($doTrails !== null) {
            $ids = explode(',', $doTrails);
            if (count($ids) !== 2 || !is_numeric($ids[0]) || !is_numeric($ids[1]))  {
                echo 'You must have exactly 2 ids.
';
                exit(0);
            }
        }

        if ($doEvents !== false) {
            $this->processEvents();
        }

        if ($doLocations !== false) {
            $this->processLocations();
        }

        if ($doTrails !== false) {
            $this->processTrails($ids);
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

    protected function processTrails($ids) {
        $this->processResources(Trail::class, 'trails', 5, 0.75, $ids);
        $this->processResources(Trail::class, 'trails', 0.1, 0.3, $ids);
    }

    protected function processResources($model, $table, $maxDistance, $minSimilarity, $ids = null)
    {
        if ($ids !== null) {
            $resource1 = $model::withoutGlobalScope(DataScope::class)->where('id', $ids[0])->first();
            $resource2 = $model::withoutGlobalScope(DataScope::class)->where('id', $ids[1])->first();
            $confirm = confirm(
                label: 'Combine these ' . $table . '? ' . $resource1->name . ' (' . $resource1->id . ') & ' . $resource2->name . ' (' . $resource2->id . ')',
                default: false,
            );
            if ($confirm) {
                $this->resourceService->combineResources($resource1->id, $resource2->id, $model);
            }
            return;
        }
        $matches = [];

        if ($model === ResourceEnum::LOCATIONS_CLASS || $model === ResourceEnum::TRAILS_CLASS) {
            $resources = $model::withoutGlobalScope(DataScope::class)
                ->selectRaw(
                    $table . '.id, ' . $table . '.name, ' . $table . '.latitude, ' . $table .
                    '.longitude, other_resource.id AS other_id, other_resource.name AS other_name'
                )
                ->from($table)
                ->crossJoin($table . ' as other_resource')
                ->where($table . '.is_usable', 1)
                ->where('other_resource.is_usable', 1)
                ->where($table . '.is_merged', 0)
                ->where('other_resource.is_merged', 0)
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
                $id = 'id1_' . $resource->id . '_id2_' . $resource->other_id;
                $compareId = 'id1_' . $resource->other_id . '_id2_' . $resource->id;
                if (!isset($matches[$id]) && !isset($matches[$compareId])) {
                    $similarity = $this->calculateSimilarity($resource->name, $resource->other_name);
                    if ($similarity >= $minSimilarity) {
                        $matches[$id] = (object)[
                            'id_1' => $resource->id,
                            'name_1' => $resource->name,
                            'id_2' => $resource->other_id,
                            'name_2' => $resource->other_name,
                            'similarity' => $similarity,
                        ];
                    }
                }
            }
        }

        if ($model === ResourceEnum::LOCATIONS_CLASS) {
            $resources = $model::withoutGlobalScope(DataScope::class)
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
                ->where($table . '.is_merged', 0)
                ->where('other_resource.is_merged', 0)
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

        foreach ($matches as $match) {
            if ($match->similarity > $minSimilarity) {
                $confirm = confirm(
                    label: 'Combine these ' . $table . '? ' . $match->name_1 . ' (' . $match->id_1 . ') & ' . $match->name_2 . ' (' . $match->id_2 . ')',
                    default: false,
                );
                if ($confirm) {
                    $this->resourceService->combineResources($match->id_1, $match->id_2, $model);
                }
            }
        }
    }
}
