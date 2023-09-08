<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        return Restaurant::with('location')->with('tags')->get();
    }

    public function getRestaurant(Request $request)
    {
        $id = $request->get('id');
        return Restaurant::with('location')->where('id', $id)->first();
    }
}
