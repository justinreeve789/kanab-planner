<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Image;
use App\Models\Location;
use App\Models\ResourceText;
use App\Models\Restaurant;
use App\Models\Scopes\UsableScope;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Repositories\ResourceRepository;
use App\Services\OpenAIService;
use Illuminate\Database\Eloquent\Model;

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
            $resource1 = $model::withoutGlobalScope(UsableScope::class)->where('id', $resource1)->first();
        }

        if (!is_object($resource2)) {
            $resource2 = $model::withoutGlobalScope(UsableScope::class)->where('id', $resource2)->first();
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
        $this->getRepository()->combineResources('locations', $primary, $secondary);
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
        $primary = Trail::withoutGlobalScope(UsableScope::class)->with(['area', 'images'])->where('id', $primaryId)->first();
        $secondary = Trail::withoutGlobalScope(UsableScope::class)->with(['area', 'images'])->where('id', $secondaryId)->first();

        // Use ChatGPT to create a new description if both have description text.
        $resourceText = ResourceText::where('resource_table', 'trails')->where('resource_id', $primary->id)->first();
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
                        'resource_table' => 'trails',
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
        $this->getRepository()->combineResources('trails', $primary, $secondary);
        $secondary->is_usable = 0;
        $secondary->save();

        // Update images.
        foreach ($secondary->images as $image) {
            $image->trail_id = $primary->id;
            $image->updateOrInsert(['trail_id' => $primary->id]);
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
