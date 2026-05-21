<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <title>401 — Unauthorized | shrt.dev</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-surface-dark text-surface-off-white min-h-screen flex flex-col">
    <div class="flex-1 flex items-center justify-center px-6">
        <div class="max-w-md w-full text-center">
            {{-- Brand --}}
            <a href="{{ route('landing') }}" wire:navigate
                class="inline-block text-2xl font-bold text-brand tracking-tight mb-12 hover:opacity-80 transition-opacity">
                shrt.dev
            </a>

            {{-- Large 401 --}}
            <div class="relative mb-6">
                <h1
                    class="text-[9rem] sm:text-[11rem] font-bold leading-[0.85] tracking-tighter text-surface-off-white select-none">
                    401
                </h1>
                {{-- Subtle decorative underline --}}
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-16 h-0.5 bg-brand/50 rounded-full"></div>
            </div>

            {{-- Message --}}
            <h2 class="text-xl sm:text-2xl font-semibold text-surface-off-white mb-3">
                Unauthorized
            </h2>

            <p class="text-surface-mid-gray text-sm sm:text-base leading-relaxed mb-10 max-w-xs mx-auto">
                You need to sign in to access this page. Log in to your account or create one to continue.
            </p>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('login') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-brand text-surface-black px-6 py-2.5 text-sm font-medium rounded-md hover:bg-brand-dark transition-colors">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    Back to Login
                </a>
                <a href="{{ route('register') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-surface-black text-surface-off-white px-6 py-2.5 text-sm font-medium rounded-md border border-surface-border hover:border-surface-mid-border transition-colors">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Register Account
                </a>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-surface-border py-6 text-center">
        <p class="text-surface-mid-gray text-xs">
            &copy; {{ date('Y') }} shrt.dev
        </p>
    </footer>
</body>

</html>
