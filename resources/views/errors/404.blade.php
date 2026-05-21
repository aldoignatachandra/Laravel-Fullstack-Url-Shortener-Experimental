<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <title>404 — Page not found | shrt.dev</title>

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

            {{-- Large 404 --}}
            <div class="relative mb-6">
                <h1
                    class="text-[9rem] sm:text-[11rem] font-bold leading-[0.85] tracking-tighter text-surface-off-white select-none">
                    404
                </h1>
                {{-- Subtle decorative underline --}}
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-16 h-0.5 bg-brand/50 rounded-full"></div>
            </div>

            {{-- Message --}}
            <h2 class="text-xl sm:text-2xl font-semibold text-surface-off-white mb-3">
                Page not found
            </h2>

            <p class="text-surface-mid-gray text-sm sm:text-base leading-relaxed mb-10 max-w-xs mx-auto">
                The page you're looking for doesn't exist or has been moved. Check the URL or head back home.
            </p>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-brand text-surface-black px-6 py-2.5 text-sm font-medium rounded-md hover:bg-brand-dark transition-colors">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Go to Dashboard
                </a>
                <a href="{{ route('landing') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-surface-black text-surface-off-white px-6 py-2.5 text-sm font-medium rounded-md border border-surface-border hover:border-surface-mid-border transition-colors">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Back to Home
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
