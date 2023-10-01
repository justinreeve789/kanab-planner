<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    Route::prefix('area')->group(function() {
        Route::get('/', 'App\Http\Controllers\AreaController@index');
        Route::get('/{areaId}', 'App\Http\Controllers\AreaController@getArea');
        Route::get('/{areaId}/event', 'App\Http\Controllers\EventController@getEventsInArea');
        Route::get('/{areaId}/location', 'App\Http\Controllers\LocationController@getLocationsInArea');
        Route::get('/{areaId}/restaurant', 'App\Http\Controllers\RestaurantController@getRestaurantsInArea');
        Route::get('/{areaId}/trail', 'App\Http\Controllers\TrailController@getTrailsInArea');
    });

    Route::prefix('event')->group(function() {
        Route::get('/', 'App\Http\Controllers\EventController@index');
        Route::get('/{id}', 'App\Http\Controllers\EventController@getEvent');
    });

    Route::prefix('itinerary')->group(function() {
//        Route::get('/', 'App\Http\Controllers\ItineraryController@index');
        Route::get('/{id}', 'App\Http\Controllers\ItineraryController@getItinerary');
        Route::get('/', 'App\Http\Controllers\ItineraryController@createItinerary');
        Route::put('/{id}', 'App\Http\Controllers\ItineraryController@updateItinerary');
    });

    Route::prefix('image')->group(function() {
        Route::get('/', 'App\Http\Controllers\ImageController@getImageByQuery');
    });

    Route::prefix('location')->group(function() {
        Route::get('/', 'App\Http\Controllers\LocationController@index');
        Route::get('/{id}', 'App\Http\Controllers\LocationController@getLocation');
    });

    Route::prefix('restaurant')->group(function() {
        Route::get('/', 'App\Http\Controllers\RestaurantController@index');
        Route::get('/{id}', 'App\Http\Controllers\RestaurantController@getRestaurant');
    });

    Route::prefix('search')->group(function() {
        Route::get('/event', 'App\Http\Controllers\EventController@search');
        Route::get('/location', 'App\Http\Controllers\LocationController@search');
        Route::get('/restaurant', 'App\Http\Controllers\RestaurantController@search');
        Route::get('/trail', 'App\Http\Controllers\TrailController@search');
    });

    Route::prefix('trail')->group(function() {
        Route::get('/', 'App\Http\Controllers\TrailController@index');
        Route::get('/{id}', 'App\Http\Controllers\TrailController@getTrail');
        Route::get('/{id}/geojson', 'App\Http\Controllers\TrailController@getGeoJsonData');
        Route::get('/{id}/geojson/download', 'App\Http\Controllers\TrailController@downloadGeoJson');
        Route::get('/{id}/gpx', 'App\Http\Controllers\TrailController@getGpxData');
        Route::get('/{id}/gpx/download', 'App\Http\Controllers\TrailController@downloadGpx');
    });
});