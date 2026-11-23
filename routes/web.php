<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The Vue app handles its own routing; every page path serves app.blade.php. The user guide is a static Starlight
| site built into public/docs (npm run docs:build).
|
*/

Route::get('/docs/{path?}', DocsController::class)->where('path', '.*')->name('docs');

Route::prefix('auth')->group(function() {
    Route::get('/{provider}/redirect', [AuthController::class, 'redirectToProvider'])->whereIn('provider', AuthController::PROVIDERS);
    Route::get('/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->whereIn('provider', AuthController::PROVIDERS);
});

Route::get('/image/{type}/{id}', [ImageController::class, 'getUrl']);

Route::get('/', function () {
    return view('app');
})->name('application');

Route::get('/{id}', function ($id) {
    return view('app', ['id' => $id]);
})->name('application.page');
