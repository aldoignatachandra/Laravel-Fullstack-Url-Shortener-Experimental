<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     *
     * Hybrid Auth Flow:
     * 1. User exists by google_id → Login (already linked)
     * 2. User exists by email (manual registration) → Auto-link Google
     * 3. No user exists → Create new user
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        // Case 1: User already linked with Google
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);

            return redirect()->route('dashboard');
        }

        // Case 2: User exists with same email (manual registration) → Auto-link
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Auto-link Google to existing account
            $existingUser->update([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $existingUser->email_verified_at ?? now(),
            ]);

            Auth::login($existingUser);

            return redirect()->route('dashboard');
        }

        // Case 3: New user → Create account
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(), // Google email is verified
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
