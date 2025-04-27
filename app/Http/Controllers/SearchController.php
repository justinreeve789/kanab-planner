<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Event;
use App\Models\Resource;
use App\ResourceEnum;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    const BASE_RANKING_SCORE_THRESHOLD = 0.5;
    const MAX_RANKING_SCORE_THRESHOLD = 0.9;
    const DEFAULT_NUM_PER_PAGE = 10;

    public float $rankingScoreThreshold = self::BASE_RANKING_SCORE_THRESHOLD;

    public function search(Request $request) {
        $query = $request->get('q') ?? '';
        $areaIds = $request->get('ar') ? explode(',', $request->get('ar')) : [];
        $resourceTables = $request->get('rt') ? explode(',', $request->get('rt')) : [];
        $page = $request->get('page') ?? 0;
        $num = $request->get('num') ?? self::DEFAULT_NUM_PER_PAGE;

        $searchService = new SearchService();
        $results = $searchService->search($query, $areaIds, $resourceTables, $page, $num);
        return $results;
//        $results = [];
//        foreach ($resourceTables as $resourceTable) {
//            $resource = new (Resource::getModelForResourceTable($resourceTable))();
//            $queryObj = $this->prepareResourceModelWithParams($resource, $query, $areaIds, $resourceTables);
//        }
//        return $queryObj->rawSearch();
//        return $queryObj->paginate($num, 'page', $page);
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
