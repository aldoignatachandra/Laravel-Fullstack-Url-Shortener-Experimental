<?php

namespace App\Services\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use App\Support\Security\RateLimitGuard;
use App\Support\Security\RateLimitPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetOtpService
{
    /**
     * Seconds a user must wait before requesting another OTP.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly RateLimitGuard $rateLimitGuard,
    ) {}

    /**
     * @throws ValidationException
     */
    public function sendOtp(Request $request, string $email): string
    {
        $email = $this->normalizeEmail($email);

        $rateLimit = $this->rateLimitGuard->attempt(
            RateLimitPolicy::passwordReset($request, $email),
        );

        if (! $rateLimit->allowed) {
            throw ValidationException::withMessages([
                'email' => "Too many reset requests. Try again in {$rateLimit->retryAfterSeconds} seconds.",
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'We couldn’t find an account with that email.',
            ]);
        }

        PasswordResetOtp::where('email', $email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::create([
            'email' => $email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new SendOtpNotification($otp));

        $this->markOtpSent($email);

        return $email;
    }

    /**
     * Check whether the given email is still within the resend cooldown.
     *
     * Returns 0 if the user can resend, or the number of seconds remaining.
     */
    public function resendCooldownRemaining(string $email): int
    {
        $key = $this->resendCooldownKey($email);
        $expiresAt = Cache::store(config('cache.limiter'))->get($key);

        if (! is_numeric($expiresAt)) {
            return 0;
        }

        $remaining = (int) $expiresAt - time();

        return max(0, $remaining);
    }

    private function markOtpSent(string $email): void
    {
        $key = $this->resendCooldownKey($email);

        // Store the Unix timestamp when the cooldown expires.
        // Cache TTL matches cooldown so the key auto-cleans.
        $expiresAt = time() + self::RESEND_COOLDOWN_SECONDS;
        Cache::store(config('cache.limiter'))->put($key, $expiresAt, self::RESEND_COOLDOWN_SECONDS);
    }

    private function resendCooldownKey(string $email): string
    {
        return 'otp-resend-cooldown:'.Str::lower($email);
    }

    /**
     * @throws ValidationException
     */
    public function verifyOtp(Request $request, string $email, string $otp): string
    {
        $email = $this->normalizeEmail($email);
        $buckets = RateLimitPolicy::passwordResetVerification($request, $email);
        $rateLimit = $this->rateLimitGuard->attempt($buckets);

        if (! $rateLimit->allowed) {
            throw ValidationException::withMessages([
                'otp' => "Too many reset code attempts. Try again in {$rateLimit->retryAfterSeconds} seconds.",
            ]);
        }

        $otpRecord = PasswordResetOtp::where('email', $email)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otpRecord || $otpRecord->isExpired() || ! Hash::check($otp, $otpRecord->otp)) {
            throw ValidationException::withMessages([
                'otp' => 'The reset code is invalid or has expired.',
            ]);
        }

        $otpRecord->update(['used_at' => now()]);
        session()->regenerate();
        session()->put('otp_verified_email', $email);

        return $email;
    }

    public function resetPassword(string $password): bool
    {
        $email = session('otp_verified_email');

        if (! is_string($email)) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        session()->forget('otp_verified_email');

        return true;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower($email);
    }
}
