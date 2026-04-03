@extends('layouts.app')

@section('title', 'WebCheckApp — Free Website Security Scanner')
@section('meta_description', 'Instantly scan any website for security issues, misconfigurations and vulnerabilities. Get a detailed report with score, grade and actionable fixes.')

@section('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": "WebCheckApp",
    "url": "{{ url('/') }}",
    "logo": "{{ url('/og-image.png') }}",
    "description": "Free website security scanner that checks SSL, headers, DNS, performance and 19 security categories.",
    "sameAs": []
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "WebCheckApp",
    "url": "{{ url('/') }}",
    "applicationCategory": "SecurityApplication",
    "operatingSystem": "Any",
    "offers": [
        {
            "@@type": "Offer",
            "name": "Quick Scan",
            "price": "0",
            "priceCurrency": "EUR",
            "description": "Free security scan with 5 scanners"
        },
        {
            "@@type": "Offer",
            "name": "Pro Scan",
            "price": "9.99",
            "priceCurrency": "EUR",
            "description": "20 security scanners + OWASP Top 10 analysis + PDF report"
        },
        {
            "@@type": "Offer",
            "name": "Deep Scan",
            "price": "29.99",
            "priceCurrency": "EUR",
            "description": "27 security scanners + OWASP Top 10 + penetration checks + PDF report"
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "What does WebCheckApp scan for?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "WebCheckApp runs up to 27 security scanners covering SSL/HTTPS, security headers, DNS & email security, OWASP Top 10, performance, content security, exposed files, open ports, TLS cipher strength, privacy & GDPR compliance, malware & reputation, accessibility, and more."
            }
        },
        {
            "@@type": "Question",
            "name": "Is WebCheckApp free to use?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The Quick Scan is completely free — no account required. It covers 5 core security categories. For a deeper analysis with OWASP Top 10, malware detection, and 20+ more scanners, Pro Scan is available for \u20ac9.99 and Deep Scan for \u20ac29.99 per scan."
            }
        },
        {
            "@@type": "Question",
            "name": "What is the difference between Quick, Pro, and Deep Scan?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Quick Scan (free) runs 5 basic scanners: SSL, headers, DNS, performance, and content. Pro Scan (\u20ac9.99) adds 15 more scanners plus an OWASP Top 10 analysis and PDF report. Deep Scan (\u20ac29.99) includes everything in Pro plus 7 advanced penetration-style checks like directory discovery, XSS reflection testing, and HTTP method analysis."
            }
        },
        {
            "@@type": "Question",
            "name": "What is the OWASP Top 10?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The OWASP Top 10 is a globally recognized standard for web application security risks, published by the Open Web Application Security Project. It covers the ten most critical security vulnerabilities including broken access control, cryptographic failures, injection attacks, and more. Our Pro and Deep scans map your results against all ten OWASP categories."
            }
        },
        {
            "@@type": "Question",
            "name": "How long does a security scan take?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A Quick Scan completes in about 15 seconds. A Pro Scan takes 30-60 seconds. A Deep Scan with all 27 scanners typically finishes in 60-90 seconds. Results are displayed in real-time as each check completes."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to install anything on my website?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. WebCheckApp scans your website from the outside, just like a visitor or search engine would. No software installation, code changes, or server access is required."
            }
        },
        {
            "@@type": "Question",
            "name": "Is my website data stored or shared?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Scan results are stored so you can access your report later and compare it with future scans. We do not share your scan data with third parties. Only publicly accessible information is checked during a scan."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I monitor my website continuously?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. Create a free account and add up to 10 sites to your monitoring dashboard. You can rescan sites at any time, track score changes over time, and get notified when your security score drops or your SSL certificate is about to expire."
            }
        }
    ]
}
</script>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════
     HERO
