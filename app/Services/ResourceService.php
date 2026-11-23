<?php

namespace App\Services;

use App\Models\Blog\Post;
use App\Models\Event;
use App\Models\Image;
use App\Models\Itinerary\Activity;
use App\Models\Location;
use App\Models\OpenAIPrompt;
use App\Models\Resource;
use App\Models\ResourceData;
use App\Models\ResourceMerge;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Scopes\DataScope;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Models\TrailImage;
use App\Repositories\ResourceRepository;
use App\ResourceEnum;
use App\Services\OpenAIService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResourceService
{
    private $repository;

    public function __construct()
    {
        $this->openAIService = new OpenAIService();
    }

    public function getRepository()
    {
        if (!isset($this->repository)) {
            $this->repository = new ResourceRepository();
        }
        return $this->repository;
    }

    public function combineResources($resource1, $resource2, $model = null) {
        if (!$resource1 || !$resource2) {
            return false;
        }

        if (!is_object($resource1)) {
            $resource1 = $model::withoutGlobalScope(DataScope::class)->where('id', $resource1)->first();
        }

        if (!is_object($resource2)) {
            $resource2 = $model::withoutGlobalScope(DataScope::class)->where('id', $resource2)->first();
        }

        if (get_class($resource1) !== get_class($resource2)) {
            throw new \Exception('Not the same model.');
        }

        switch (get_class($resource1)) {
            case ResourceEnum::LOCATIONS_CLASS:
                $this->combineLocations($resource1, $resource2);
                break;
            case ResourceEnum::TRAILS_CLASS:
                $this->combineTrails($resource1, $resource2);
                break;
            default:
        }
    }

    public function combineLocations($resource1, $resource2) {
        $primary = $resource1;
        $secondary = $resource2;

        // Use ChatGPT to create a new description if both have description text.
        $resourceText = ResourceText::where('resource_table', 'locations')->where('resource_id', $primary->id)->first();
        if (!$resourceText) {
            if (($primary->description && $secondary->description) || ($primary->description_alt && $secondary->description_alt)) {
                $raw = $primary->name . ' ' . $primary->description . ' ' . $primary->description_alt . ' ' .
                    $secondary->description . ' ' . $secondary->description_alt;
                $clean = $this->openAIService->rewriteTextWithOpenAI($raw);
                $json = json_decode($clean);
                if ($json) {
                    $json->combined_primary = $primary->id;
                    $json->combined_secondary = $secondary->id;
                    $clean = json_encode($json);

                    $resourceText = new ResourceText([
                        'resource_table' => 'locations',
                        'resource_id' => $primary->id,
                        'type' => 'combine',
                        'raw' => $raw,
                        'clean' => $clean,
                    ]);
                    $resourceText->save();
                }
            }
        }

        // Combine/coalesce the records.
        $this->getRepository()->combineResources(Location::class, $primary, $secondary);
        $secondary->is_usable = 0;
        $secondary->save();

        // Update images.
        foreach ($secondary->images as $image) {
            $image->location_id = $primary->id;
            $image->updateOrInsert(['location_id' => $primary->id]);
        }
    }

    public function combineTrails($resource1, $resource2) {
        // Use whichever trail has the GeoJSON data as the primary.
        $geoJson = TrailGeoJson::whereIn('trail_id', [$resource1->id, $resource2->id])->first();
        if ($geoJson) {
            $primaryId = $geoJson->trail_id;
            $secondaryId = ($resource1->id === $primaryId) ? $resource2->id : $resource1->id;
        } else {
            $primaryId = $resource1->id;
            $secondaryId = $resource2->id;
        }

        $primary = Trail::withoutGlobalScope(DataScope::class)->with(['area', 'images'])->where('id', $primaryId)->first();
        $secondary = Trail::withoutGlobalScope(DataScope::class)->with(['area', 'images'])->where('id', $secondaryId)->first();

        // Create a new record that combines $primary and $secondary.
        try {
            DB::beginTransaction();

            // Combine/coalesce the records.
            $newResource = $this->getRepository()->combineResources(Trail::class, $primary, $secondary);

            // Combine the resource text.
            $this->combineResourceText($newResource, $primary, $secondary);

            // Combine the resource data.
            $this->combineResourceData($newResource, $primary, $secondary);

            // Combine the images.
            $this->combineImages($newResource, $primary, $secondary);

            // Update the blog posts.
            Post::where('resource_table', $newResource->getTable())
                ->whereIn('resource_id', [$primary->id, $secondary->id])
                ->update(['resource_id' => $newResource->id]);

            // Update itineraries.
            Activity::where('resource_table', $newResource->getTable())
                ->whereIn('resource_id', [$primary->id, $secondary->id])
                ->update(['resource_id' => $newResource->id]);

            // Combine the OpenAI prompts.
            $this->combineOpenAIPrompts($newResource, $primary, $secondary);

            // Add the geoJSON.
            if ($geoJson) {
                TrailGeoJson::insert([
                    'trail_id' => $newResource->id,
                    'geojson' => $geoJson->geojson,
                    'created_at' => $primary->created_at,
                    'updated_at' => $primary->updated_at,
                ]);
            }

            ResourceMerge::insert([[
                'resource_table' => $newResource->getTable(),
                'merged_from' => $primary->id,
                'merged_to' => $newResource->id,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'resource_table' => $newResource->getTable(),
                'merged_from' => $secondary->id,
                'merged_to' => $newResource->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);

            $primary->is_usable = 0;
            $primary->save();
            $secondary->is_usable = 0;
            $secondary->save();

            DB::commit();
            return ['new' => $newResource, 'primary' => $primary, 'secondary' => $secondary];
        } catch (Throwable $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function combineResourceData(Model $newResource, Model $primary, Model $secondary) {
        $primaryData = ResourceData::where(['resource_table' => $primary->getTable(), 'resource_id' => $primary->id])->first();
        $secondaryData = ResourceData::where(['resource_table' => $secondary->getTable(), 'resource_id' => $secondary->id])->first();
        ResourceData::insert([
            'resource_table' => $newResource->getTable(),
            'resource_id' => $newResource->id,
            'activity_type_id' => $primaryData->activity_type_id ?? $secondaryData->activity_type_id,
            'adventurousness' => max($primaryData->adventurousness ?? null, $secondaryData->adventurousness ?? null),
            'solitariness' => max($primaryData->solitariness ?? null, $secondaryData->solitariness ?? null),
            'strenuousness' => max($primaryData->strenuousness ?? null, $secondaryData->strenuousness ?? null),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Use ChatGPT to create a new description if both have description text.
     *
     * @param Model $newResource
     * @param Model $primary
     * @param Model $secondary
     * @return void
     */
    public function combineResourceText(Model $newResource, Model $primary, Model $secondary) {
        if (get_class($newResource) === ResourceEnum::TRAILS_CLASS &&
            get_class($primary) === ResourceEnum::TRAILS_CLASS &&
            get_class($secondary) === ResourceEnum::TRAILS_CLASS) {
            $texts = ResourceText::where('resource_table', $newResource->getTable())
                ->whereIn('resource_id', [$primary->id, $secondary->id])
                ->get();

            $newTexts = [];
            foreach ($texts as $text) {
                if (!isset($newTexts[$text->type])) {
                    $newTexts[$text->type] = [];
                }
                $newTexts[$text->type][] = $text->clean;
            }

            foreach (array_keys($newTexts) as $type) {
                $newClean = '';
                if (count($newTexts[$type]) > 1) {
                    $clean = '';
                    foreach ($newTexts[$type] as $newText) {
                        $clean .= $newText . ' ';
                    }
                    if ($type === 'short') {
                        $newClean = $this->openAIService->createShortDescriptionForResourceWithWithOpenAI($clean);
                    } else {
                        $newClean = $this->openAIService->rewriteTextWithOpenAI($clean);
                    }
                } else {
                    $newClean = $newTexts[$type][0];
                }

                if ($newClean !== '') {
                    $json = json_decode($newClean);
                    if ($json) {
                        $json->merged_primary = $primary->id;
                        $json->merged_secondary = $secondary->id;
                        $newClean = json_encode($json);
                        ResourceText::insert([
                            'resource_table' => $newResource->getTable(),
                            'resource_id' => $newResource->id,
                            'type' => $type,
                            'clean' => $newClean,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * TODO: Check for duplicates. Store extra metadata and hashes of the images for comparison.
     *
     * @param Model $newResource
     * @param Model $primary
     * @param Model $secondary
     * @return void
     */
    public function combineImages(Model $newResource, Model $primary, Model $secondary) {
        if (get_class($newResource) === ResourceEnum::TRAILS_CLASS &&
            get_class($primary) === ResourceEnum::TRAILS_CLASS &&
            get_class($secondary) === ResourceEnum::TRAILS_CLASS) {
            $primaryImages = TrailImage::where('trail_id', $primary->id)->get();
            $secondaryImages = TrailImage::where('trail_id', $secondary->id)->get();
            $images = $primaryImages->merge($secondaryImages);
            foreach ($images as $image) {
                $trailImage = TrailImage::where(['trail_id' => $newResource->id, 'local_url' => $image->local_url])->first();
                if (!$trailImage) {
                    $newValues = [
                        'id' => null,
                        'trail_id' => $newResource->id,
                        'created_at' => date('Y-m-d H:i:s', strtotime($image->created_at)),
                        'updated_at' => date('Y-m-d H:i:s', strtotime($image->updated_at)),
                    ];
                    TrailImage::insertWithValues($newValues, $image->toArray());
                }
            }
        }
    }

    public function combineOpenAIPrompts(Model $newResource, Model $primary, Model $secondary) {
        $primaryPrompts = OpenAIPrompt::where(['source_table' => $primary->getTable(), 'source_id' => $primary->id])->get();
        $secondaryPrompts = OpenAIPrompt::where(['source_table' => $secondary->getTable(), 'source_id' => $secondary->id])->get();
        $prompts = $primaryPrompts->merge($secondaryPrompts);
        foreach ($prompts as $prompt) {
            OpenAIPrompt::insertWithValues([
                'id' => null,
                'source_id' => $newResource->id,
                'created_at' => date('Y-m-d H:i:s', strtotime($prompt->created_at)),
                'updated_at' => date('Y-m-d H:i:s', strtotime($prompt->updated_at)),
            ], $prompt->toArray());
        }
    }

    public function calculateSimilarity($resource1, $resource2): ?float {
        return $this->calculateSimilarityBetweenStrings($resource1->name, $resource2->name);
    }

    private function calculateSimilarityBetweenStrings($str1, $str2): float {
        // Calculate the Levenshtein distance between the two strings.
        $levenshteinDistance = levenshtein($str1, $str2);

        // Calculate the similarity score.
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = 1 - ($levenshteinDistance / $maxLength);

        return round($similarity, 5);
    }
}
