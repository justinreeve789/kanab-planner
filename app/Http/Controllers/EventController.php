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

    public function getEvent(Request $request, int $id)
    {
        return Event::with(['data', 'dates', 'links', 'location', 'text'])->where('id', $id)->first();
    }
}
