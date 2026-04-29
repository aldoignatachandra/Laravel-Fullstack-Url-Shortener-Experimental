<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
{
    /**
     * Send an OTP to the given email address.
     */
    public function sendOtp(Request $request, PasswordResetOtpService $passwordResetOtpService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = $passwordResetOtpService->sendOtp($request, $validated['email']);

        return redirect()
            ->route('password.otp', ['email' => $email])
            ->with('status', 'We sent a reset code to your email. Check your inbox and spam folder.');
    }

    /**
     * Verify the OTP code.
     */
    public function verifyOtp(Request $request, PasswordResetOtpService $passwordResetOtpService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $passwordResetOtpService->verifyOtp($request, $validated['email'], $validated['otp']);

        return redirect()->route('password.reset');
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request, PasswordResetOtpService $passwordResetOtpService): RedirectResponse
    {
        if (! session()->has('otp_verified_email')) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! $passwordResetOtpService->resetPassword($validated['password'])) {
            return redirect()->route('password.request');
        }

        return redirect()->route('login')->with('status', 'Your password has been reset!');
    }
}
