<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_page_has_button(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Continue with Google');
    }

    public function test_google_login_redirects_to_google(): void
    {
        $response = $this->get('/auth/google');

        // Should redirect to Google OAuth
        $response->assertRedirect();
        $this->assertStringContainsString('google', $response->headers->get('Location'));
    }

    public function test_google_user_has_initials(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $this->assertEquals('JD', $user->initials);
    }

    public function test_google_user_initials_with_single_name(): void
    {
        $user = User::factory()->create(['name' => 'John']);

        $this->assertEquals('J', $user->initials);
    }

    public function test_google_user_initials_with_multiple_names(): void
    {
        $user = User::factory()->create(['name' => 'John Michael Doe']);

        $this->assertEquals('JM', $user->initials);
    }

    public function test_google_user_is_google_user(): void
    {
        $user = User::factory()->google()->create();

        $this->assertTrue($user->isGoogleUser());
    }

    public function test_regular_user_is_not_google_user(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->isGoogleUser());
    }

    public function test_google_user_has_no_password(): void
    {
        $user = User::factory()->google()->create();

        $this->assertNull($user->password);
    }

    public function test_google_user_has_avatar(): void
    {
        $user = User::factory()->google()->create();

        $this->assertNotNull($user->google_avatar);
        $this->assertNotNull($user->google_id);
    }

    public function test_regular_user_can_be_linked_to_google(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'google_id' => null,
        ]);

        // Simulate linking
        $user->update([
            'google_id' => '123456789',
            'google_avatar' => 'https://example.com/avatar.jpg',
        ]);

        $this->assertTrue($user->isGoogleUser());
        $this->assertEquals('123456789', $user->google_id);
    }

    public function test_google_user_cannot_register_with_same_email(): void
    {
        // Create Google user
        $googleUser = User::factory()->google()->create(['email' => 'test@example.com']);

        // Try to register with same email
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should not create a new user with same email
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }

    public function test_google_auto_link_blocked_for_unverified_account(): void
    {
        // Create an unverified manual user
        $user = User::factory()->unverified()->create(['email' => 'victim@example.com']);

        // Fake Socialite to return a Google user with same email
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-123',
            'name' => 'Attacker',
            'email' => 'victim@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $response = $this->get('/auth/google/callback');

        // Should redirect to login with error, not auto-link
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');

        // User should still have no google_id
        $user->refresh();
        $this->assertNull($user->google_id);
    }

    public function test_google_auto_link_allowed_for_verified_account(): void
    {
        // Create a verified manual user
        $user = User::factory()->create(['email' => 'verified@example.com']);

        // Fake Socialite to return a Google user with same email
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-456',
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'avatar' => 'https://example.com/avatar2.jpg',
        ]));

        $response = $this->get('/auth/google/callback');

        // Should auto-link and redirect to dashboard
        $response->assertRedirect(route('dashboard'));

        // User should now have google_id
        $user->refresh();
        $this->assertEquals('google-456', $user->google_id);
    }
}
