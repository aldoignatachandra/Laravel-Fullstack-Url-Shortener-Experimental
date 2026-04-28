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

    public function delete(int $linkId): void
    {
        $link = Link::findOrFail($linkId);
        $this->authorize('delete', $link);

        $link->delete();
        unset($this->links);

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
                    <div class="w-full max-w-md mx-4 p-6 rounded-lg bg-surface-dark border border-surface-border" wire:click.stop>
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

            {{-- Links List --}}
            <div class="space-y-3">
                @forelse ($this->links as $link)
                    <div class="p-5 rounded-lg border border-surface-border bg-surface-dark">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                {{-- Title --}}
                                <h3 class="text-base font-medium text-surface-off-white truncate">
                                    {{ $link->title ?: $link->original_url }}
                                </h3>

                                {{-- Short URL --}}
                                <p class="mt-1 text-sm text-brand">
                                    {{ url('/s/' . $link->short_code) }}
                                </p>

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
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    onclick="navigator.clipboard.writeText('{{ url('/s/' . $link->short_code) }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Share', 1500)"
                                    class="px-3 py-1.5 text-xs rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                                >
                                    Share
                                </button>
                                <a
                                    href="{{ url('/s/' . $link->short_code) }}"
                                    target="_blank"
                                    class="px-3 py-1.5 text-xs rounded-md border border-surface-border text-surface-light-gray hover:text-surface-off-white hover:border-surface-mid-border transition-colors"
                                >
                                    Detail
                                </a>
                                <button
                                    wire:click="delete({{ $link->id }})"
                                    wire:confirm="Are you sure you want to delete this link?"
                                    class="px-3 py-1.5 text-xs rounded-md border border-red-900/50 text-red-400 hover:text-red-300 hover:border-red-800 transition-colors"
                                >
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
