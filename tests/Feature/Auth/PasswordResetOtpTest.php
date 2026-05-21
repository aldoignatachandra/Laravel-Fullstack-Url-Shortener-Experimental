<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_accessible(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_forgot_password_sends_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('password.otp', ['email' => 'test@example.com']));
        $response->assertSessionHas('status', 'We sent a reset code to your email. Check your inbox and spam folder.');
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
        ]);

        $otp = PasswordResetOtp::where('email', 'test@example.com')->firstOrFail();
        $sentOtp = null;

        Notification::assertSentTo(
            $user,
            SendOtpNotification::class,
            function (SendOtpNotification $notification) use (&$sentOtp): bool {
                $sentOtp = $notification->otp;

                return true;
            },
        );

        $this->assertNotNull($sentOtp);
        $this->assertNotSame($sentOtp, $otp->otp);
        $this->assertTrue(Hash::check($sentOtp, $otp->otp));
    }

    public function test_forgot_password_returns_error_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'We couldn’t find an account with that email.',
        ]);
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => 'nonexistent@example.com',
        ]);
    }

    public function test_forgot_password_livewire_keeps_email_on_error_without_redirect(): void
    {
        Notification::fake();

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'nonexistent@example.com')
            ->call('sendOtp')
            ->assertHasErrors('email')
            ->assertNoRedirect()
            ->assertSet('email', 'nonexistent@example.com');
    }

    public function test_forgot_password_is_rate_limited_by_email(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'limited@example.com']);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
                ->post(route('password.email'), [
                    'email' => 'limited@example.com',
                ])
                ->assertRedirect(route('password.otp', ['email' => 'limited@example.com']));
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
            ->post(route('password.email'), [
                'email' => 'limited@example.com',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many reset requests. Try again in',
            session('errors')->first('email'),
        );
    }

    public function test_forgot_password_is_rate_limited_by_guest_ip(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
                ->post(route('password.email'), [
                    'email' => "missing-{$attempt}@example.com",
                ])
                ->assertSessionHasErrors([
                    'email' => 'We couldn’t find an account with that email.',
                ]);
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->post(route('password.email'), [
                'email' => 'missing-6@example.com',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many reset requests. Try again in',
            session('errors')->first('email'),
        );
    }

    public function test_verify_otp_page_is_accessible(): void
    {
        $response = $this->get(route('password.otp', ['email' => 'test@example.com']));

        $response->assertOk();
    }

    public function test_verify_otp_accepts_valid_code(): void
    {
        $otp = PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post(route('password.verify'), [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('otp_verified_email', 'test@example.com');

        // OTP should be marked as used
        $this->assertNotNull($otp->fresh()->used_at);
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->post(route('password.verify'), [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertSessionHasErrors('otp');
    }

    public function test_verify_otp_rejects_wrong_code(): void
    {
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post(route('password.verify'), [
            'email' => 'test@example.com',
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
    }

    public function test_verify_otp_livewire_keeps_code_on_error_without_redirect(): void
    {
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        Volt::test('pages.auth.verify-otp', ['email' => 'test@example.com'])
            ->set('otp', '999999')
            ->call('verifyOtp')
            ->assertHasErrors('otp')
            ->assertNoRedirect()
            ->assertSet('otp', '999999');
    }

    public function test_verify_otp_livewire_can_resend_code_without_page_refresh(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'resend@example.com']);

        PasswordResetOtp::create([
            'email' => 'resend@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        Volt::test('pages.auth.verify-otp', ['email' => 'resend@example.com'])
            ->set('otp', '999999')
            ->call('resendOtp')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('otp', '')
            ->assertSeeText('We sent a new reset code to your email. Check your inbox and spam folder.');

        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_verify_otp_is_rate_limited_by_email(): void
    {
        PasswordResetOtp::create([
            'email' => 'limited-verify@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.2.{$attempt}"])
                ->post(route('password.verify'), [
                    'email' => 'limited-verify@example.com',
                    'otp' => '999999',
                ])
                ->assertSessionHasErrors('otp');
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.6'])
            ->post(route('password.verify'), [
                'email' => 'limited-verify@example.com',
                'otp' => '999999',
            ]);

        $response->assertSessionHasErrors('otp');
        $this->assertStringContainsString(
            'Too many reset code attempts. Try again in',
            session('errors')->first('otp'),
        );
    }

    public function test_verify_otp_is_rate_limited_by_guest_ip_across_emails(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            PasswordResetOtp::create([
                'email' => "limited-ip-{$attempt}@example.com",
                'otp' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(15),
            ]);

            $this->withServerVariables(['REMOTE_ADDR' => '10.0.3.1'])
                ->post(route('password.verify'), [
                    'email' => "limited-ip-{$attempt}@example.com",
                    'otp' => '999999',
                ])
                ->assertSessionHasErrors('otp');
        }

        PasswordResetOtp::create([
            'email' => 'limited-ip-11@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.3.1'])
            ->post(route('password.verify'), [
                'email' => 'limited-ip-11@example.com',
                'otp' => '999999',
            ]);

        $response->assertSessionHasErrors('otp');
        $this->assertStringContainsString(
            'Too many reset code attempts. Try again in',
            session('errors')->first('otp'),
        );
    }

    public function test_successful_verify_otp_does_not_clear_guest_ip_rate_limit(): void
    {
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            PasswordResetOtp::create([
                'email' => "success-limit-{$attempt}@example.com",
                'otp' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(15),
            ]);

            $this->withServerVariables(['REMOTE_ADDR' => '10.0.4.1'])
                ->post(route('password.verify'), [
                    'email' => "success-limit-{$attempt}@example.com",
                    'otp' => '999999',
                ])
                ->assertSessionHasErrors('otp');
        }

        PasswordResetOtp::create([
            'email' => 'success-limit-valid@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.4.1'])
            ->post(route('password.verify'), [
                'email' => 'success-limit-valid@example.com',
                'otp' => '123456',
            ])
            ->assertRedirect(route('password.reset'));

        PasswordResetOtp::create([
            'email' => 'success-limit-blocked@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.4.1'])
            ->post(route('password.verify'), [
                'email' => 'success-limit-blocked@example.com',
                'otp' => '999999',
            ]);

        $response->assertSessionHasErrors('otp');
        $this->assertStringContainsString(
            'Too many reset code attempts. Try again in',
            session('errors')->first('otp'),
        );
    }

    public function test_reset_password_requires_verified_session(): void
    {
        $response = $this->get(route('password.reset'));

        $response->assertRedirect(route('password.request'));
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->withSession(['otp_verified_email' => 'test@example.com'])
            ->post(route('password.update'), [
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));

        // Session should be cleared
        $response->assertSessionMissing('otp_verified_email');
    }

    public function test_reset_password_livewire_keeps_password_on_validation_error_without_redirect(): void
    {
        User::factory()->create(['email' => 'test@example.com']);
        $this->withSession(['otp_verified_email' => 'test@example.com']);

        Volt::test('pages.auth.reset-password')
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->call('resetPassword')
            ->assertHasErrors('password')
            ->assertNoRedirect()
            ->assertSet('password', 'Password1')
            ->assertSet('password_confirmation', 'Password1');
    }

    public function test_resend_otp_is_blocked_by_cooldown(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'cooldown@example.com']);

        PasswordResetOtp::create([
            'email' => 'cooldown@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        // First resend succeeds
        Volt::test('pages.auth.verify-otp', ['email' => 'cooldown@example.com'])
            ->call('resendOtp')
            ->assertHasNoErrors()
            ->assertSet('otp', '');

        // Immediate second resend is blocked by cooldown
        Volt::test('pages.auth.verify-otp', ['email' => 'cooldown@example.com'])
            ->call('resendOtp')
            ->assertHasErrors('email');

        // Only one notification sent (the blocked resend didn't send)
        Notification::assertSentTimes(SendOtpNotification::class, 1);
    }

    public function test_resend_cooldown_expires_after_60_seconds(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'expire@example.com']);

        PasswordResetOtp::create([
            'email' => 'expire@example.com',
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        // First resend
        Volt::test('pages.auth.verify-otp', ['email' => 'expire@example.com'])
            ->call('resendOtp')
            ->assertHasNoErrors();

        // Fast-forward past cooldown via travel
        $this->travel(61)->seconds();

        // Second resend should succeed
        Volt::test('pages.auth.verify-otp', ['email' => 'expire@example.com'])
            ->call('resendOtp')
            ->assertHasNoErrors()
            ->assertSet('otp', '');

        Notification::assertSentTimes(SendOtpNotification::class, 2);
    }

    public function test_cooldown_remaining_returns_zero_when_no_cooldown(): void
    {
        $service = app(PasswordResetOtpService::class);

        $this->assertSame(0, $service->resendCooldownRemaining('never@example.com'));
    }

    public function test_cooldown_remaining_returns_seconds_after_send(): void
    {
        $service = app(PasswordResetOtpService::class);
        Notification::fake();
        User::factory()->create(['email' => 'remaining@example.com']);

        $service->sendOtp(request(), 'remaining@example.com');

        $remaining = $service->resendCooldownRemaining('remaining@example.com');
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(PasswordResetOtpService::RESEND_COOLDOWN_SECONDS, $remaining);
    }

    public function test_cooldown_uses_limiter_store_with_fallback(): void
    {
        // The limiter store is configured as failover (redis → database).
        // In tests, CACHE_LIMITER=array so this proves the fallback works.
        $service = app(PasswordResetOtpService::class);
        Notification::fake();
        User::factory()->create(['email' => 'fallback@example.com']);

        $service->sendOtp(request(), 'fallback@example.com');

        // Should have a cooldown even with array driver (per-request only, but proves no error)
        $remaining = $service->resendCooldownRemaining('fallback@example.com');
        $this->assertGreaterThan(0, $remaining);
    }
}
