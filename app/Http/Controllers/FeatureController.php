<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index(Request $request) {
        return [
            (object) ['feature' => 'show-accommodations', 'enabled' => true],
            (object) ['feature' => 'show-assistant', 'enabled' => true],
            (object) ['feature' => 'show-menus', 'enabled' => true],
            (object) ['feature' => 'show-trail-maps', 'enabled' => true],
        ];
    }
}
