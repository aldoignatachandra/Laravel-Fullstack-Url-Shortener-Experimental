<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinksListTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_page_requires_auth(): void
    {
        $this->get('/links')->assertRedirect(route('login'));
    }

    public function test_shows_users_links_as_cards(): void
    {
        $user = User::factory()->create();
        Link::factory()->count(3)->create(['user_id' => $user->id, 'title' => 'Test Link']);

        $response = $this->actingAs($user)->get('/links');

        $response->assertOk();
        $response->assertSee('Test Link');
    }

    public function test_does_not_show_other_users_links(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Link::factory()->create(['user_id' => $otherUser->id, 'title' => 'Private Link']);

        $response = $this->actingAs($user)->get('/links');

        $response->assertDontSee('Private Link');
    }

    public function test_can_create_new_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/links', [
            'original_url' => 'https://example.com/very-long',
            'title' => 'My New Link',
        ]);

        $response->assertRedirect('/links');
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/very-long',
            'title' => 'My New Link',
            'status' => 1,
        ]);
    }

    public function test_create_link_requires_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/links', [
            'original_url' => 'not-a-url',
            'title' => 'Bad Link',
        ]);

        $response->assertSessionHasErrors('original_url');
    }

    public function test_can_delete_own_link(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/links/{$link->id}");

        $response->assertRedirect('/links');
        $this->assertSoftDeleted('links', ['id' => $link->id]);
    }

    public function test_cannot_delete_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete("/links/{$link->id}");

        $response->assertForbidden();
    }
}
