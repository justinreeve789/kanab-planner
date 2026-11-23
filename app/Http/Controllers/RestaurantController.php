<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Paginated: ?page=2&per_page=50 (max 100).
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 50)));
        return Restaurant::with(['data', 'location', 'tags'])->orderBy('restaurants.id')->paginate($perPage);
    }

    public function getRestaurantsInArea(Request $request, int $areaId)
    {
        return Restaurant::with(['data', 'location', 'tags'])
            ->whereHas('location', fn($q) => $q->where('area_id', $areaId))
            ->get();
    }

    public function getRestaurant(Request $request, int $id)
    {
        return Restaurant::with(['data', 'location', 'tags'])->where('id', $id)->first();
    }
}
