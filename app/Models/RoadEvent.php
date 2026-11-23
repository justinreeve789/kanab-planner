<?php

namespace App\Models;

use App\Services\Routing\Point;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A closure, roadwork or incident from a state 511 system (see `php artisan update:road-events`). Times are UTC.
 */
class RoadEvent extends Model
{
    public const ROADWORK = 'roadwork';
    public const CLOSURES = 'closures';
    public const INCIDENTS = 'accidentsAndIncidents';
    public const SPECIAL_EVENTS = 'specialEvents';

    protected $guarded = ['id'];

    protected $casts = [
        'is_full_closure' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'latitude_secondary' => 'float',
        'longitude_secondary' => 'float',
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
    ];

    /**
     * In effect at some point on $date (a local trip date). Events with no end are current incidents, so they only
     * count for the next few days.
     */
    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        $tz = config('routing.timezone');
        $dayStart = CarbonImmutable::parse($date, $tz)->startOfDay()->utc();
        $dayEnd = $dayStart->addDay();
        $openEndedUntil = CarbonImmutable::now($tz)->startOfDay()->addDays(config('routing.road_events.open_ended_days') + 1)->utc();
        $includeOpenEnded = $dayStart->lt($openEndedUntil);

        return $query
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<', $dayEnd))
            ->where(function($q) use ($dayStart, $includeOpenEnded) {
                $q->where('ends_at', '>=', $dayStart);
                if ($includeOpenEnded) {
                    $q->orWhereNull('ends_at');
                }
            });
    }

    /** Within $miles of the box around $a and $b. */
    public function scopeAround(Builder $query, Point $a, Point $b, float $miles): Builder
    {
        $dLat = $miles / 69.0;
        $dLng = $miles / (69.0 * cos(deg2rad(($a->lat + $b->lat) / 2)));
        return $query
            ->whereBetween('latitude', [min($a->lat, $b->lat) - $dLat, max($a->lat, $b->lat) + $dLat])
            ->whereBetween('longitude', [min($a->lng, $b->lng) - $dLng, max($a->lng, $b->lng) + $dLng]);
    }

    /** @return Point[] the event's location and, for events along a stretch of road, its far end */
    public function points(): array
    {
        $points = [new Point($this->latitude, $this->longitude)];
        if ($secondary = Point::make($this->latitude_secondary, $this->longitude_secondary)) {
            $points[] = $secondary;
        }
        return $points;
    }

    /** One line for the itinerary, e.g. "Roadwork on SR-9: Lane closures near the tunnel". */
    public function summary(): string
    {
        $what = match (true) {
            $this->is_full_closure => 'Road closed',
            $this->event_type === self::ROADWORK => 'Roadwork',
            $this->event_type === self::INCIDENTS => 'Incident',
            $this->event_type === self::CLOSURES => 'Lane closure',
            default => 'Traffic event',
        };
        $text = $what . ($this->roadway ? " on {$this->roadway}" : '');
        $detail = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->description)));
        if ($detail !== '') {
            $text .= ': ' . mb_strimwidth($detail, 0, 160, '…');
        }
        return $text;
    }
}
