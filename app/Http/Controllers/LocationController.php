<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return Location::with(['area', 'data', 'hours', 'text'])->get();
    }

    public function getLocation(Request $request, int $id)
    {
        return Location::with(['area', 'data', 'hours', 'text'])->where('id', $id)->first();
    }

}
