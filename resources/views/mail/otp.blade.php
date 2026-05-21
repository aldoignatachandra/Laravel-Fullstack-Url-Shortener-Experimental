<x-mail::message>
    # Password Reset Code

    Hello {{ $user->name ?? 'there' }},

    You requested a password reset for your **shrt.dev** account. Use the code below to reset your password:

    <x-mail::panel>
        <div style="text-align: center; padding: 20px;">
            <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0;">Your verification code</p>
            <p
                style="font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #3ecf8e; margin: 0; font-family: monospace;">
                {{ $otp }}</p>
        </div>
    </x-mail::panel>

    **This code expires in 15 minutes.**

    If you didn't request this password reset, you can safely ignore this email. Your password will remain unchanged.

    <x-mail::button :url="config('app.url')">
        Go to shrt.dev
    </x-mail::button>

    Thanks,<br>
    The **shrt.dev** Team
</x-mail::message>
