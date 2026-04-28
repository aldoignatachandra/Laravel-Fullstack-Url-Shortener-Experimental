<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>shrt.dev — The Developer-First Link Shortener</title>
        <meta name="description" content="The developer-first link shortener. Create short links, track every click, and get real-time analytics.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-dark text-surface-off-white">
        <div class="min-h-screen flex flex-col">
            {{-- Navbar --}}
            <nav x-data="{ open: false }" class="fixed top-0 left-0 right-0 z-50 bg-surface-black/80 backdrop-blur-md border-b border-surface-dark-border">
                <div class="w-full max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <a href="{{ route('landing') }}" wire:navigate class="text-brand text-2xl tracking-tight font-bold">
                            shrt.dev
                        </a>

                        <div class="hidden md:flex items-center gap-6">
                            <a href="#features" class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                                Features
                            </a>
                            <a href="#developers" class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                                Developers
                            </a>
                        </div>
                    </div>

                    <div class="hidden md:flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" wire:navigate class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                            Open Dashboard
                        </a>
                        <a href="{{ route('register') }}" wire:navigate class="bg-brand text-surface-black rounded-md px-5 py-2 text-sm font-medium hover:bg-brand-dark transition-colors">
                            Get Started Free
                        </a>
                    </div>

                    {{-- Mobile hamburger --}}
                    <button @click="open = !open" class="md:hidden text-surface-off-white p-2">
                        <svg x-show="!open" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg x-show="open" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Mobile menu --}}
                <div x-show="open" @click.away="open = false" class="md:hidden border-t border-surface-dark-border bg-surface-black/95 backdrop-blur-md">
                    <div class="px-6 py-4 flex flex-col gap-4">
                        <a href="#features" @click="open = false" class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                            Features
                        </a>
                        <a href="#developers" @click="open = false" class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                            Developers
                        </a>
                        <a href="{{ route('dashboard') }}" wire:navigate class="text-surface-light-gray hover:text-surface-off-white transition-colors text-sm">
                            Open Dashboard
                        </a>
                        <a href="{{ route('register') }}" wire:navigate class="bg-brand text-surface-black rounded-md px-5 py-2 text-sm font-medium hover:bg-brand-dark transition-colors text-center">
                            Get Started Free
                        </a>
                    </div>
                </div>
            </nav>

            {{-- Hero Section --}}
            <section class="relative flex flex-col items-center px-6 text-center pt-28 pb-20">
                <div class="max-w-5xl mx-auto">
                    <span class="inline-block text-[11px] tracking-[0.2em] text-brand/70 uppercase font-semibold mb-6">
                        Open Source Link Management
                    </span>

                    <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold leading-[0.9] tracking-tight mb-8">
                        <span class="block text-surface-off-white">Shorten in seconds</span>
                        <span class="block text-brand">Scale to millions</span>
                    </h1>

                    <p class="text-lg text-surface-mid-gray max-w-2xl mx-auto mb-10 leading-relaxed">
                        The developer-first link shortener. Create short links, track every click, and get real-time analytics — all with a beautiful dark dashboard.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ route('register') }}" wire:navigate class="bg-brand text-surface-black rounded-md px-8 py-3 text-sm font-medium hover:bg-brand-dark transition-colors">
                            Get Started Free
                        </a>
                        <a href="{{ route('dashboard') }}" wire:navigate class="bg-surface-black text-surface-off-white rounded-md px-8 py-3 text-sm font-medium border border-surface-border hover:border-surface-mid-border transition-colors">
                            Open Dashboard
                        </a>
                    </div>
                </div>
            </section>

            {{-- Stats Bar --}}
            <section class="border-y border-surface-border">
                <div class="w-full max-w-5xl mx-auto px-6 py-8">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                        <div class="flex flex-col items-center">
                            <span class="text-3xl md:text-4xl font-bold text-brand mb-1">10M+</span>
                            <span class="text-sm text-surface-mid-gray">Links Shortened</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-3xl md:text-4xl font-bold text-brand mb-1">99.9%</span>
                            <span class="text-sm text-surface-mid-gray">Uptime</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-3xl md:text-4xl font-bold text-brand mb-1">&lt;10ms</span>
                            <span class="text-sm text-surface-mid-gray">Redirect Time</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features Section --}}
            <section id="features" class="w-full max-w-6xl mx-auto px-6 py-16">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-surface-off-white mb-4">
                        Everything you need
                    </h2>
                    <p class="text-lg text-surface-mid-gray max-w-xl mx-auto">
                        Short links. Powerful analytics. Simple dashboard.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Card 1 --}}
                    <div class="bg-surface-dark border border-surface-border rounded-2xl p-8 hover:border-brand/30 transition-all duration-300">
                        <div class="w-12 h-12 rounded-lg bg-brand/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </div>
                        <h3 class="text-surface-off-white text-lg font-medium mb-3">Lightning Short Links</h3>
                        <p class="text-surface-mid-gray text-sm leading-relaxed">
                            Transform long URLs into clean, memorable short links instantly. Custom aliases, QR codes, and deep links coming soon.
                        </p>
                    </div>

                    {{-- Card 2 --}}
                    <div class="bg-surface-dark border border-surface-border rounded-2xl p-8 hover:border-brand/30 transition-all duration-300">
                        <div class="w-12 h-12 rounded-lg bg-brand/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-surface-off-white text-lg font-medium mb-3">Real-time Analytics</h3>
                        <p class="text-surface-mid-gray text-sm leading-relaxed">
                            Track every click with detailed analytics. Monitor referrers, devices, locations, and click trends in real-time.
                        </p>
                    </div>

                    {{-- Card 3 --}}
                    <div class="bg-surface-dark border border-surface-border rounded-2xl p-8 hover:border-brand/30 transition-all duration-300">
                        <div class="w-12 h-12 rounded-lg bg-brand/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <h3 class="text-surface-off-white text-lg font-medium mb-3">Developer-First Dashboard</h3>
                        <p class="text-surface-mid-gray text-sm leading-relaxed">
                            A beautiful dark dashboard built for developers. Manage links, view reports, and integrate with your workflow.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Code Preview Section --}}
            <section id="developers" class="w-full max-w-4xl mx-auto px-6 py-12">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-4xl font-bold text-surface-off-white mb-4">
                        Built for developers
                    </h2>
                    <p class="text-lg text-surface-mid-gray max-w-xl mx-auto">
                        Simple API. Powerful features. Integrate in minutes.
                    </p>
                </div>

                <div class="bg-surface-black rounded-xl border border-surface-border overflow-hidden shadow-[0_0_30px_rgba(62,207,142,0.05)]">
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-surface-border bg-surface-dark/50">
                        <div class="w-3 h-3 rounded-full bg-[#ff5f57]"></div>
                        <div class="w-3 h-3 rounded-full bg-[#febc2e]"></div>
                        <div class="w-3 h-3 rounded-full bg-[#28c840]"></div>
                        <span class="ml-2 text-xs text-surface-mid-gray font-mono">terminal</span>
                    </div>
                    <div class="p-6 font-mono text-sm overflow-x-auto">
                        <div class="text-surface-mid-gray mb-1">$ curl -X POST <span class="text-brand">https://shrt.dev/api/links</span> \</div>
                        <div class="text-surface-mid-gray mb-1">  -H <span class="text-surface-off-white">"Authorization: Bearer YOUR_TOKEN"</span> \</div>
                        <div class="text-surface-mid-gray mb-4">  -d <span class="text-surface-off-white">"url=https://example.com/very-long-url"</span></div>
                        <div class="text-surface-mid-gray mb-1">{</div>
                        <div class="text-surface-off-white mb-1">  "short_url": <span class="text-brand">"https://shrt.dev/s/abc123"</span>,</div>
                        <div class="text-surface-off-white mb-1">  "original_url": <span class="text-brand">"https://example.com/very-long-url"</span>,</div>
                        <div class="text-surface-off-white mb-1">  "clicks": <span class="text-brand">0</span></div>
                        <div class="text-surface-mid-gray">}</div>
                    </div>
                </div>
            </section>

            {{-- Bottom CTA Section --}}
            <section class="w-full bg-surface-black border-y border-surface-border">
                <div class="max-w-4xl mx-auto px-6 py-16 text-center">
                    <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-[0.95] tracking-tight mb-6">
                        <span class="block text-surface-off-white">Ready to shorten</span>
                        <span class="block text-brand">your first link?</span>
                    </h2>
                    <p class="text-lg text-surface-mid-gray max-w-xl mx-auto mb-10">
                        Get started for free. No credit card required.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ route('register') }}" wire:navigate class="bg-brand text-surface-black rounded-md px-8 py-3 text-sm font-medium hover:bg-brand-dark transition-colors">
                            Get Started Free
                        </a>
                        <a href="{{ route('dashboard') }}" wire:navigate class="bg-surface-black text-surface-off-white rounded-md px-8 py-3 text-sm font-medium border border-surface-border hover:border-surface-mid-border transition-colors">
                            Open Dashboard
                        </a>
                    </div>
                </div>
            </section>

            {{-- Footer --}}
            <footer class="w-full border-t border-surface-border">
                <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-surface-mid-gray text-sm">&copy; 2026 shrt.dev</span>
                    <div class="flex items-center gap-6">
                        <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-surface-mid-gray hover:text-surface-light-gray text-sm transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0 0 22 12.017C22 6.484 17.522 2 12 2Z" clip-rule="evenodd" />
                            </svg>
                            GitHub
                        </a>
                        <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 text-xs font-medium text-surface-light-gray border border-surface-border rounded-full px-3 py-1 hover:border-surface-mid-border transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
                            Open Source
                        </a>
                        <a href="{{ route('login') }}" wire:navigate class="text-surface-mid-gray hover:text-surface-light-gray text-sm transition-colors">Login</a>
                        <a href="{{ route('register') }}" wire:navigate class="text-surface-mid-gray hover:text-surface-light-gray text-sm transition-colors">Register</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
