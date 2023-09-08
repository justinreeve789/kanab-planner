<?php

namespace App\Http\Controllers;

use App\Models\Trail;
use Illuminate\Http\Request;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        return Trail::with('area')->get();
    }

    public function getTrail(Request $request)
    {
        $id = $request->get('id');
        return Trail::with('area')->where('id', $id)->first();
    }
}
