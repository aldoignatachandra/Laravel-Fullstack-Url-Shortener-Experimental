<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Link;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts.app')] class extends Component
{
    public Link $link;

    public function mount(Link $link): void
    {
        $this->authorize('view', $link);
        $this->link = $link->loadCount('logs');
    }

    public function getClickCountProperty(): int
    {
        return $this->link->logs()->count();
    }

    public function getUniqueVisitorsProperty(): int
    {
        return $this->link->logs()->distinct('ip_address')->count('ip_address');
    }

    public function getRecentClicksProperty()
    {
        return $this->link->logs()->latest('clicked_at')->take(10)->get();
    }

    public function getTopReferrersProperty()
    {
        return $this->link->logs()
            ->select('referrer', \DB::raw('count(*) as total'))
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('total')
            ->take(5)
            ->get();
    }
}; ?>

<div>
    <div class="min-h-screen bg-surface-dark text-surface-off-white">
        <div class="max-w-5xl mx-auto px-6 py-8">
            {{-- Back button --}}
            <a href="{{ route('links.index') }}" wire:navigate class="inline-flex items-center gap-2 text-surface-mid-gray hover:text-surface-off-white text-sm transition-colors mb-6">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Dashboard
            </a>

            {{-- Link Info Card --}}
            <div class="border border-surface-border rounded-2xl p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl text-surface-off-white mb-3">{{ $link->title }}</h1>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-surface-mid-gray uppercase tracking-wider w-20 shrink-0">Short URL</span>
                                <span class="text-brand text-sm truncate">{{ url('/s/' . $link->short_code) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-surface-mid-gray uppercase tracking-wider w-20 shrink-0">Original</span>
                                <span class="text-surface-light-gray text-sm truncate">{{ $link->original_url }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-surface-mid-gray uppercase tracking-wider w-20 shrink-0">Status</span>
                                @if($link->isActive())
                                    <span class="inline-flex items-center gap-1.5 text-xs text-brand">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs text-surface-mid-gray">
                                        <span class="w-1.5 h-1.5 rounded-full bg-surface-mid-gray"></span>
                                        Archived
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-surface-mid-gray uppercase tracking-wider w-20 shrink-0">Created</span>
                                <span class="text-surface-light-gray text-sm">{{ $link->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <button
                            onclick="navigator.clipboard.writeText('{{ url('/s/' . $link->short_code) }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Share', 1500)"
                            class="bg-brand text-surface-black rounded-pill px-5 py-2 text-sm font-medium hover:bg-brand-dark transition-colors"
                        >
                            Share
                        </button>
                    </div>
                </div>
            </div>

            {{-- Analytics Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                {{-- Total Clicks --}}
                <div class="border border-surface-border rounded-2xl p-6">
                    <div class="text-xs text-surface-mid-gray uppercase tracking-wider mb-2">Total Clicks</div>
                    <div class="text-4xl text-surface-off-white">{{ $this->clickCount }}</div>
                </div>

                {{-- Unique Visitors --}}
                <div class="border border-surface-border rounded-2xl p-6">
                    <div class="text-xs text-surface-mid-gray uppercase tracking-wider mb-2">Unique Visitors</div>
                    <div class="text-4xl text-surface-off-white">{{ $this->uniqueVisitors }}</div>
                </div>
            </div>

            {{-- Top Referrers --}}
            @if($this->topReferrers->isNotEmpty())
                <div class="border border-surface-border rounded-2xl p-6 mb-8">
                    <h2 class="text-lg text-surface-off-white mb-4">Top Referrers</h2>
                    <div class="space-y-3">
                        @foreach($this->topReferrers as $ref)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-surface-light-gray truncate max-w-[80%]">{{ $ref->referrer }}</span>
                                <span class="text-sm text-surface-mid-gray shrink-0 ml-4">{{ $ref->total }} clicks</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recent Clicks --}}
            <div class="border border-surface-border rounded-2xl p-6">
                <h2 class="text-lg text-surface-off-white mb-4">Recent Clicks</h2>
                @if($this->recentClicks->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border">
                                    <th class="text-left text-xs text-surface-mid-gray uppercase tracking-wider pb-3 pr-4">Date</th>
                                    <th class="text-left text-xs text-surface-mid-gray uppercase tracking-wider pb-3 pr-4">IP Address</th>
                                    <th class="text-left text-xs text-surface-mid-gray uppercase tracking-wider pb-3 pr-4">Browser</th>
                                    <th class="text-left text-xs text-surface-mid-gray uppercase tracking-wider pb-3">Referrer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->recentClicks as $click)
                                    <tr class="border-b border-surface-dark-border last:border-0">
                                        <td class="py-3 pr-4 text-surface-light-gray whitespace-nowrap">{{ $click->clicked_at?->format('M d, H:i') }}</td>
                                        <td class="py-3 pr-4 text-surface-light-gray font-mono text-xs">{{ $click->ip_address }}</td>
                                        <td class="py-3 pr-4 text-surface-light-gray max-w-[200px] truncate">{{ $click->user_agent }}</td>
                                        <td class="py-3 text-surface-light-gray max-w-[200px] truncate">{{ $click->referrer ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-surface-mid-gray text-sm">No clicks recorded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
