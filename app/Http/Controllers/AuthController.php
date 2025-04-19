<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{

    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to authenticate with ' . ucfirst($provider)], 401);
        }

        $user = User::where('provider_id', $socialUser->id)
            ->where('provider', $provider)
            ->first();

        if ($user) {
            Auth::login($user);
            $token = $user->createToken('social-login')->plainTextToken;
            return response()->json(['token' => $token]);
        } else {
            $newUser = User::create([
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'provider_id' => $socialUser->id,
                'provider' => $provider,
                'password' => Hash::make(uniqid()), // TODO: Generate a random password
                'email_verified_at' => now(),
            ]);

            Auth::login($newUser);
            $token = $newUser->createToken('social-login')->plainTextToken;
            return response()->json(['token' => $token]);
        }
    }
}
