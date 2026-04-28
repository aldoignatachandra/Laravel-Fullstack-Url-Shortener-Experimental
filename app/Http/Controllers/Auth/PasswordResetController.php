<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
{
    /**
     * Send an OTP to the given email address.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // Don't reveal whether the user exists
            return back()->with('status', 'If an account exists with this email, we have sent a reset code.');
        }

        // Invalidate any existing OTPs for this email
        PasswordResetOtp::where('email', $request->email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new SendOtpNotification($otp));

        return redirect()->route('password.otp', ['email' => $request->email]);
    }

    /**
     * Verify the OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $otpRecord = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otpRecord || $otpRecord->isExpired()) {
            return back()->withErrors(['otp' => 'The reset code is invalid or has expired.'])->withInput();
        }

        // Mark as used
        $otpRecord->update(['used_at' => now()]);

        // Store verified email in session for the reset step
        session()->put('otp_verified_email', $request->email);

        return redirect()->route('password.reset');
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request)
    {
        if (! session()->has('otp_verified_email')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = session('otp_verified_email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        session()->forget('otp_verified_email');

        return redirect()->route('login')->with('status', 'Your password has been reset!');
    }
}
