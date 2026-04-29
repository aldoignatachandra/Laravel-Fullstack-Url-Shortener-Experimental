<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.otp', ['email' => $user->email]));
        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_verify_otp_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.otp', ['email' => 'test@example.com']));

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Verify OTP
        $response = $this->post('/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '654321',
        ]);

        $response->assertRedirect(route('password.reset'));

        // Reset password
        $response = $this->withSession(['otp_verified_email' => 'test@example.com'])
            ->post('/reset-password', [
                'password' => 'NewSecure1!',
                'password_confirmation' => 'NewSecure1!',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewSecure1!', $user->fresh()->password));
    }
}
