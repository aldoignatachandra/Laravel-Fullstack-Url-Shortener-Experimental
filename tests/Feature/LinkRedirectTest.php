<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_active_link_to_original_url(): void
    {
        $link = Link::factory()->create([
            'short_code' => 'abc123',
            'original_url' => 'https://example.com/very-long-url',
            'status' => 1,
        ]);

        $response = $this->get('/s/abc123');

        $response->assertRedirect('https://example.com/very-long-url');
    }

    public function test_returns_404_for_unknown_short_code(): void
    {
        $response = $this->get('/s/nonexistent');

        $response->assertNotFound();
    }

    public function test_returns_404_for_archived_link(): void
    {
        Link::factory()->archived()->create([
            'short_code' => 'archiv1',
        ]);

        $response = $this->get('/s/archiv1');

        $response->assertNotFound();
    }

    public function test_returns_404_for_soft_deleted_link(): void
    {
        $link = Link::factory()->create([
            'short_code' => 'deleted',
            'status' => 1,
        ]);
        $link->delete();

        $response = $this->get('/s/deleted');

        $response->assertNotFound();
    }

    public function test_logs_click_on_redirect(): void
    {
        $link = Link::factory()->create([
            'short_code' => 'logged1',
            'status' => 1,
        ]);

        $this->get('/s/logged1');

        $this->assertDatabaseHas('link_logs', [
            'link_id' => $link->id,
        ]);
    }

    public function test_logs_ip_address_and_user_agent(): void
    {
        Link::factory()->create([
            'short_code' => 'track1',
            'status' => 1,
        ]);

        $this->get('/s/track1', [
            'User-Agent' => 'TestBrowser/1.0',
            'Referer' => 'https://twitter.com',
        ]);

        $hashedIp = hash('sha256', '127.0.0.1'.config('app.key'));

        $this->assertDatabaseHas('link_logs', [
            'ip_address' => $hashedIp,
            'user_agent' => 'TestBrowser/1.0',
            'referrer' => 'https://twitter.com',
        ]);
    }
}
