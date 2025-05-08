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
    const FEATURED_RESULT_COUNT = 4;

    public float $rankingScoreThreshold = self::BASE_RANKING_SCORE_THRESHOLD;

    public function search(Request $request) {
        $query = $request->get('q') ?? '';
        $areaIds = $request->get('ar') ?? [];
        $resourceTables = $request->get('rt') ?? [];
        $page = $request->get('page') ?? 0;
        $num = $request->get('num') ?? self::DEFAULT_NUM_PER_PAGE;
        $searchService = new SearchService();
        return $searchService->search($query, $areaIds, $resourceTables, $page, $num);
    }

    public function featured(Request $request, string $resourceTable) {
        $areaIds = $request->get('area_ids');
        /** @var Resource $model */
        $model = Resource::getModelForResourceTable($resourceTable);
        return $model::getFeatured($areaIds, self::FEATURED_RESULT_COUNT);
    }
}
