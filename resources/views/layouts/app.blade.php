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
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-all duration-200 shadow-md shadow-indigo-500/25 ring-1 ring-indigo-400/20 hover:shadow-indigo-500/40 hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New scan
                </a>
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
            targetUrl: null,
            _timer: null,

            start(rawUrl, formEl) {
                // Parse display domain
                let d = rawUrl.trim() || '...';
                try {
                    if (!d.startsWith('http')) d = 'https://' + d;
                    d = new URL(d).hostname.replace(/^www\./i, '');
                } catch(e) {}
                this.domain    = d;
                this.targetUrl = null;
                this.activeIdx = 0;
                this.doneUpto  = -1;
                this.show      = true;
                clearInterval(this._timer);

                // Animation: advance one scanner every 2400ms.
                // The LAST scanner stays active (spinning) until the fetch resolves.
                this._timer = setInterval(() => {
                    if (this.activeIdx < this.scanners.length - 1) {
                        this.doneUpto = this.activeIdx;
                        this.activeIdx++;
                    } else if (this.targetUrl) {
                        // Last scanner done + scan complete → finish & navigate
                        this.doneUpto = this.activeIdx;
                        clearInterval(this._timer);
                        setTimeout(() => { window.location.href = this.targetUrl; }, 500);
                    }
                    // else: hold on last scanner, waiting for server response
                }, 2400);

                // Submit via fetch — prevents the browser navigating before animation ends
                const data = new FormData(formEl);
                fetch(formEl.action, { method: 'POST', body: data, redirect: 'follow' })
                    .then(res => {
                        const url = res.url;
                        // Check if we landed on a scan result page
                        if (/\/scan\/[^\/]+/.test(url)) {
                            this.targetUrl = url;
                            // If animation already passed the last scanner, redirect now
                            if (this.activeIdx >= this.scanners.length - 1) {
                                this.doneUpto = this.scanners.length - 1;
                                clearInterval(this._timer);
                                setTimeout(() => { window.location.href = url; }, 500);
                            }
                        } else {
                            // Validation error or unexpected response — navigate immediately
                            window.location.href = url;
                        }
                    })
                    .catch(() => { window.location.href = '/'; });
            }
        }"
        @scan-start.window="start($event.detail.url, $event.detail.form)"
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

</body>
</html>
