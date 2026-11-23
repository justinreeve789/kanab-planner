<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        return Restaurant::with(['data', 'location', 'tags'])->get();
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
