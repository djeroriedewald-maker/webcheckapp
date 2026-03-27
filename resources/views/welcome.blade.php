@extends('layouts.app')

@section('title', 'WebCheckApp — Free Website Security Scanner')

@section('content')

<section class="relative overflow-hidden min-h-[600px] flex items-center">
    {{-- Hero image full width background --}}
    <div class="absolute inset-0 pointer-events-none">
        <img src="/hero.jpg" alt="" class="w-full h-full object-cover object-center opacity-30">
        {{-- Dark overlay so text stays readable --}}
        <div class="absolute inset-0 bg-gray-950/60"></div>
        {{-- Gradient fade at bottom to blend into page --}}
        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-gray-950 to-transparent"></div>
        {{-- Subtle indigo tint top-left --}}
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/60 via-transparent to-transparent"></div>
    </div>

    <div class="relative w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 text-center">
        <div class="">

            <div class="inline-flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full px-4 py-1.5 text-sm text-indigo-300 mb-8">
                <span class="w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></span>
                Free instant security scan
            </div>

            <h1 class="text-5xl sm:text-6xl font-bold tracking-tight mb-6 leading-tight">
                Is your website
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400"> secure?</span>
            </h1>

            <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto">
                Scan any website in seconds. Get a detailed security report with your score, detected issues and clear recommendations to fix them.
            </p>

            <form action="{{ route('scan.store') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3 max-w-2xl mx-auto">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="url"
                            placeholder="example.com or https://example.com"
                            value="{{ old('url') }}"
                            class="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-4 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-lg"
                            autofocus
                        >
                    </div>
                    <button
                        type="submit"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-8 py-4 rounded-xl transition-all duration-200 text-lg flex items-center justify-center gap-2 min-w-[160px] disabled:opacity-70"
                        :disabled="loading"
                    >
                        <span x-show="!loading">Scan now</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Scanning...
                        </span>
                    </button>
                </div>

                @error('url')
                    <p class="mt-3 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </form>

            <p class="mt-5 text-sm text-gray-600">
                We check SSL, security headers, DNS records, performance and more.
            </p>

            @if($scanCount > 0)
            <p class="mt-3 text-sm text-gray-600">
                <span class="text-indigo-400 font-semibold">{{ number_format($scanCount) }}</span> scans performed
            </p>
            @endif
        </div>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <h2 class="text-center text-3xl font-bold mb-4">What we check</h2>
    <p class="text-center text-gray-400 mb-14">A comprehensive scan across 6 security, performance and technology categories.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">SSL & HTTPS</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Certificate validity, HSTS configuration and forced HTTPS redirects.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">Security Headers</h3>
            <p class="text-sm text-gray-400 leading-relaxed">CSP, X-Frame-Options, X-Content-Type-Options and more.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">DNS & Email Security</h3>
            <p class="text-sm text-gray-400 leading-relaxed">SPF, DMARC, DNSSEC and CAA records to protect your domain.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-yellow-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">Performance & SEO</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Response time, compression, robots.txt and sitemap checks.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-green-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">Content & CMS</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Mixed content, admin exposure and WordPress version leaks.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-pink-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">Score & Grade</h3>
            <p class="text-sm text-gray-400 leading-relaxed">Overall security grade from A+ to F with priority fixes listed.</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-cyan-500/15 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
            </div>
            <h3 class="font-semibold text-white mb-2">Technology Detection</h3>
            <p class="text-sm text-gray-400 leading-relaxed">CMS, web server, CDN, JavaScript frameworks, analytics tools and HTTP/2 support.</p>
        </div>

    </div>
</section>

@endsection
