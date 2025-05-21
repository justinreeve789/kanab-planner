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
    Route::prefix('auth')->group(function() {
        Route::post('/register', 'Laravel\Fortify\Http\Controllers\RegisteredUserController@store');
    });

    Route::prefix('activity_type')->group(function() {
        Route::get('/', 'App\Http\Controllers\ActivityController@getSelectableActivityTypes');
    });

    Route::prefix('activity')->group(function() {
        Route::get('/{activityId}', 'App\Http\Controllers\ItineraryController@getActivity');
        Route::put('/{activityId}', 'App\Http\Controllers\ItineraryController@updateActivity');
        Route::delete('/{activityId}', 'App\Http\Controllers\ItineraryController@deleteActivity');
    });

    Route::prefix('area')->group(function() {
        Route::get('/', 'App\Http\Controllers\AreaController@index');
        Route::get('/{id}/trail', 'App\Http\Controllers\AreaController@getTrails');
        Route::get('/{areaId}', 'App\Http\Controllers\AreaController@getArea');
        Route::get('/{areaId}/event', 'App\Http\Controllers\EventController@getEventsInArea');
        Route::get('/{areaId}/location', 'App\Http\Controllers\LocationController@getLocationsInArea');
        Route::get('/{areaId}/restaurant', 'App\Http\Controllers\RestaurantController@getRestaurantsInArea');
    });

    Route::prefix('assistant')->group(function() {
        Route::get('/', 'App\Http\Controllers\AssistantController@getAssistants');
        Route::post('/itinerary', 'App\Http\Controllers\AssistantController@createItinerary');
        Route::put('/itinerary/{itineraryId}', 'App\Http\Controllers\AssistantController@updateItinerary');
        Route::prefix('message')->group(function() {
            Route::post('/', 'App\Http\Controllers\AssistantController@sendMessage');
        });
        Route::prefix('thread')->group(function() {
            Route::get('/', 'App\Http\Controllers\AssistantController@getMessages');
            Route::delete('/', 'App\Http\Controllers\AssistantController@deleteMessages');
        });
    });

    Route::prefix('day')->group(function() {
        Route::get('/{dayId}', 'App\Http\Controllers\ItineraryController@getDay');
        Route::post('/{dayId}/activity', 'App\Http\Controllers\ItineraryController@createActivity');
        Route::put('/{dayId}', 'App\Http\Controllers\ItineraryController@updateDay');
        Route::delete('/{dayId}', 'App\Http\Controllers\ItineraryController@deleteDay');
    });

    Route::prefix('event')->group(function() {
        Route::get('/', 'App\Http\Controllers\EventController@index');
        Route::get('/{id}', 'App\Http\Controllers\EventController@getEvent');
    });

    Route::prefix('feature')->group(function() {
        Route::get('/', 'App\Http\Controllers\FeatureController@index');
    });

    Route::prefix('featured')->group(function() {
        Route::get('/{resourceTable}', 'App\Http\Controllers\SearchController@featured');
    });

    Route::prefix('itinerary')->group(function() {
        // TODO: Make this a POST to create the itinerary. A GET should return a list of itineraries.
        Route::get('/', 'App\Http\Controllers\ItineraryController@createItinerary');
        Route::get('/{id}', 'App\Http\Controllers\ItineraryController@loadItinerary');
        Route::put('/{id}', 'App\Http\Controllers\ItineraryController@updateItinerary');
        Route::delete('/{id}', 'App\Http\Controllers\ItineraryController@deleteItinerary');
    });

    Route::prefix('image')->group(function() {
        Route::get('/', 'App\Http\Controllers\ImageController@getImageByQuery');
        Route::get('/map/{mapType}', 'App\Http\Controllers\ImageController@getMapTile');
        Route::get('/map/{mapType}/{z}/{x}/{y}.{ext}', 'App\Http\Controllers\ImageController@getMapTile');
    });

    Route::prefix('location')->group(function() {
        Route::get('/', 'App\Http\Controllers\LocationController@index');
        Route::get('/{id}', 'App\Http\Controllers\LocationController@getLocation');
    });

    Route::prefix('period')->group(function() {
        Route::post('/{periodId}/activity', 'App\Http\Controllers\ItineraryController@createActivity');
    });

    Route::prefix('restaurant')->group(function() {
        Route::get('/', 'App\Http\Controllers\RestaurantController@index');
        Route::get('/{id}', 'App\Http\Controllers\RestaurantController@getRestaurant');
    });

    Route::prefix('search')->group(function() {
        Route::get('/', 'App\Http\Controllers\SearchController@search');
    });

    Route::prefix('trail')->group(function() {
        Route::get('/', 'App\Http\Controllers\TrailController@index');
        Route::get('/{id}', 'App\Http\Controllers\TrailController@getTrail');
        Route::get('/{id}/geojson', 'App\Http\Controllers\TrailController@getGeoJsonData');
        Route::get('/{id}/geojson/analyze', 'App\Http\Controllers\TrailController@analyzeGeoJson');
        Route::get('/{id}/geojson/download', 'App\Http\Controllers\TrailController@downloadGeoJson');
        Route::get('/{id}/gpx', 'App\Http\Controllers\TrailController@getGpxData');
        Route::get('/{id}/gpx/download', 'App\Http\Controllers\TrailController@downloadGpx');
    });
});
