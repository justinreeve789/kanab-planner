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
use App\Models\TrailMerge;
use App\Repositories\ResourceRepository;
use App\ResourceEnum;
use App\Services\OpenAIService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Meilisearch\Client;
use Throwable;

class SearchService
{
    const BASE_RANKING_SCORE_THRESHOLD = 0.5;
    const MAX_RANKING_SCORE_THRESHOLD = 0.9;

    public float $rankingScoreThreshold = self::BASE_RANKING_SCORE_THRESHOLD;
    public $client;
    public array $results = [];
    public array $stats = [];

    public function __construct()
    {
        $this->client = new Client(Config::get('scout.meilisearch.host'), Config::get('scout.meilisearch.key'));
    }

    public function search($query, $areaIds, $resourceTables, $page, $num) {
        // Create a list of the $areaIds and $resourceTables we can query Meilisearch with, and sanitize the data.
        $areaIdList = implode(',', array_filter(array_map('intval', $areaIds), function($value) {
            return $value > 0;
        }));
        $results = [];

        foreach ($resourceTables as $resourceTable) {
            $hits = $this->client->index($resourceTable)->search($query, [
                'attributesToSearchOn' => ['name'],
                'matchingStrategy' => 'frequency',
                'rankingScoreThreshold' => $this->calculateRankingScoreThreshold($areaIds, $resourceTables),
                'showRankingScore' => true,
                'filter' => 'area_id IN [' . $areaIdList . ']',
            ])->getHits();
            if ($hits) {
                foreach ($hits as $hit) {
                    $stats = [
                        'resource_table' => $resourceTable,
                        'id' => $hit['id'],
                        'score' => $hit['_rankingScore'],
                    ];
                    $this->stats[$resourceTable . '_' . $hit['id']] = $stats;
                    $result = $hit;
                    $result['result_id'] = $resourceTable . '_' . $hit['id'];
                    $results[$result['result_id']] = $result;
                }
            }
        }

        // Sort by the ranking score.
        usort($results, function($a, $b) {
            return $b['_rankingScore'] <=> $a['_rankingScore'];
        });

        $this->results = $results;
        return $results;
    }

    private function prepareResourceModelWithParams($resource, $query, $areaIds = [], $resourceTables = []) {
        /** @var $resource Resource */
        $queryObj = $resource::search($query)
            ->whereIn('area_id', $areaIds)
            ->options(['rankingScoreThreshold' => $this->calculateRankingScoreThreshold($areaIds, $resourceTables)]);

        // Don't include events that have already passed.
        if (get_class($resource) === Event::class) {
//            $queryObj->where('start_date', '>=', date('Y-m-d 00:00:00'));
        }
        return $queryObj;
    }

    /**
     * General searches should use a higher ranking score threshold so there's fewer results for each filter that has to
     * be processed. If we're searching more specific results, such as trails in one area, use a lower ranking score
     * threshold so more results are shown.
     */
    private function calculateRankingScoreThreshold($areaIds = [], $resourceTables = []) {
        $this->rankingScoreThreshold += min(0.2, log(count($areaIds) + 1) / log(7) * 0.2);
        $this->rankingScoreThreshold += min(0.2, log(count($resourceTables) + 1) / log(5) * 0.2);
        return min($this->rankingScoreThreshold, self::MAX_RANKING_SCORE_THRESHOLD);
    }
}
