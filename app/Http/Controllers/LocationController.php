<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return Location::with('area')->get();
    }

    public function getLocation(Request $request)
    {
        $id = $request->get('id');
        return Location::with('area')->where('id', $id)->first();
    }
}
