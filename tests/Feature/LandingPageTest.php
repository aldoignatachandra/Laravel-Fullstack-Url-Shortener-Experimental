<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('shrt.dev');
    }

    public function test_landing_page_shows_login_link(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('login'));
    }

    public function test_landing_page_shows_register_link(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('register'));
    }

    public function test_authenticated_user_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
