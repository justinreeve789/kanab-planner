<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Trail;
use App\Models\TrailGeoJson;
use App\Services\TrailService;
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
        $trailGeoJson = TrailGeoJson::where('trail_id', $id)->first();
        if ($trailGeoJson) {
            return $trailGeoJson->geojson;
        }
    }

    public function downloadGeoJson(Request $request, int $id)
    {
        $json = $this->getGeoJsonData($request, $id);
        if (!$json) {
            abort(404);
        }
        // download() expects a file path; this is the file *contents*.
        return response()->streamDownload(function() use ($json) {
            echo $json;
        }, $id . '.geojson', ['Content-Type' => 'application/geo+json']);
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

    public function analyzeGeoJson(Request $request, int $id)
    {
        $averageSpeed = (float) ($request->get('average_speed') ?? Trail::AVERAGE_HIKING_SPEED_MPH);
        if ($averageSpeed <= 0 || $averageSpeed > 30) {
            return response()->json(['errors' => ['average_speed' => ['Must be between 0 and 30 mph.']]], 422);
        }
        $json = $this->getGeoJsonData($request, $id);
        if ($json) {
            $trailService = new TrailService();
            try {
                return $trailService->analyzeTrail($json, $averageSpeed);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['errors' => ['geojson' => [$e->getMessage()]]], 422);
            }
        }
    }
}
