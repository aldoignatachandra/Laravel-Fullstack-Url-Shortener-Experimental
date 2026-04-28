<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_total_links_count(): void
    {
        $user = User::factory()->create();
        Link::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('5');
    }

    public function test_dashboard_shows_total_clicks(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);
        LinkLog::factory()->count(10)->create(['link_id' => $link->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('10');
    }

    public function test_dashboard_only_shows_user_own_links(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Link::factory()->create(['user_id' => $otherUser->id, 'title' => 'Secret Link']);
        Link::factory()->create(['user_id' => $user->id, 'title' => 'My Link']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertDontSee('Secret Link');
        $response->assertSee('My Link');
    }

    public function test_dashboard_shows_popular_links(): void
    {
        $user = User::factory()->create();
        $popular = Link::factory()->create(['user_id' => $user->id, 'title' => 'Popular One']);
        LinkLog::factory()->count(5)->create(['link_id' => $popular->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Popular One');
    }
}
