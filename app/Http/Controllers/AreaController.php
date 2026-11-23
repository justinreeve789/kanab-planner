<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Trail;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Columns /area/{id}/trail may be sorted by. The sort parameter used to be passed straight to orderByRaw(),
     * which allowed arbitrary SQL injection (e.g. ?sort=(SELECT SLEEP(5))).
     */
    public const SORTABLE_TRAIL_COLUMNS = [
        'name', 'difficulty', 'distance_miles', 'average_hours', 'elevation_high_ft', 'elevation_low_ft',
        'incline_ft', 'decline_ft', 'average_grade_pt', 'max_grade_pt', 'created_at', 'updated_at',
    ];

    public function index(Request $request)
    {
        return Area::orderBy('name')->get();
    }

    public function getArea(Request $request, int $areaId)
    {
        return Area::findOrFail($areaId);
    }

    /**
     * Precede the sort parameter with '-' to sort in descending order, for example: ?sort=-name
     */
    public function getTrails(Request $request, int $id)
    {
        $sort = (string) $request->get('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (!in_array($column, self::SORTABLE_TRAIL_COLUMNS, true)) {
            return response()->json([
                'errors' => ['sort' => ['Sort must be one of: ' . implode(', ', self::SORTABLE_TRAIL_COLUMNS)]],
            ], 422);
        }

        return Trail::with(['area', 'data', 'images'])
            ->where('area_id', $id)
            ->orderBy('trails.' . $column, $direction)
            ->get();
    }
}
