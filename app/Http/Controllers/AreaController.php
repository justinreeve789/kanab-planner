<?php

namespace App\Http\Controllers;

use App\Models\Trail;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     */
    public function getTrails(Request $request, int $id)
    {
        // Precede a $sort parameter with '-' to sort by descending order, for example: ?sort=-name
        $sort = $request->get('sort');
        $orderBy = ($sort && $sort !== '') ? (substr($sort, 0, 1) === '-' ? substr($sort, 1) . ' DESC' : $sort) : 'name';
        return Trail::with(['area', 'data', 'images'])->where('area_id', $id)->orderByRaw($orderBy)->get();
    }
}
