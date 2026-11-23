<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Paginated: ?page=2&per_page=50 (max 100).
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 50)));
        return Location::with(['area', 'data', 'hours', 'text'])->orderBy('locations.id')->paginate($perPage);
    }

    public function getLocationsInArea(Request $request, int $areaId)
    {
        return Location::with(['area', 'data', 'hours', 'text'])->where('area_id', $areaId)->get();
    }

    public function getLocation(Request $request, int $id)
    {
        return Location::with(['area', 'data', 'hours', 'text'])->where('id', $id)->first();
    }

}
