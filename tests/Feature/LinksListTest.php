<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
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

    public function test_detail_action_opens_internal_analytics_page_not_redirect_url(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'abc123',
            'title' => 'Analytics Link',
        ]);

        $response = $this->actingAs($user)->get('/links');

        $response->assertOk();
        $response->assertSee('href="'.route('links.show', $link).'"', false);
        $response->assertSee('href="'.url('/s/abc123').'"', false);
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

    public function test_can_open_custom_delete_modal_for_own_link(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'title' => 'Delete Me',
            'short_code' => 'modal1',
        ]);

        $this->actingAs($user);

        Volt::test('links.index')
            ->call('confirmDelete', $link->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingLinkId', $link->id)
            ->assertSet('deletingLinkTitle', 'Delete Me')
            ->assertSee('Delete short URL?');
    }

    public function test_can_close_custom_delete_modal_without_deleting(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'title' => 'Keep Me',
        ]);

        $this->actingAs($user);

        Volt::test('links.index')
            ->call('confirmDelete', $link->id)
            ->call('closeDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingLinkId', null)
            ->assertSet('deletingLinkTitle', '')
            ->assertSet('deletingLinkShortUrl', '');

        $this->assertDatabaseHas('links', ['id' => $link->id]);
        $this->assertNotSoftDeleted('links', ['id' => $link->id]);
    }

    public function test_can_delete_link_from_custom_delete_modal(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Volt::test('links.index')
            ->call('confirmDelete', $link->id)
            ->call('deleteSelectedLink')
            ->assertSet('showDeleteModal', false)
            ->assertSee('Link deleted.');

        $this->assertSoftDeleted('links', ['id' => $link->id]);
    }

    public function test_delete_without_selected_link_safely_closes_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('links.index')
            ->set('showDeleteModal', true)
            ->call('deleteSelectedLink')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingLinkId', null);
    }

    public function test_cannot_open_delete_modal_for_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user);

        Volt::test('links.index')
            ->call('confirmDelete', $link->id)
            ->assertForbidden();
    }
}
