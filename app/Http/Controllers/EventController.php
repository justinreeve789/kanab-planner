<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        return Event::with(['data', 'dates', 'links', 'location', 'text'])->get();
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
