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

    /**
     * ?date=YYYY-MM-DD limits to events happening that day.
     */
    public function getEventsInArea(Request $request, int $areaId)
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        return Event::with(['data', 'dates', 'links', 'location', 'text'])
            ->whereHas('location', fn($q) => $q->where('area_id', $areaId))
            ->when($request->get('date'), fn($q, $date) => $q->betweenDates($date . ' 00:00:00', $date . ' 23:59:59'))
            ->get();
    }

    public function getEvent(Request $request, int $id)
    {
        return Event::with(['data', 'dates', 'links', 'location', 'text'])->where('id', $id)->first();
    }
}
