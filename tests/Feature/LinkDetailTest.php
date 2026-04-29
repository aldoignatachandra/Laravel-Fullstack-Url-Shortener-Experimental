<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\LinkLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_link_detail(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'title' => 'My Detail Link',
            'short_code' => 'det123',
            'original_url' => 'https://example.com/long-url',
        ]);

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertOk();
        $response->assertSee('My Detail Link');
        $response->assertSee('det123');
        $response->assertSee('https://example.com/long-url');
    }

    public function test_cannot_view_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertForbidden();
    }

    public function test_shows_click_analytics(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);
        LinkLog::factory()->count(7)->create(['link_id' => $link->id]);

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertSee('7'); // total clicks
    }

    public function test_shows_empty_click_trend_state(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertOk();
        $response->assertSee('Clicks Trend');
        $response->assertSee('7 days');
        $response->assertSee('30 days');
        $response->assertSee('No clicks in this period');
    }

    public function test_click_trend_shows_bars_when_clicks_exist(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);
        LinkLog::factory()->create([
            'link_id' => $link->id,
            'clicked_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertOk();
        $response->assertSee('Clicks Trend');
        $response->assertDontSee('No clicks in this period');
        $response->assertSee('bg-brand/40', false);
    }

    public function test_shows_unique_visitors(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);
        // Create logs with different IPs
        LinkLog::factory()->create(['link_id' => $link->id, 'ip_address' => '192.168.1.1']);
        LinkLog::factory()->create(['link_id' => $link->id, 'ip_address' => '192.168.1.2']);
        LinkLog::factory()->create(['link_id' => $link->id, 'ip_address' => '192.168.1.1']); // duplicate

        $response = $this->actingAs($user)->get("/links/{$link->id}");

        $response->assertSee('2'); // unique visitors
    }

    public function test_unauthenticated_redirected(): void
    {
        $link = Link::factory()->create();

        $this->get("/links/{$link->id}")->assertRedirect(route('login'));
    }
}
