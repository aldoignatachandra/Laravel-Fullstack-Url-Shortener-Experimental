<?php

use App\Services\Auth\PasswordResetOtpService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset code.
     */
    public function sendOtp(PasswordResetOtpService $passwordResetOtpService): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = $passwordResetOtpService->sendOtp(request(), $validated['email']);

        session()->flash('status', 'We sent a reset code to your email. Check your inbox and spam folder.');

        $this->redirectRoute('password.otp', ['email' => $email], navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-surface-mid-gray">
        {{ __('Forgot your password? Enter your email and we\'ll send you a reset code.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendOtp">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between mt-6">
            <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-surface-mid-gray hover:text-surface-off-white transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to login') }}
            </a>

            <x-primary-button wire:loading.attr="disabled" wire:target="sendOtp">
                <span wire:loading.remove wire:target="sendOtp">
                    {{ __('Send Reset Code') }}
                </span>

                <span wire:loading wire:target="sendOtp" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                    {{ __('Sending...') }}
                </span>
            </x-primary-button>
        </div>
    </form>
</div>
