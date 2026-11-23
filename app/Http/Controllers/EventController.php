<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Paginated: ?page=2&per_page=50 (max 100).
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->get('per_page', 50)));
        return Event::with(['data', 'dates', 'links', 'location', 'text'])->orderBy('events.id')->paginate($perPage);
    }

    public function getEventsInArea(Request $request, int $areaId)
    {
        return Event::with(['data', 'dates', 'links', 'location', 'text'])
            ->whereHas('location', fn($q) => $q->where('area_id', $areaId))
            ->get();
    }

    public function getEvent(Request $request, int $id)
    {
        return Event::with(['data', 'dates', 'links', 'location', 'text'])->where('id', $id)->first();
    }
}
