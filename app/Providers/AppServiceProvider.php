<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Clear OTP verification session on login to prevent session leak.
        // If a user verifies OTP, then logs in (or logs in with another account),
        // the otp_verified_email session key should not persist.
        Event::listen(Login::class, function (Login $event): void {
            session()->forget('otp_verified_email');
        });
    }
}
