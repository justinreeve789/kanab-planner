<?php

namespace App\Http\Controllers;

use App\Models\Trail;
use Illuminate\Http\Request;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        return Trail::with(['area', 'data'])->get();
    }

    public function getTrail(Request $request, int $id)
    {
        return Trail::with(['area', 'data'])->where('id', $id)->first();
    }

    public function getGeoJsonData(Request $request, int $id)
    {
        $trail = Trail::where('id', $id)->first();
        if ($trail) {
            return $trail->getGeoJsonData();
        }
    }

    public function downloadGeoJson(Request $request, int $id)
    {
        $trail = Trail::where('id', $id)->first();
        if ($trail) {
            $geoJsonFile = storage_path('geojson/' . $trail->id . '-' . $trail->source_id . '.geojson');
            if (file_exists($geoJsonFile)) {
                return response()->download($geoJsonFile, pathinfo($geoJsonFile, PATHINFO_BASENAME));
            }
        }
    }

    public function getGpxData(Request $request, int $id)
    {
        $trail = Trail::where('id', $id)->first();
        if ($trail) {
            return $trail->getGpxData();
        }
    }

    public function downloadGpx(Request $request, int $id)
    {
        $trail = Trail::where('id', $id)->first();
        if ($trail) {
            $gpxFile = storage_path('gpx/' . $trail->id . '-' . $trail->source_id . '.gpx');
            if (file_exists($gpxFile)) {
                return response()->download($gpxFile, pathinfo($gpxFile, PATHINFO_BASENAME));
            }
        }
    }
}
