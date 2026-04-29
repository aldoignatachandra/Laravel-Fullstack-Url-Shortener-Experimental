<?php

use App\Services\Auth\PasswordResetOtpService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Url]
    public string $email = '';

    public string $otp = '';

    public int $cooldownSeconds = 0;

    /**
     * Mount the component.
     */
    public function mount(PasswordResetOtpService $passwordResetOtpService): void
    {
        if (! $this->email) {
            $this->redirectRoute('password.request', navigate: true);

            return;
        }

        $this->cooldownSeconds = $passwordResetOtpService->resendCooldownRemaining($this->email);
    }

    /**
     * Verify the password reset code.
     */
    public function verifyOtp(PasswordResetOtpService $passwordResetOtpService): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $passwordResetOtpService->verifyOtp(request(), $validated['email'], $validated['otp']);

        $this->redirectRoute('password.reset', navigate: true);
    }

    /**
     * Send a new password reset code.
     */
    public function resendOtp(PasswordResetOtpService $passwordResetOtpService): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $remaining = $passwordResetOtpService->resendCooldownRemaining($validated['email']);

        if ($remaining > 0) {
            $this->addError('email', "Please wait {$remaining} seconds before requesting another code.");

            return;
        }

        $this->email = $passwordResetOtpService->sendOtp(request(), $validated['email']);
        $this->otp = '';
        $this->cooldownSeconds = $passwordResetOtpService::RESEND_COOLDOWN_SECONDS;

        $this->dispatch('resend-cooldown');

        session()->flash('status', 'We sent a new reset code to your email. Check your inbox and spam folder.');
    }
}; ?>

<div x-data="{
    countdown: 0,
    timer: null,
    init() {
        this.startCountdown(parseInt(this.$el.dataset.cooldown) || 0);
    },
    startCountdown(seconds) {
        this.countdown = seconds;
        if (this.timer) clearInterval(this.timer);
        if (seconds <= 0) return;
        this.timer = setInterval(() => {
            this.countdown = Math.max(0, this.countdown - 1);
            if (this.countdown <= 0) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }, 1000);
    }
}" data-cooldown="{{ $cooldownSeconds }}" x-on:resend-cooldown.window="startCountdown(60)">
    <div class="mb-4 text-sm text-surface-mid-gray">
        {{ __('We\'ve sent a 6-digit reset code to your email. Enter it below.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="verifyOtp">
        <!-- OTP -->
        <div>
            <x-input-label for="otp" :value="__('Reset Code')" />
            <x-text-input wire:model="otp" id="otp" class="block mt-1 w-full text-center text-2xl tracking-[0.5em]" type="text" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]*" required autofocus autocomplete="one-time-code" placeholder="000000" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-3 text-sm text-surface-mid-gray">
            {{ __('Didn\'t receive a code?') }}
            <span x-show="countdown > 0" x-cloak class="text-surface-off-white/60" x-text="'Resend in ' + countdown + 's'"></span>
            <button type="button"
                    x-show="countdown <= 0"
                    x-cloak
                    wire:click="resendOtp"
                    wire:loading.attr="disabled"
                    wire:target="resendOtp"
                    class="font-medium text-brand hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-dark rounded-sm disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="resendOtp">{{ __('Send a new code') }}</span>
                <span wire:loading wire:target="resendOtp">{{ __('Sending...') }}</span>
            </button>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('password.request') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-surface-mid-gray hover:text-surface-off-white transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back') }}
            </a>

            <x-primary-button wire:loading.attr="disabled" wire:target="verifyOtp">
                <span wire:loading.remove wire:target="verifyOtp">
                    {{ __('Verify Code') }}
                </span>

                <span wire:loading wire:target="verifyOtp" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                    {{ __('Verifying...') }}
                </span>
            </x-primary-button>
        </div>
    </form>
</div>
