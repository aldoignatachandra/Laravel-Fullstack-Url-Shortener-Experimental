<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\Link;
use App\Models\LinkLog;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component
{
    #[Computed]
    public function totalLinks(): int
    {
        return Link::where('user_id', Auth::id())->count();
    }

    #[Computed]
    public function totalClicks(): int
    {
        return LinkLog::whereIn('link_id', function ($query) {
            $query->select('id')->from('links')->where('user_id', Auth::id());
        })->count();
    }

    #[Computed]
    public function uniqueVisitors(): int
    {
        return LinkLog::whereIn('link_id', function ($query) {
            $query->select('id')->from('links')->where('user_id', Auth::id());
        })->distinct('ip_address')->count('ip_address');
    }

    #[Computed]
    public function popularLinks()
    {
        return Link::where('user_id', Auth::id())
            ->withCount('logs')
            ->orderByDesc('logs_count')
            ->take(5)
            ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-surface-off-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {{-- Total Links --}}
                <div class="bg-surface-dark border border-surface-border rounded-lg p-6">
                    <div class="text-4xl font-bold text-surface-off-white">{{ $this->totalLinks }}</div>
                    <div class="text-surface-mid-gray mt-1 text-sm">Total Links</div>
                </div>

                {{-- Total Clicks --}}
                <div class="bg-surface-dark border border-surface-border rounded-lg p-6">
                    <div class="text-4xl font-bold text-surface-off-white">{{ $this->totalClicks }}</div>
                    <div class="text-surface-mid-gray mt-1 text-sm">Total Clicks</div>
                </div>

                {{-- Unique Visitors --}}
                <div class="bg-surface-dark border border-surface-border rounded-lg p-6">
                    <div class="text-4xl font-bold text-surface-off-white">{{ $this->uniqueVisitors }}</div>
                    <div class="text-surface-mid-gray mt-1 text-sm">Unique Visitors</div>
                </div>
            </div>

            {{-- Popular Links --}}
            <div class="bg-surface-dark border border-surface-border rounded-lg p-6">
                <h3 class="text-lg font-semibold text-surface-off-white mb-4">Popular Links</h3>

                @if($this->popularLinks->count() > 0)
                    <ul class="space-y-3">
                        @foreach($this->popularLinks as $link)
                            <li class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-surface-border' : '' }}">
                                <div>
                                    <div class="text-surface-off-white font-medium">{{ $link->title }}</div>
                                    <div class="text-surface-mid-gray text-sm truncate max-w-md">{{ $link->original_url }}</div>
                                </div>
                                <div class="text-brand font-semibold ml-4 shrink-0">
                                    {{ $link->logs_count }} {{ Str::plural('click', $link->logs_count) }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-surface-mid-gray text-sm">No links yet. Create your first short link to get started.</p>
                @endif
            </div>
        </div>
    </div>
</div>
