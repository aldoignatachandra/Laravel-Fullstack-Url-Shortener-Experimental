<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Url]
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! $this->email) {
            $this->redirectRoute('password.request', navigate: true);
        }
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-surface-mid-gray">
        {{ __('We\'ve sent a 6-digit reset code to your email. Enter it below.') }}
    </div>

    <form method="POST" action="{{ route('password.verify') }}">
        @csrf
        <!-- Email (hidden) -->
        <input type="hidden" name="email" value="{{ $email }}" />

        <!-- OTP -->
        <div>
            <x-input-label for="otp" :value="__('Reset Code')" />
            <x-text-input id="otp" class="block mt-1 w-full text-center text-2xl tracking-[0.5em]" type="text" name="otp" maxlength="6" required autofocus autocomplete="one-time-code" placeholder="000000" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('password.request') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-surface-mid-gray hover:text-surface-off-white transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back') }}
            </a>

            <x-primary-button>
                {{ __('Verify Code') }}
            </x-primary-button>
        </div>
    </form>
</div>
