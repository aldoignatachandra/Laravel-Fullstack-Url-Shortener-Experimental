<?php

use App\Models\Link;
use App\Services\ShortCodeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Rule('required|url|max:2048')]
    public string $original_url = '';

    #[Rule('nullable|string|max:100')]
    public string $title = '';

    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingLinkId = null;

    public string $deletingLinkTitle = '';

    public string $deletingLinkShortUrl = '';

    #[Computed]
    public function links()
    {
        return Link::where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    public function create(): void
    {
        $validated = $this->validate();

        Link::create([
            'user_id' => Auth::id(),
            'original_url' => $validated['original_url'],
            'title' => $validated['title'] ?: null,
            'short_code' => ShortCodeService::generateUnique(),
            'status' => 1,
        ]);

        $this->reset(['original_url', 'title', 'showCreateModal']);
        unset($this->links);

        session()->flash('status', 'Link created successfully!');
    }

    public function confirmDelete(int $linkId): void
    {
        $link = Link::findOrFail($linkId);
        $this->authorize('delete', $link);

        $this->deletingLinkId = $link->id;
        $this->deletingLinkTitle = $link->title ?: $link->original_url;
        $this->deletingLinkShortUrl = url('/s/' . $link->short_code);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingLinkId = null;
        $this->deletingLinkTitle = '';
        $this->deletingLinkShortUrl = '';
    }

    public function deleteSelectedLink(): void
    {
        if ($this->deletingLinkId === null) {
            $this->closeDeleteModal();

            return;
        }

        $link = Link::findOrFail($this->deletingLinkId);
        $this->authorize('delete', $link);

        $link->delete();
        unset($this->links);

        $this->closeDeleteModal();

        session()->flash('status', 'Link deleted.');
    }
}; ?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-medium text-surface-off-white">Short URLs</h1>
                    <p class="mt-1 text-sm text-surface-mid-gray">Manage your shortened links</p>
                </div>
                <button
                    wire:click="$set('showCreateModal', true)"
                    class="px-6 py-2 text-sm font-medium rounded-pill bg-brand text-surface-black border border-brand hover:opacity-90 transition-opacity"
                >
                    + Add URL
                </button>
            </div>

            {{-- Flash message --}}
            @if (session('status'))
                <div class="mb-6 px-4 py-3 rounded-md border border-brand/30 bg-brand/10 text-brand text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Create Modal --}}
            @if ($showCreateModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" wire:click="$set('showCreateModal', false)">
                    <div class="w-full max-w-md mx-4 p-6 rounded-lg bg-surface-dark border border-surface-border" onclick="event.stopPropagation()">
                        <h2 class="text-lg font-medium text-surface-off-white mb-4">Create Short URL</h2>

                        <form wire:submit="create">
                            {{-- URL Field --}}
                            <div class="mb-4">
                                <label for="original_url" class="block text-sm text-surface-light-gray mb-1.5">Destination URL</label>
                                <input
                                    wire:model="original_url"
                                    type="url"
                                    id="original_url"
                                    placeholder="https://example.com/very-long-url"
                                    class="w-full px-3 py-2 text-sm rounded-md bg-surface-black border border-surface-border text-surface-off-white placeholder-surface-dark-gray focus:border-brand focus:outline-none transition-colors"
                                />
                                @error('original_url')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Title Field --}}
                            <div class="mb-6">
                                <label for="title" class="block text-sm text-surface-light-gray mb-1.5">Title <span class="text-surface-dark-gray">(optional)</span></label>
                                <input
                                    wire:model="title"
                                    type="text"
                                    id="title"
                                    placeholder="My link title"
                                    class="w-full px-3 py-2 text-sm rounded-md bg-surface-black border border-surface-border text-surface-off-white placeholder-surface-dark-gray focus:border-brand focus:outline-none transition-colors"
                                />
                                @error('title')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    wire:click="$set('showCreateModal', false)"
                                    class="px-4 py-2 text-sm rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-6 py-2 text-sm font-medium rounded-pill bg-brand text-surface-black hover:opacity-90 transition-opacity"
                                >
                                    Create
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Delete Confirmation Modal --}}
            @if ($showDeleteModal)
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                    x-data
                    x-on:keydown.escape.window="$wire.call('closeDeleteModal')"
                    wire:click="closeDeleteModal"
                >
                    <div
                        class="w-full max-w-md mx-4 p-6 rounded-xl bg-surface-dark border border-surface-border"
                        onclick="event.stopPropagation()"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="delete-modal-title"
                    >
                        {{-- Icon --}}
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/10 border border-rose-500/20 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-rose-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </div>

                        {{-- Title --}}
                        <h3 id="delete-modal-title" class="text-center text-lg font-medium text-surface-off-white mb-2">
                            Delete short URL?
                        </h3>

                        {{-- Body --}}
                        <p class="text-center text-sm text-surface-mid-gray mb-5">
                            This action cannot be undone. The short URL will stop working immediately.
                        </p>

                        {{-- Link preview --}}
                        <div class="rounded-lg border border-surface-border bg-surface-black p-3 mb-6">
                            <p class="text-sm text-surface-off-white truncate">{{ $deletingLinkTitle }}</p>
                            <p class="text-xs text-brand mt-0.5 truncate">{{ $deletingLinkShortUrl }}</p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                wire:click="closeDeleteModal"
                                class="px-4 py-2 text-sm rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="deleteSelectedLink"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium rounded-md bg-rose-500 text-white hover:bg-rose-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="deleteSelectedLink">Delete URL</span>
                                <span wire:loading wire:target="deleteSelectedLink">Deleting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Links List --}}
            <div class="space-y-3">
                @forelse ($this->links as $link)
                    <div class="p-5 rounded-lg border border-surface-border bg-surface-dark">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                {{-- Title --}}
                                <h3 class="text-base font-medium text-surface-off-white truncate">
                                    {{ $link->title ?: $link->original_url }}
                                </h3>

                                {{-- Short URL --}}
                                <a
                                    href="{{ url('/s/' . $link->short_code) }}"
                                    target="_blank"
                                    class="mt-1 inline-block text-sm text-brand hover:underline"
                                >
                                    {{ url('/s/' . $link->short_code) }}
                                </a>

                                {{-- Original URL --}}
                                <p class="mt-1 text-xs text-surface-mid-gray truncate max-w-lg">
                                    {{ Str::limit($link->original_url, 80) }}
                                </p>

                                {{-- Date --}}
                                <p class="mt-2 text-xs text-surface-dark-gray">
                                    {{ $link->created_at->format('M d, Y') }}
                                </p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText(@js(url('/s/' . $link->short_code))); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="h-9 px-3.5 inline-flex items-center gap-2 text-sm rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375V9.375a1.125 1.125 0 0 0-1.125-1.125h-9.75a1.125 1.125 0 0 0-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125h9.75Z" />
                                    </svg>
                                    <span x-text="copied ? 'Copied' : 'Share'"></span>
                                </button>

                                <a
                                    href="{{ route('links.show', $link) }}"
                                    wire:navigate
                                    class="h-9 px-3.5 inline-flex items-center gap-2 text-sm rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                    Detail
                                </a>

                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $link->id }})"
                                    class="h-9 px-3.5 inline-flex items-center gap-2 text-sm rounded-md border border-rose-500/30 text-rose-400 bg-rose-500/10 hover:text-rose-300 hover:bg-rose-500/20 hover:border-rose-500/40 transition-colors ml-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center border border-dashed border-surface-border rounded-lg">
                        <p class="text-surface-mid-gray">No links yet. Create your first short URL!</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
