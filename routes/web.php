<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('app');
})->name('application');

Route::get('/{id}', function ($id) {
    return view('app', ['id' => $id]);
})->name('application');

Route::prefix('auth')->group(function() {
    Route::get('/{provider}', [AuthController::class, 'redirectToProvider'])
        ->where('provider', 'google|facebook');

    Route::get('/callback', [AuthController::class, 'handleProviderCallback'])
        ->where('provider', 'google|facebook');

    Route::get('/redirect/{provider}', function ($provider) {
        return Socialite::driver($provider)->stateless()->redirect();
    });

    Route::get('/callback/{provider}', function ($provider) {
        $user = Socialite::driver($provider)->stateless()->user();
        $existingUser = User::where('email', $user->getEmail())->first();
        if (!$existingUser) {
            $existingUser = User::create([
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'provider_id' => $user->getId(),
            ]);
        }
        Auth::login($existingUser);
        return redirect('/');
    });
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/callback', function () {
    $user = Socialite::driver('google')->user();

    // $user->token
});

Route::prefix('itinerary')->group(function() {
    Route::get('/', 'App\Http\Controllers\ItineraryController@create');
});

Route::get('/image/{type}/{id}', [ImageController::class, 'getUrl']);
Route::get('/featured-resource-image/{type}/{resourceId}', [ImageController::class, 'getFeaturedImageUrl']);
