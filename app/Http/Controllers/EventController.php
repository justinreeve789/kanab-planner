<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        return Event::with('location')->get();
    }

    public function getEvent(Request $request)
    {
        $id = $request->get('id');
        return Event::with('location')->where('id', $id)->first();
    }
}
