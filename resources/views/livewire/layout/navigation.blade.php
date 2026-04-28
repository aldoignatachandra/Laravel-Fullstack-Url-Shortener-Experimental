<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ sidebarOpen: false }" class="flex">
    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/60 z-40 lg:hidden"
         x-cloak></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed lg:static lg:translate-x-0 inset-y-0 left-0 z-50 w-64 bg-surface-dark border-r border-surface-border flex flex-col transition-transform duration-300 ease-in-out"
           x-cloak>
        <!-- Logo -->
        <div class="flex items-center h-16 px-6 border-b border-surface-border shrink-0">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-xl font-bold text-brand tracking-tight">
                shrt.dev
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}"
               wire:navigate
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-md transition-colors duration-150
                      {{ request()->routeIs('dashboard')
                         ? 'bg-surface-border text-brand border-l-2 border-brand'
                         : 'text-surface-mid-gray hover:text-surface-off-white hover:bg-surface-border border-l-2 border-transparent' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-brand' : 'text-surface-mid-gray group-hover:text-surface-off-white' }}"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                {{ __('Dashboard') }}
            </a>

            <a href="{{ route('links.index') }}"
               wire:navigate
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-md transition-colors duration-150
                      {{ request()->routeIs('links.index')
                         ? 'bg-surface-border text-brand border-l-2 border-brand'
                         : 'text-surface-mid-gray hover:text-surface-off-white hover:bg-surface-border border-l-2 border-transparent' }}">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('links.index') ? 'text-brand' : 'text-surface-mid-gray group-hover:text-surface-off-white' }}"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                {{ __('Short URLs') }}
            </a>
        </nav>

        <!-- Bottom Section -->
        <div class="px-3 py-4 border-t border-surface-border shrink-0">
            <div class="flex items-center gap-3 px-3 mb-3">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-surface-off-white truncate"
                         x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                         x-text="name"
                         x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-xs text-surface-mid-gray truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <button wire:click="logout"
                    class="flex w-full items-center gap-2 px-3 py-2 text-sm font-medium text-surface-mid-gray rounded-md hover:text-surface-off-white hover:bg-surface-border transition-colors duration-150">
                <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                {{ __('Log Out') }}
            </button>
        </div>
    </aside>

    <!-- Mobile Hamburger (visible only when sidebar is closed) -->
    <button @click="sidebarOpen = !sidebarOpen"
            class="fixed top-4 left-4 z-30 p-2 rounded-md text-surface-mid-gray hover:text-surface-off-white hover:bg-surface-border border border-surface-border bg-surface-dark lg:hidden transition-colors duration-150">
        <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>
