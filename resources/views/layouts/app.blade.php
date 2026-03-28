<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WebCheckApp — Website Security Scanner')</title>
    <meta name="description" content="@yield('meta_description', 'Instantly scan your website for security issues, performance problems and get actionable recommendations.')">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="WebCheckApp">
    <meta property="og:title" content="@yield('og_title', 'WebCheckApp — Free Website Security Scanner')">
    <meta property="og:description" content="@yield('og_description', 'Instantly scan your website for security issues, performance problems and get actionable recommendations.')">
    <meta property="og:url" content="@yield('og_url', url()->current())">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('og_title', 'WebCheckApp — Free Website Security Scanner')">
    <meta name="twitter:description" content="@yield('og_description', 'Instantly scan your website for security issues, performance problems and get actionable recommendations.')">

    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    @yield('structured_data')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-950 text-white antialiased" x-data>

    <nav class="border-b border-white/5 bg-gray-900/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-lg">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-white">WebCheck<span class="text-indigo-400">App</span></span>
                </a>
                <div class="flex items-center gap-2">
                    @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 px-3.5 py-2 rounded-lg transition-all duration-200">
                        <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Dashboard
                    </a>
                    @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-gray-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 px-3.5 py-2 rounded-lg transition-all duration-200">
                        <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign in
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-white bg-indigo-600/80 hover:bg-indigo-600 border border-indigo-500/50 hover:border-indigo-400/60 px-3.5 py-2 rounded-lg transition-all duration-200 shadow-sm shadow-indigo-500/20">
                        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Register
                    </a>
                    @endauth
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-all duration-200 shadow-md shadow-indigo-500/25 ring-1 ring-indigo-400/20 hover:shadow-indigo-500/40 hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New scan
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    {{-- Global scan loading overlay --}}
    <div
        x-data="{
            show: false,
            domain: '',
            scanners: [
                'SSL & HTTPS',
                'Security Headers',
                'DNS & Email Security',
                'Performance & SEO',
                'Content & CMS',
                'Technology Stack',
                'Malware & Reputation',
                'Open Ports',
                'Exposed Files',
                'Privacy & GDPR',
                'Trust & WHOIS',
            ],
            activeIdx: 0,
            doneUpto: -1,
            _timer: null,

            start(rawUrl) {
                // Parse display domain
                let d = rawUrl.trim() || '...';
                try {
                    if (!d.startsWith('http')) d = 'https://' + d;
                    d = new URL(d).hostname.replace(/^www\./i, '');
                } catch(e) {}
                this.domain    = d;
                this.activeIdx = 0;
                this.doneUpto  = -1;
                this.show      = true;
                clearInterval(this._timer);

                // Advance one scanner every 2400ms, then hold at the last one
                // until the browser naturally navigates to the scan result page.
                this._timer = setInterval(() => {
                    if (this.activeIdx < this.scanners.length - 1) {
                        this.doneUpto = this.activeIdx;
                        this.activeIdx++;
                    }
                }, 2400);
            }
        }"
        @scan-start.window="start($event.detail.url)"
        x-show="show"
        x-cloak
        style="display:none"
        class="fixed inset-0 z-[9999] bg-gray-950 flex flex-col items-center justify-center px-6"
    >
        {{-- Top glow --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative w-full max-w-sm text-center">

            {{-- Logo --}}
            <div class="flex items-center justify-center gap-2 mb-10 text-lg font-bold">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>WebCheck<span class="text-indigo-400">App</span></span>
            </div>

            {{-- Title --}}
            <p class="text-sm text-gray-500 uppercase tracking-widest mb-2">Analyzing security for</p>
            <h2 class="text-2xl font-bold text-white truncate mb-8" x-text="domain"></h2>

            {{-- Progress bar --}}
            <div class="w-full h-1 bg-white/5 rounded-full mb-8 overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-700 ease-out"
                    :style="'width:' + Math.round(((doneUpto + 1) / scanners.length) * 100) + '%'"
                ></div>
            </div>

            {{-- Scanner list --}}
            <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-left mb-10">
                <template x-for="(scanner, idx) in scanners" :key="idx">
                    <div class="flex items-center gap-2.5 text-sm">
                        {{-- Done --}}
                        <span x-show="idx <= doneUpto" class="flex-shrink-0 w-4 h-4 rounded-full bg-green-500/20 flex items-center justify-center">
                            <svg class="w-2.5 h-2.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        {{-- Active / spinning --}}
                        <span x-show="idx === activeIdx && idx > doneUpto" class="flex-shrink-0 w-4 h-4">
                            <svg class="w-4 h-4 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        {{-- Pending --}}
                        <span x-show="idx > activeIdx" class="flex-shrink-0 w-4 h-4 flex items-center justify-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-white/15"></span>
                        </span>
                        <span
                            :class="{
                                'text-green-400': idx <= doneUpto,
                                'text-white font-medium': idx === activeIdx && idx > doneUpto,
                                'text-gray-600': idx > activeIdx
                            }"
                            x-text="scanner"
                        ></span>
                    </div>
                </template>
            </div>

            {{-- Footer hint --}}
            <p class="text-xs text-gray-600" x-show="doneUpto < scanners.length - 1">This usually takes 20–40 seconds. Please wait&hellip;</p>
            <p class="text-xs text-indigo-400 animate-pulse" x-show="doneUpto >= scanners.length - 1 && !targetUrl">Generating your report&hellip;</p>

        </div>
    </div>

    <footer class="border-t border-white/5 mt-20 py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                {{-- Brand + made by --}}
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>&copy; {{ date('Y') }} WebCheckApp</span>
                    <span class="text-gray-700">&mdash;</span>
                    <span>Made by <a href="https://www.budgetpixels.nl" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300 transition-colors">BudgetPixels.nl</a></span>
                </div>

                {{-- Legal links --}}
                <nav class="flex items-center gap-5 text-sm text-gray-600">
                    <a href="{{ route('api.docs') }}" class="hover:text-gray-400 transition-colors">API</a>
                    <a href="{{ route('disclaimer') }}" class="hover:text-gray-400 transition-colors">Disclaimer</a>
                    <a href="{{ route('privacy') }}" class="hover:text-gray-400 transition-colors">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="hover:text-gray-400 transition-colors">Terms of Use</a>
                </nav>
            </div>

            <p class="mt-4 text-center text-xs text-gray-700">
                Scan results are for informational purposes only and do not constitute professional security advice.
            </p>
        </div>
    </footer>

    {{-- Cookie consent banner --}}
    <div
        x-data="{
            show: false,
            init() {
                if (! localStorage.getItem('cookie_consent')) {
                    setTimeout(() => this.show = true, 800);
                }
            },
            accept() {
                localStorage.setItem('cookie_consent', 'accepted');
                this.show = false;
            },
            decline() {
                localStorage.setItem('cookie_consent', 'declined');
                this.show = false;
            }
        }"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        style="display:none"
        class="fixed bottom-0 inset-x-0 z-[9998] p-4"
    >
        <div class="max-w-4xl mx-auto bg-gray-900 border border-white/10 rounded-2xl shadow-2xl shadow-black/40 p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">

            {{-- Cookie icon --}}
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>

            {{-- Text --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-300 leading-relaxed">
                    We use functional cookies to keep your session working and remember your preferences. No tracking or advertising cookies are used.
                    <a href="{{ route('privacy') }}" class="text-indigo-400 hover:text-indigo-300 underline underline-offset-2 transition-colors whitespace-nowrap">Privacy Policy</a>
                </p>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                <button
                    @click="decline"
                    class="text-sm text-gray-500 hover:text-gray-300 transition-colors px-3 py-2"
                >
                    Decline
                </button>
                <button
                    @click="accept"
                    class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-all shadow-md shadow-indigo-500/20"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Accept
                </button>
            </div>

        </div>
    </div>

</body>
</html>
