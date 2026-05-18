<?php

use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! session()->has('otp_verified_email')) {
            $this->redirectRoute('password.request', navigate: true);
        }
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(PasswordResetOtpService $passwordResetOtpService): void
    {
        if (! session()->has('otp_verified_email')) {
            $this->redirectRoute('password.request', navigate: true);

            return;
        }

        $validated = $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! $passwordResetOtpService->resetPassword($validated['password'])) {
            $this->redirectRoute('password.request', navigate: true);

            return;
        }

        session()->flash('status', 'Your password has been reset!');

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <form wire:submit="resetPassword">
        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <x-input-label for="password" :value="__('New Password')" />

            <div class="relative mt-1">
                <x-text-input wire:model="password" id="password" class="block w-full pr-12" type="password"
                              x-bind:type="showPassword ? 'text' : 'password'"
                              name="password" required autocomplete="new-password" autofocus />

                <button type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-surface-mid-gray hover:text-surface-off-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-dark focus:text-brand transition-colors"
                        x-on:click="showPassword = ! showPassword"
                        x-bind:aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                    <svg x-show="! showPassword" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>

                    <svg x-show="showPassword" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                          type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between mt-6">
            <a href="{{ route('password.request') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-surface-mid-gray hover:text-surface-off-white transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Start over') }}
            </a>

            <x-primary-button wire:loading.attr="disabled" wire:target="resetPassword">
                <span wire:loading.remove wire:target="resetPassword">
                    {{ __('Reset Password') }}
                </span>

                <span wire:loading wire:target="resetPassword" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                    {{ __('Resetting...') }}
                </span>
            </x-primary-button>
        </div>
    </form>
</div>