═══════════════════════════════════════════ --}}
<section class="relative overflow-hidden">

    {{-- Mesh / glow background --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        {{-- Base image --}}
        <img src="/hero.jpg" alt="Website security scanner interface with scan results and vulnerability analysis" class="absolute inset-0 w-full h-full object-cover opacity-20">
        <div class="absolute inset-0 bg-gray-950/70"></div>
        {{-- Radial glows --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-indigo-600/12 rounded-full blur-[120px]"></div>
        <div class="absolute top-20 left-1/4 w-[400px] h-[300px] bg-purple-600/8 rounded-full blur-[80px]"></div>
        <div class="absolute top-40 right-1/4 w-[300px] h-[200px] bg-blue-600/8 rounded-full blur-[60px]"></div>
        {{-- Grid overlay --}}
        <div class="absolute inset-0 opacity-[0.025]"
             style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 48px 48px;"></div>
        {{-- Fade bottom --}}
        <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-gray-950 to-transparent"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-32 text-center">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/25 rounded-full px-4 py-1.5 text-sm text-indigo-300 mb-8 backdrop-blur-sm">
            <span class="w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></span>
            Free quick scan · Pro & Deep scans available
        </div>

        {{-- Headline --}}
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight mb-6 leading-[1.08]">
            Is your website<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400">
                actually secure?
            </span>
        </h1>

        <p class="text-lg sm:text-xl text-gray-400 mb-12 max-w-2xl mx-auto leading-relaxed">
            Scan any website in seconds. Get a detailed security report with score,
            grade and clear steps to fix every issue found.
        </p>

        {{-- Scan form with tier selection --}}
        @php $grantedTier = auth()->user()->granted_tier ?? null; @endphp
        <div x-data="{ tier: '{{ $grantedTier ?? 'free' }}', loading: false, url: '{{ old('url') }}' }">
            {{-- URL input --}}
            <div class="max-w-2xl mx-auto mb-6">
                <div class="relative">
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="scan-url"
                        x-model="url"
                        placeholder="example.com or https://example.com"
                        class="w-full bg-white/6 border border-white/12 rounded-2xl pl-12 pr-4 py-4 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white/8 transition-all text-lg backdrop-blur-sm"
                        autofocus
                    >
                </div>
                @error('url')
                    <p class="mt-3 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tier cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-3xl mx-auto mb-6">
                {{-- Quick Scan (Free) --}}
                <button @click="tier = 'free'" :class="tier === 'free' ? 'border-indigo-500 bg-indigo-500/10' : 'border-white/10 bg-white/3 hover:bg-white/5'" class="border rounded-2xl p-4 text-left transition-all duration-200 cursor-pointer">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">Quick Scan</span>
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">Free</span>
                    </div>
                    <p class="text-xs text-gray-500">5 scanners — SSL, Headers, DNS, Performance, Content</p>
                </button>

                {{-- Pro Scan --}}
                <button @click="tier = 'pro'" :class="tier === 'pro' ? 'border-purple-500 bg-purple-500/10' : 'border-white/10 bg-white/3 hover:bg-white/5'" class="border rounded-2xl p-4 text-left transition-all duration-200 cursor-pointer relative">
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2">
                        <span class="text-[10px] font-bold text-white bg-purple-600 px-2 py-0.5 rounded-full uppercase tracking-wider">Popular</span>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">Pro Scan</span>
                        <span class="text-xs font-bold text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-full">&euro;9,99</span>
                    </div>
                    <p class="text-xs text-gray-500">20 scanners + OWASP Top 10 analysis</p>
                </button>

                {{-- Deep Scan --}}
                <button @click="tier = 'deep'" :class="tier === 'deep' ? 'border-pink-500 bg-pink-500/10' : 'border-white/10 bg-white/3 hover:bg-white/5'" class="border rounded-2xl p-4 text-left transition-all duration-200 cursor-pointer">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">Deep Scan</span>
                        <span class="text-xs font-bold text-pink-400 bg-pink-500/10 px-2 py-0.5 rounded-full">&euro;29,99</span>
                    </div>
                    <p class="text-xs text-gray-500">27 scanners + OWASP + penetration checks</p>
                </button>
            </div>

            {{-- Submit buttons --}}
            @if($grantedTier)
            {{-- User has granted tier — all scans go through scan.store (no payment needed) --}}
            <form action="{{ route('scan.store') }}" method="POST"
                  @submit="loading = true" class="max-w-2xl mx-auto">
                @csrf
                <input type="hidden" name="url" x-bind:value="url">
                <button type="submit" :disabled="loading"
                        class="w-full sm:w-auto bg-gradient-to-r {{ $grantedTier === 'deep' ? 'from-pink-600 to-pink-500 shadow-pink-500/25' : 'from-purple-600 to-purple-500 shadow-purple-500/25' }} hover:opacity-90 disabled:opacity-60 text-white font-bold px-8 py-4 rounded-2xl transition-all duration-300 text-lg flex items-center justify-center gap-2.5 mx-auto min-w-[200px] shadow-lg">
                    <span x-show="!loading">{{ $grantedTier === 'deep' ? 'Deep' : 'Pro' }} Scan →</span>
                    <span x-show="loading" x-cloak>Scanning…</span>
                </button>
                <p class="text-xs text-emerald-400 mt-2 text-center">Unlimited {{ $grantedTier === 'deep' ? 'Deep' : 'Pro' }} scans enabled on your account</p>
            </form>
            @else
            {{-- Free scan --}}
            <form x-show="tier === 'free'" action="{{ route('scan.store') }}" method="POST"
                  @submit="loading = true" class="max-w-2xl mx-auto">
                @csrf
                <input type="hidden" name="url" x-bind:value="url">
                <button type="submit" :disabled="loading"
                        class="w-full sm:w-auto bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-60 text-white font-bold px-8 py-4 rounded-2xl transition-all duration-300 text-lg flex items-center justify-center gap-2.5 mx-auto min-w-[200px] shadow-lg shadow-indigo-500/25">
                    <span x-show="!loading">Quick Scan (Free) →</span>
                    <span x-show="loading" x-cloak>Scanning…</span>
                </button>
            </form>

            {{-- Pro/Deep scan (requires auth + payment) --}}
            <form x-show="tier !== 'free'" action="{{ route('checkout.create') }}" method="POST"
                  @submit="loading = true" class="max-w-2xl mx-auto">
                @csrf
                <input type="hidden" name="url" x-bind:value="url">
                <input type="hidden" name="tier" :value="tier">
                <button type="submit" :disabled="loading"
                        :class="tier === 'pro' ? 'from-purple-600 to-purple-500 shadow-purple-500/25' : 'from-pink-600 to-pink-500 shadow-pink-500/25'"
                        class="w-full sm:w-auto bg-gradient-to-r hover:opacity-90 disabled:opacity-60 text-white font-bold px-8 py-4 rounded-2xl transition-all duration-300 text-lg flex items-center justify-center gap-2.5 mx-auto min-w-[250px] shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span x-show="!loading">
                        <template x-if="tier === 'pro'">
                            <span>Pro Scan — &euro;9,99 →</span>
                        </template>
                        <template x-if="tier === 'deep'">
                            <span>Deep Scan — &euro;29,99 →</span>
                        </template>
                    </span>
                    <span x-show="loading" x-cloak>Processing…</span>
                </button>
                @guest
                <p class="text-xs text-gray-500 mt-2 text-center">Requires <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300">sign in</a> and payment via Stripe</p>
                @endguest
            </form>
            @endif
        </div>

        <p class="mt-5 text-sm text-gray-600">
            SSL · Headers · DNS · OWASP Top 10 · Malware · Ports · Privacy and more
        </p>

        {{-- Stats with animated counter --}}
        <div class="flex items-center justify-center gap-10 mt-12 flex-wrap">
            @if($scanCount > 0)
            <div class="text-center" x-data="{ count: 0 }" x-init="
                let target = {{ $scanCount }};
                let start = performance.now();
                let duration = 2000;
                let tick = (now) => {
                    let p = Math.min((now - start) / duration, 1);
                    let ease = 1 - Math.pow(1 - p, 3);
                    count = Math.round(ease * target);
                    if (p < 1) requestAnimationFrame(tick);
                };
                setTimeout(() => requestAnimationFrame(tick), 300);
            ">
                <p class="text-3xl sm:text-4xl font-black text-white"><span x-text="count.toLocaleString()">0</span>+</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider">Websites scanned</p>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            @endif
            <div class="text-center">
                <p class="text-3xl sm:text-4xl font-black text-white">27</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider">Security scanners</p>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center">
                <p class="text-3xl sm:text-4xl font-black text-white">1500+</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider">Individual checks</p>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center">
                <p class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Free</p>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider">Quick scan</p>
            </div>
        </div>

        {{-- Recent scans ticker --}}
        @if($recentPublicScans->isNotEmpty())
        <div class="mt-8 overflow-hidden relative max-w-2xl mx-auto" x-data="{}" x-init="
            const el = $el.querySelector('.ticker-track');
            if (el) { el.style.animationDuration = (el.children.length * 3) + 's'; }
        ">
            <div class="absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-gray-950 to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-gray-950 to-transparent z-10 pointer-events-none"></div>
            <div class="ticker-track flex gap-4 animate-[tickerScroll_30s_linear_infinite] whitespace-nowrap">
                @foreach($recentPublicScans->concat($recentPublicScans) as $rs)
                <span class="inline-flex items-center gap-2 text-xs text-gray-500 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full {{ ($rs->score ?? 0) >= 80 ? 'bg-green-500' : (($rs->score ?? 0) >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                    <span class="text-gray-400">{{ $rs->host }}</span>
                    <span class="font-bold {{ ($rs->score ?? 0) >= 80 ? 'text-green-400' : (($rs->score ?? 0) >= 60 ? 'text-yellow-400' : 'text-red-400') }}">{{ $rs->grade }}</span>
                    <span class="text-gray-600">{{ $rs->completed_at->diffForHumans() }}</span>
                </span>
                @endforeach
            </div>
            <style>
            @keyframes tickerScroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            </style>
        </div>
        @endif

        {{-- Floating check badges --}}
        <div class="absolute left-8 top-1/3 hidden xl:flex flex-col gap-3 opacity-60">
            @foreach(['SSL Valid', 'HSTS On', 'SPF Pass', 'No Malware'] as $badge)
            <div class="flex items-center gap-2 bg-gray-900/80 border border-green-500/20 rounded-full pl-2 pr-4 py-1.5 text-xs text-green-400 backdrop-blur-sm">
                <span class="w-4 h-4 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
                {{ $badge }}
            </div>
            @endforeach
        </div>
        <div class="absolute right-8 top-1/3 hidden xl:flex flex-col gap-3 opacity-60">
            @foreach(['DMARC Pass', 'No Open Ports', 'TLS 1.3', 'CSP Set'] as $badge)
            <div class="flex items-center gap-2 bg-gray-900/80 border border-indigo-500/20 rounded-full pl-2 pr-4 py-1.5 text-xs text-indigo-400 backdrop-blur-sm">
                <span class="w-4 h-4 rounded-full bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
                {{ $badge }}
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════ --}}
<section class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

    <div class="text-center mb-16">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Simple & fast</p>
        <h2 class="text-4xl font-black mb-4">How it works</h2>
        <p class="text-gray-400 max-w-xl mx-auto">No installation. No account. Just type a domain and get a full security report within seconds.</p>
    </div>

    <div class="relative grid grid-cols-1 sm:grid-cols-3 gap-8">

        {{-- Step 1 --}}
        <div class="relative text-center px-4">
            <div class="relative w-20 h-20 rounded-3xl bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 border border-indigo-500/25 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-500/10">
                <span class="text-3xl font-black text-indigo-400">1</span>
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-500 rounded-full"></div>
            </div>
            <h3 class="font-bold text-white text-lg mb-3">Enter a domain</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Paste any URL or domain name — <code class="text-indigo-400 bg-indigo-500/8 px-1.5 py-0.5 rounded text-xs">example.com</code> works just fine. No account or sign-up required.</p>
        </div>

        {{-- Step 2 --}}
        <div class="relative text-center px-4">
            <div class="relative w-20 h-20 rounded-3xl bg-gradient-to-br from-purple-500/20 to-purple-600/10 border border-purple-500/25 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-purple-500/10">
                <span class="text-3xl font-black text-purple-400">2</span>
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-500 rounded-full"></div>
            </div>
            <h3 class="font-bold text-white text-lg mb-3">19 scanners run</h3>
            <p class="text-sm text-gray-500 leading-relaxed">We run 19 security scanners — SSL, DNS, headers, malware, exposed files, open ports, privacy, accessibility and more. Done in under 60 seconds.</p>
        </div>

        {{-- Step 3 --}}
        <div class="relative text-center px-4">
            <div class="relative w-20 h-20 rounded-3xl bg-gradient-to-br from-pink-500/20 to-pink-600/10 border border-pink-500/25 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-pink-500/10">
                <span class="text-3xl font-black text-pink-400">3</span>
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-pink-500 rounded-full"></div>
            </div>
            <h3 class="font-bold text-white text-lg mb-3">Get your report</h3>
            <p class="text-sm text-gray-500 leading-relaxed">A security score from 0–100, letter grade A+–F, and a prioritised list of issues with exact steps to fix each one. Share via link or download as PDF.</p>
        </div>

    </div>
</section>


{{-- ═══════════════════════════════════════════
     REPORT PREVIEW
═══════════════════════════════════════════ --}}
<section class="relative py-24 overflow-hidden">

    {{-- Background glow --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[400px] bg-indigo-600/6 rounded-full blur-[100px]"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">What you get</p>
            <h2 class="text-4xl font-black mb-4">A real security report</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Every scan produces a complete, readable report you can share with your team or client.</p>
        </div>

        {{-- Mock report card --}}
        <div class="bg-gray-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-2xl shadow-black/40 backdrop-blur-sm">

            {{-- Report header --}}
            <div class="border-b border-white/8 px-8 py-6 flex flex-col sm:flex-row items-start sm:items-center gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-green-500/15 border border-green-500/20 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-green-400 leading-none">A</span>
                    </div>
                    <div>
                        <p class="text-xl font-black text-white">87<span class="text-gray-500 text-sm font-normal">/100</span></p>
                        <p class="text-sm text-gray-500">example.com</p>
                    </div>
                </div>
                <div class="sm:ml-auto flex flex-wrap gap-2">
                    @foreach(['SSL ✓', 'Headers ✓', 'DNS ✓', 'No Malware ✓'] as $tag)
                    <span class="text-xs bg-green-500/10 text-green-400 border border-green-500/15 px-3 py-1 rounded-full">{{ $tag }}</span>
                    @endforeach
                    <span class="text-xs bg-amber-500/10 text-amber-400 border border-amber-500/15 px-3 py-1 rounded-full">2 warnings</span>
                </div>
            </div>

            {{-- Category rows --}}
            <div class="divide-y divide-white/5">
                @php
                $previewCategories = [
                    ['SSL & HTTPS',       95, true,  'indigo'],
                    ['Security Headers',  72, true,  'purple'],
                    ['DNS & Email',       88, true,  'blue'],
                    ['Performance',       80, true,  'yellow'],
                    ['Malware & Blacklists', 100, true, 'green'],
                    ['Open Ports',        90, true,  'red'],
                    ['Privacy & GDPR',    55, false, 'violet'],
                    ['Trust & WHOIS',     85, true,  'teal'],
                ];
                @endphp
                @foreach($previewCategories as [$cat, $score, $pass, $color])
                <div class="flex items-center gap-4 px-8 py-4 hover:bg-white/2 transition-colors">
                    <div class="w-2 h-2 rounded-full {{ $pass ? 'bg-green-500' : 'bg-amber-500' }} flex-shrink-0"></div>
                    <p class="text-sm text-gray-300 flex-1">{{ $cat }}</p>
                    <div class="w-28 bg-white/5 rounded-full h-1.5 hidden sm:block">
                        <div class="h-1.5 rounded-full {{ $score >= 80 ? 'bg-green-500' : ($score >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                             style="width: {{ $score }}%"></div>
                    </div>
                    <span class="text-sm font-bold w-8 text-right {{ $score >= 80 ? 'text-green-400' : ($score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $score }}
                    </span>
                </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="border-t border-white/8 px-8 py-5 flex flex-col sm:flex-row items-center gap-4 bg-white/2">
                <p class="text-xs text-gray-600 flex-1">This is a sample report. Run a scan to see real results for your site.</p>
                <form action="{{ route('scan.store') }}" method="POST"
                      x-data="{ loading: false }"
                      @submit="loading = true; $dispatch('scan-start', { url: $el.querySelector('[name=url]').value })"
                      class="flex gap-2">
                    @csrf
                    <input type="text" name="url" placeholder="your-domain.com"
                           class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition w-48">
                    <button type="submit" :disabled="loading"
                            class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition whitespace-nowrap">
                        <span x-show="!loading">Scan now</span>
                        <span x-show="loading" x-cloak>…</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>


{{-- ═══════════════════════════════════════════
     BEFORE / AFTER
═══════════════════════════════════════════ --}}
<section class="relative py-24 border-t border-white/5">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Real results</p>
            <h2 class="text-4xl font-black mb-4">From D to A+ in one afternoon</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Most security issues are quick to fix once you know what they are. Here is a real example of what a single scan can uncover.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {{-- Before --}}
            <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl bg-red-500/15 border border-red-500/25 flex flex-col items-center justify-center">
                        <span class="text-lg font-black text-red-400 leading-none">D</span>
                        <span class="text-[9px] text-red-500">42</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Before scan</p>
                        <p class="text-xs text-gray-500">Score: 42/100</p>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach([
                        'No HTTPS redirect configured',
                        'HSTS header missing',
                        'No SPF or DMARC records',
                        'Server version exposed',
                        '.env file publicly accessible',
                        'No Content-Security-Policy',
                    ] as $issue)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="text-gray-400">{{ $issue }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- After --}}
            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/15 border border-emerald-500/25 flex flex-col items-center justify-center">
                        <span class="text-lg font-black text-emerald-400 leading-none">A+</span>
                        <span class="text-[9px] text-emerald-500">96</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">After fixes</p>
                        <p class="text-xs text-gray-500">Score: 96/100</p>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach([
                        'HTTPS with auto-redirect enabled',
                        'HSTS with 1-year max-age',
                        'SPF + DMARC reject policy',
                        'Server header removed',
                        '.env blocked via server config',
                        'Strict CSP implemented',
                    ] as $fix)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-400">{{ $fix }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">All fixes were implemented in under 2 hours using only the recommendations from our report.</p>
    </div>
</section>


{{-- ═══════════════════════════════════════════
     WHAT WE CHECK
═══════════════════════════════════════════ --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

    <div class="text-center mb-16">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Comprehensive</p>
        <h2 class="text-4xl font-black mb-4">What we check</h2>
        <p class="text-gray-400 max-w-xl mx-auto">19 security, performance and privacy categories — 50+ individual checks per scan.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        @php
        $checks = [
            ['SSL & HTTPS',         'indigo',  'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
             'Certificate validity, HSTS, TLS version, weak ciphers and forced redirect.',
             ['Valid certificate', 'HSTS enabled', 'TLS 1.3', 'No weak ciphers']],

            ['Security Headers',   'purple',  'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
             'HTTP response headers that protect against common browser-based attacks.',
             ['Content-Security-Policy', 'X-Frame-Options', 'Referrer-Policy', 'COOP / COEP']],

            ['DNS & Email',        'blue',    'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2',
             'Email spoofing protection and DNS hardening for your domain.',
             ['SPF record', 'DMARC policy', 'DKIM selector', 'DNSSEC', 'CAA record']],

            ['Performance',        'yellow',  'M13 10V3L4 14h7v7l9-11h-7z',
             'Speed, compression and discoverability checks.',
             ['Response time', 'Gzip / Brotli', 'robots.txt', 'Sitemap.xml']],

            ['Content & CMS',      'green',   'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
             'Mixed content, version leaks and open redirect vulnerabilities.',
             ['Mixed content', 'WP version leak', 'Admin exposure', 'Open redirect', 'SRI checks']],

            ['Technology',         'cyan',    'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',
             'Identify the full tech stack running on the site.',
             ['CMS & e-commerce', 'JS frameworks (Astro, Qwik, Remix…)', 'CSS frameworks', 'CDN / WAF', 'HTTP/2']],

            ['Malware & Blacklists','red',    'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
             'Cross-referenced against multiple threat intelligence feeds.',
             ['Google Safe Browsing', 'URLhaus', 'Spamhaus ZEN', 'OpenDNS']],

            ['Open Ports',         'orange',  'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
             'Detects exposed services that should never face the public internet.',
             ['MySQL / Redis / MongoDB', 'Kubernetes API (6443)', 'Prometheus (9090)', 'Docker API (2375)']],

            ['Exposed Files',      'emerald', 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
             'Publicly accessible files that leak secrets or server internals.',
             ['.env file', '.git directory', 'phpinfo.php', 'Backup files']],

            ['Privacy & GDPR',     'violet',  'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
             'Cookie consent, tracking presence and privacy documentation.',
             ['Cookie consent', 'Privacy policy', 'Tracker detection', 'GDPR signals']],

            ['Trust & WHOIS',      'teal',    'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
             'Domain age, registrar, expiry date and nameserver information.',
             ['Domain age', 'Expiry date', 'Registrar', 'Name servers']],

            ['Accessibility',      'pink',    'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
             'Basic accessibility checks for inclusivity and usability compliance.',
             ['Alt text on images', 'Lang attribute', 'Viewport meta', 'Heading structure']],

            ['TLS / Cipher Suite',  'sky',    'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
             'Deep dive into TLS protocol versions and cipher suite configuration.',
             ['TLS 1.2 / 1.3 only', 'ECDHE ciphers', 'Forward secrecy', 'No weak ciphers']],

            ['Robots & Crawling',  'lime',    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
             'Validates robots.txt rules and checks for sitemap availability.',
             ['robots.txt present', 'Sitemap.xml', 'Crawl rules', 'Disallow check']],

            ['API Security',       'amber',   'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
             'Detects exposed API endpoints and common security misconfigurations.',
             ['Swagger / OpenAPI exposed', 'GraphQL introspection', 'OpenID Connect metadata', 'JWKS endpoint']],

            ['Carbon Footprint',   'emerald', 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
             'Estimates the environmental impact and green hosting status of the site.',
             ['CO₂ per visit', 'Green hosting', 'Page weight', 'Efficiency rating']],

            ['Broken Links',       'rose',    'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
             'Crawls the homepage and checks for broken links and redirect chains.',
             ['404 detection', 'External links', 'Redirect chains', 'Anchor tags']],

            ['Branding',           'pink',    'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
             'Checks for favicon, Open Graph tags and social media presence.',
             ['Favicon', 'Open Graph', 'Twitter Card', 'Apple touch icon']],

            ['Subdomain Takeover', 'orange',  'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
             'Detects dangling DNS records that could be hijacked by attackers.',
             ['CNAME dangling', 'Vercel / Render / Railway', 'GitHub Pages / Netlify', 'Firebase / Fly.io']],
        ];

        $colorMap = [
            'indigo'  => ['icon' => 'text-indigo-400', 'bg' => 'bg-indigo-500/12', 'border' => 'border-indigo-500/20', 'dot' => 'bg-indigo-500'],
            'purple'  => ['icon' => 'text-purple-400', 'bg' => 'bg-purple-500/12', 'border' => 'border-purple-500/20', 'dot' => 'bg-purple-500'],
            'blue'    => ['icon' => 'text-blue-400',   'bg' => 'bg-blue-500/12',   'border' => 'border-blue-500/20',   'dot' => 'bg-blue-500'],
            'yellow'  => ['icon' => 'text-yellow-400', 'bg' => 'bg-yellow-500/12', 'border' => 'border-yellow-500/20', 'dot' => 'bg-yellow-500'],
            'green'   => ['icon' => 'text-green-400',  'bg' => 'bg-green-500/12',  'border' => 'border-green-500/20',  'dot' => 'bg-green-500'],
            'cyan'    => ['icon' => 'text-cyan-400',   'bg' => 'bg-cyan-500/12',   'border' => 'border-cyan-500/20',   'dot' => 'bg-cyan-500'],
            'red'     => ['icon' => 'text-red-400',    'bg' => 'bg-red-500/12',    'border' => 'border-red-500/20',    'dot' => 'bg-red-500'],
            'orange'  => ['icon' => 'text-orange-400', 'bg' => 'bg-orange-500/12', 'border' => 'border-orange-500/20', 'dot' => 'bg-orange-500'],
            'emerald' => ['icon' => 'text-emerald-400','bg' => 'bg-emerald-500/12','border' => 'border-emerald-500/20','dot' => 'bg-emerald-500'],
            'violet'  => ['icon' => 'text-violet-400', 'bg' => 'bg-violet-500/12', 'border' => 'border-violet-500/20', 'dot' => 'bg-violet-500'],
            'teal'    => ['icon' => 'text-teal-400',   'bg' => 'bg-teal-500/12',   'border' => 'border-teal-500/20',   'dot' => 'bg-teal-500'],
            'pink'    => ['icon' => 'text-pink-400',   'bg' => 'bg-pink-500/12',   'border' => 'border-pink-500/20',   'dot' => 'bg-pink-500'],
            'sky'     => ['icon' => 'text-sky-400',    'bg' => 'bg-sky-500/12',    'border' => 'border-sky-500/20',    'dot' => 'bg-sky-500'],
            'lime'    => ['icon' => 'text-lime-400',   'bg' => 'bg-lime-500/12',   'border' => 'border-lime-500/20',   'dot' => 'bg-lime-500'],
            'amber'   => ['icon' => 'text-amber-400',  'bg' => 'bg-amber-500/12',  'border' => 'border-amber-500/20',  'dot' => 'bg-amber-500'],
            'rose'    => ['icon' => 'text-rose-400',   'bg' => 'bg-rose-500/12',   'border' => 'border-rose-500/20',   'dot' => 'bg-rose-500'],
        ];
        @endphp

        @foreach($checks as [$title, $color, $path, $desc, $items])
        @php $c = $colorMap[$color]; @endphp
        <div class="group bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 hover:border-white/15 transition-all duration-300 cursor-default">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} border {{ $c['border'] }} flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @foreach(explode(' M', $path) as $i => $segment)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $i === 0 ? $segment : 'M'.$segment }}"/>
                        @endforeach
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">{{ $title }}</h3>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($items as $item)
                <span class="text-xs {{ $c['bg'] }} {{ $c['icon'] }} border {{ $c['border'] }} px-2 py-0.5 rounded-full">{{ $item }}</span>
                @endforeach
            </div>
        </div>
        @endforeach

    </div>
</section>


{{-- ═══════════════════════════════════════════
     FEATURE HIGHLIGHTS
═══════════════════════════════════════════ --}}
<section class="relative py-24 border-t border-white/5 overflow-hidden">

    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute bottom-0 right-0 w-[500px] h-[300px] bg-purple-600/6 rounded-full blur-[80px]"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Built for everyone</p>
            <h2 class="text-4xl font-black mb-4">More than just a scan</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Tools built for developers, agencies and site owners who care about security.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- PDF Report --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/12 border border-indigo-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">PDF Export</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Download a professional PDF report for your records or to share with clients and management.</p>
            </div>

            {{-- Compare --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-purple-500/12 border border-purple-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">Side-by-side compare</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Compare two websites head to head across all categories — perfect for competitive analysis or pre/post audits.</p>
                <a href="{{ route('scan.compare') }}" class="inline-flex items-center gap-1 text-xs text-purple-400 hover:text-purple-300 mt-3 transition-colors">
                    Try compare →
                </a>
            </div>

            {{-- Badge --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-green-500/12 border border-green-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">Embed badge</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Show your security grade on your own site with a live SVG badge that auto-updates when you rescan.</p>
            </div>

            {{-- API --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/12 border border-cyan-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">Public JSON API</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Integrate scans into your own tools, dashboards or CI/CD pipelines. Free, no authentication needed.</p>
                <a href="{{ route('api.docs') }}" class="inline-flex items-center gap-1 text-xs text-cyan-400 hover:text-cyan-300 mt-3 transition-colors">
                    View API docs →
                </a>
            </div>

            {{-- Monitoring --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-amber-500/12 border border-amber-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">Monitoring & alerts</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Register your sites and get weekly email alerts when your score drops or your SSL certificate is about to expire.</p>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-1 text-xs text-amber-400 hover:text-amber-300 mt-3 transition-colors">
                    Create free account →
                </a>
            </div>

            {{-- GitHub Action --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:border-white/15 transition-all duration-300">
                <div class="w-10 h-10 rounded-xl bg-pink-500/12 border border-pink-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white mb-2">GitHub Action</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Add security scanning to your CI/CD pipeline. Fail the build automatically when the score drops below your threshold.</p>
            </div>

        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════
     PRICING
═══════════════════════════════════════════ --}}
<section class="relative py-24 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-purple-600/6 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Choose your scan</p>
            <h2 class="text-4xl font-black mb-4">Simple, transparent pricing</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Start free. Upgrade when you need deeper analysis or OWASP compliance reporting.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $checkIcon = '<svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                $crossIcon = '<svg class="w-4 h-4 text-gray-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            @endphp

            {{-- Quick Scan --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-8 hover:border-white/15 transition-all">
                <h3 class="text-xl font-bold text-white mb-1">Quick Scan</h3>
                <p class="text-sm text-gray-500 mb-4">Basic security check</p>
                <p class="text-4xl font-black text-white mb-6">Free</p>
                <ul class="space-y-3 mb-8 text-sm">
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} 5 security scanners</li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} SSL, Headers, DNS, Performance, Content</li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} No account required</li>
                    <li class="flex items-center gap-2 text-gray-600">{!! $crossIcon !!} OWASP Top 10 analysis</li>
                    <li class="flex items-center gap-2 text-gray-600">{!! $crossIcon !!} PDF report</li>
                </ul>
                <a href="#" onclick="document.getElementById('scan-url').focus(); return false;" class="block w-full text-center bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold py-3 rounded-xl transition">Start free scan</a>
            </div>

            {{-- Pro Scan --}}
            <div class="bg-purple-500/5 border-2 border-purple-500/30 rounded-2xl p-8 relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="text-xs font-bold text-white bg-purple-600 px-4 py-1 rounded-full uppercase tracking-wider">Most popular</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">Pro Scan</h3>
                <p class="text-sm text-gray-500 mb-4">Complete security analysis</p>
                <p class="text-4xl font-black text-white mb-1">&euro;9<span class="text-xl text-gray-400">,99</span></p>
                <p class="text-xs text-gray-600 mb-6">per scan</p>
                <ul class="space-y-3 mb-8 text-sm">
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} <strong>20 security scanners</strong></li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} <strong>OWASP Top 10 analysis</strong></li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} Malware, Ports, Exposed Files</li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} Privacy, Accessibility, API Security</li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} PDF report + dashboard</li>
                </ul>
                <a href="#" onclick="document.getElementById('scan-url').focus(); return false;" class="block w-full text-center bg-purple-600 hover:bg-purple-500 text-white font-semibold py-3 rounded-xl transition shadow-lg shadow-purple-500/25">Get Pro Scan</a>
            </div>

            {{-- Deep Scan --}}
            <div class="bg-white/3 border border-white/8 rounded-2xl p-8 hover:border-white/15 transition-all">
                <h3 class="text-xl font-bold text-white mb-1">Deep Scan</h3>
                <p class="text-sm text-gray-500 mb-4">Advanced penetration checks</p>
                <p class="text-4xl font-black text-white mb-1">&euro;29<span class="text-xl text-gray-400">,99</span></p>
                <p class="text-xs text-gray-600 mb-6">per scan</p>
                <ul class="space-y-3 mb-8 text-sm">
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} <strong>27 security scanners</strong></li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} Everything in Pro +</li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} <strong>Directory brute-force</strong></li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} <strong>XSS reflection testing</strong></li>
                    <li class="flex items-center gap-2 text-gray-300">{!! $checkIcon !!} Error disclosure, HTTP methods, Session, Email, Cookies</li>
                </ul>
                <a href="#" onclick="document.getElementById('scan-url').focus(); return false;" class="block w-full text-center bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold py-3 rounded-xl transition">Get Deep Scan</a>
            </div>
        </div>

        {{-- BudgetPixels CTA --}}
        <div class="mt-12 text-center bg-gradient-to-r from-indigo-600/10 to-purple-600/10 border border-indigo-500/20 rounded-2xl p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-2">Need more?</p>
            <h3 class="text-xl font-bold text-white mb-2">Professional pentest or security audit?</h3>
            <p class="text-sm text-gray-400 mb-5 max-w-lg mx-auto">Our experts at BudgetPixels perform manual penetration tests, security audits with source code review, and AVG/GDPR compliance checks.</p>
            <a href="https://budgetpixels.nl" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition shadow-lg shadow-indigo-500/25">
                View professional services →
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════ --}}
<section class="relative py-24 border-t border-white/5">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400 mb-3">Trusted by developers &amp; agencies</p>
            <h2 class="text-4xl font-black mb-4">What people say</h2>
            <p class="text-gray-400 max-w-xl mx-auto">Used by security-conscious teams and developers worldwide.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white/3 border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-gray-300 leading-relaxed mb-5">"We run WebCheckApp on every client site before handoff. The report is clear enough to share with non-technical stakeholders and detailed enough for our dev team."</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-sm font-bold text-indigo-400">M</div>
                    <div>
                        <p class="text-sm font-semibold text-white">Mark de Vries</p>
                        <p class="text-xs text-gray-500">CTO, Digital Agency</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/3 border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-gray-300 leading-relaxed mb-5">"I added the security badge to our README and pointed our sysadmin at the report. Two days later our HSTS and CSP were fixed. Couldn't have been easier."</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-sm font-bold text-purple-400">S</div>
                    <div>
                        <p class="text-sm font-semibold text-white">Sarah Klement</p>
                        <p class="text-xs text-gray-500">Full-stack Developer</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/3 border border-white/8 rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-gray-300 leading-relaxed mb-5">"Love the compare feature. We scanned our site against a competitor and found three header issues they had already fixed. Great motivation for the team."</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-green-500/20 border border-green-500/30 flex items-center justify-center text-sm font-bold text-green-400">J</div>
                    <div>
                        <p class="text-sm font-semibold text-white">James Okonkwo</p>
                        <p class="text-xs text-gray-500">Security Engineer</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════
     CTA
