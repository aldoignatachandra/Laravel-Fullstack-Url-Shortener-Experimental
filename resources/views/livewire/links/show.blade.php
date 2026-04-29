<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Link;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Link $link;

    public int $trendDays = 30;

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
            ->select('referrer', DB::raw('count(*) as total'))
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('total')
            ->take(5)
            ->get();
    }

    public function setTrendDays(int $days): void
    {
        if (! in_array($days, [7, 30], true)) {
            return;
        }

        $this->trendDays = $days;
        unset($this->trendData);
    }

    #[Computed]
    public function trendData(): array
    {
        $start = now()->subDays($this->trendDays)->startOfDay();

        $raw = $this->link->logs()
            ->where('clicked_at', '>=', $start)
            ->selectRaw('DATE(clicked_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $days = [];
        for ($i = $this->trendDays - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[] = [
                'label' => now()->subDays($i)->format('M j'),
                'count' => $raw[$date] ?? 0,
            ];
        }

        $max = max(array_column($days, 'count')) ?: 1;

        return [
            'days' => $days,
            'max' => $max,
            'total' => array_sum(array_column($days, 'count')),
        ];
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
                                <a href="{{ url('/s/' . $link->short_code) }}" target="_blank" class="text-brand text-sm truncate hover:underline">
                                    {{ url('/s/' . $link->short_code) }}
                                </a>
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
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText(@js(url('/s/' . $link->short_code))); copied = true; setTimeout(() => copied = false, 1500)"
                            class="h-9 px-3.5 inline-flex items-center gap-2 text-sm rounded-pill bg-brand text-surface-black font-medium hover:bg-brand-dark transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375V9.375a1.125 1.125 0 0 0-1.125-1.125h-9.75a1.125 1.125 0 0 0-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125h9.75Z" />
                            </svg>
                            <span x-text="copied ? 'Copied' : 'Share'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Clicks Trend --}}
            <div class="border border-surface-border rounded-2xl p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg text-surface-off-white">Clicks Trend</h2>
                    <div class="inline-flex items-center rounded-pill border border-surface-border p-0.5">
                        <button
                            type="button"
                            wire:click="setTrendDays(7)"
                            class="px-3 py-1 text-xs rounded-pill transition-colors {{ $trendDays === 7 ? 'bg-brand/10 text-brand border border-brand/30' : 'text-surface-mid-gray hover:text-surface-off-white' }}"
                        >
                            7 days
                        </button>
                        <button
                            type="button"
                            wire:click="setTrendDays(30)"
                            class="px-3 py-1 text-xs rounded-pill transition-colors {{ $trendDays === 30 ? 'bg-brand/10 text-brand border border-brand/30' : 'text-surface-mid-gray hover:text-surface-off-white' }}"
                        >
                            30 days
                        </button>
                    </div>
                </div>

                @if($this->trendData['total'] === 0)
                    <div class="py-10 text-center">
                        <p class="text-sm text-surface-mid-gray">No clicks in this period</p>
                    </div>
                @else
                    <div class="flex items-end justify-center {{ $trendDays === 7 ? 'gap-2' : 'gap-1.5' }}" aria-label="Clicks trend chart" role="img">
                        @foreach($this->trendData['days'] as $day)
                            <div class="{{ $trendDays === 7 ? 'w-10' : 'w-3.5 sm:w-5' }} shrink-0 flex flex-col items-center gap-1 group">
                                <div class="w-full h-32 flex items-end">
                                    <div class="w-full rounded-t-sm transition-colors relative {{ $day['count'] > 0 ? 'bg-brand/40 hover:bg-brand/60 min-h-[4px]' : 'bg-surface-border min-h-px' }}"
                                         title="{{ $day['label'] }}: {{ $day['count'] }} clicks"
                                         style="height: {{ $day['count'] > 0 ? max(4, round(($day['count'] / $this->trendData['max']) * 128)) : 2 }}px">
                                    </div>
                                </div>
                                <span class="text-[10px] text-surface-dark-gray leading-none hidden sm:block h-3 text-center">
                                    @if(count($this->trendData['days']) <= 10 || $loop->index % 5 === 0 || $loop->last)
                                        {{ $day['label'] }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
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
