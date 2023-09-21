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

    public function getRestaurant(Request $request, int $id)
    {
        return Restaurant::with(['data', 'location', 'tags'])->where('id', $id)->first();
    }
}