═══════════════════════════════════════════ --}}
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
    <div class="relative bg-gradient-to-br from-indigo-600/20 via-indigo-500/10 to-purple-600/15 border border-indigo-500/25 rounded-3xl p-12 text-center overflow-hidden">

        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[500px] h-[200px] bg-indigo-500/10 rounded-full blur-[60px]"></div>
        </div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 bg-indigo-500/15 border border-indigo-500/25 rounded-full px-4 py-1.5 text-sm text-indigo-300 mb-6">
                <span class="w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></span>
                Free quick scan — no account needed
            </div>

            <h2 class="text-4xl font-black mb-4">Scan your website now</h2>
            <p class="text-gray-400 mb-10 max-w-lg mx-auto">Find out what attackers see when they look at your site. Free quick scan in 15 seconds, or upgrade for OWASP Top 10 and deep analysis.</p>

            <form action="{{ route('scan.store') }}" method="POST"
                  x-data="{ loading: false }"
                  @submit="loading = true; $dispatch('scan-start', { url: $el.querySelector('[name=url]').value })">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto">
                    <input type="text" name="url" placeholder="your-domain.com"
                           class="flex-1 bg-white/6 border border-white/15 rounded-2xl px-5 py-4 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition text-sm">
                    <button type="submit" :disabled="loading"
                            class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 text-white font-bold px-8 py-4 rounded-2xl transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 whitespace-nowrap">
                        <span x-show="!loading">Scan for free →</span>
                        <span x-show="loading" x-cloak>Scanning…</span>
                    </button>
                </div>
            </form>

            <p class="mt-5 text-xs text-gray-600">
                Or <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300 transition-colors">create a free account</a> to monitor your sites and get weekly alerts.
            </p>
        </div>
    </div>
</section>

@endsection
