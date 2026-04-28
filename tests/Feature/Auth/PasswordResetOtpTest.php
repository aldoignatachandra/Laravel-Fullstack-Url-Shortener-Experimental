<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
        ]);
        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => 'nonexistent@example.com',
        ]);
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
            'otp' => '123456',
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
            'otp' => '123456',
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
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post(route('password.verify'), [
            'email' => 'test@example.com',
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
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
}
