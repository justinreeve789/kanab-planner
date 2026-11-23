<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Sign in with Google": redirect out, then log the returning user into the session and send them back to the app.
 * New emails get an account; an existing account with the same email is linked to the provider.
 */
class AuthController extends Controller
{
    /** Providers configured in config/services.php. */
    public const PROVIDERS = ['google'];

    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login?error=' . urlencode('Could not sign in with ' . ucfirst($provider) . '.'));
        }

        $user = User::where('provider', $provider)->where('provider_id', $socialUser->getId())->first()
            ?? User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $user->forceFill(['provider' => $provider, 'provider_id' => $socialUser->getId()])->save();
        } else {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect('/');
    }
}
