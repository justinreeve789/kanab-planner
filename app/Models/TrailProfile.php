<?php

namespace App\Models;

use App\Chat\Data\TrailProfiler;
use Illuminate\Database\Eloquent\Model;

/**
 * Grades and steep sections for a trail's GPS track (see App\Chat\Data\TrailProfiler). Recomputed by
 * `php artisan trails:profile` when the track changes.
 */
class TrailProfile extends Model
{
    protected $primaryKey = 'trail_id';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'track_miles' => 'float', 'gentle_share' => 'float'];

    public function trail()
    {
        return $this->belongsTo(Trail::class);
    }

    /**
     * The stored profile, computing (and saving) it first if the track is newer. Null when the trail has no usable
     * track.
     */
    public static function forTrail(int $trailId, ?TrailProfiler $profiler = null): ?array
    {
        $geojson = TrailGeoJson::where('trail_id', $trailId)->value('geojson');
        if (!$geojson) {
            return null;
        }
        $hash = hash('sha256', $geojson);
        $stored = self::find($trailId);
        if ($stored && $stored->source_hash === $hash) {
            return $stored->data;
        }
        return self::store($trailId, $geojson, $profiler)?->data;
    }

    public static function store(int $trailId, string $geojson, ?TrailProfiler $profiler = null): ?self
    {
        $data = ($profiler ?? TrailProfiler::fromConfig())->profile($geojson);
        if (!$data) {
            return null;
        }
        return self::updateOrCreate(['trail_id' => $trailId], [
            'track_miles' => $data['track_miles'],
            'gain_ft' => $data['gain_ft'],
            'loss_ft' => $data['loss_ft'],
            'min_elevation_ft' => $data['min_elevation_ft'],
            'max_elevation_ft' => $data['max_elevation_ft'],
            'steepest_sustained_pct' => $data['steepest_sustained']['grade_pct'] ?? null,
            'steepest_pitch_pct' => $data['steepest_pitch']['grade_pct'] ?? null,
            'gentle_share' => $data['gentle_share'],
            'data' => $data,
            'source_hash' => hash('sha256', $geojson),
        ]);
    }
}
