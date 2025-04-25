<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function getSelectableActivityTypes(Request $request)
    {
        return ActivityType::where('is_selectable', true)->orderBy('description')->get();
    }
}
