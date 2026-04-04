@extends('layouts.app')

@section('title', $data['title'] . ' — WebCheckApp')
@section('meta_description', $data['description'])

@section('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($data['faq'] as $i => $faq)
        {
            "@@type": "Question",
            "name": "{{ $faq['q'] }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $faq['a'] }}"
            }
        }{{ $i < count($data['faq']) - 1 ? ',' : '' }}
        @endforeach
    ]
}
</script>
@endsection

@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px] bg-indigo-600/10 rounded-full blur-[100px]"></div>
    </div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
        <div class="inline-flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/25 rounded-full px-4 py-1.5 text-sm text-indigo-300 mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Free online tool
        </div>
        <h1 class="text-4xl sm:text-5xl font-black text-white mb-6">{{ $data['h1'] }}</h1>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">{{ $data['description'] }}</p>

        {{-- Scan form --}}
        <form action="{{ route('scan.store') }}" method="POST"
              x-data="{ loading: false, url: '' }"
              x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) loading = false; })"
              @submit="$el.querySelector('[name=url]').value = url; loading = true">
            @csrf
            <div class="flex flex-col sm:flex-row gap-3 max-w-xl mx-auto">
                <input type="text" x-model="url" placeholder="example.com"
                       class="flex-1 bg-white/6 border border-white/12 rounded-2xl px-5 py-4 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition text-lg" autofocus>
                <input type="hidden" name="url">
                <button type="submit" :disabled="loading"
                        class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 text-white font-bold px-8 py-4 rounded-2xl transition text-lg whitespace-nowrap">
                    <span x-show="!loading">Check now →</span>
                    <span x-show="loading" x-cloak>Scanning…</span>
                </button>
            </div>
        </form>
    </div>
</section>

{{-- What we check --}}
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div>
            <h2 class="text-2xl font-bold text-white mb-4">What we check</h2>
            <div class="space-y-3">
                @foreach($data['checks'] as $check)
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-gray-300">{{ $check }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white mb-4">How it works</h2>
            <div class="text-sm text-gray-400 leading-relaxed space-y-4">
                <p>{{ $data['intro'] }}</p>
                <p>Results are available in seconds. No installation or server access required — we scan your website from the outside, just like an attacker would.</p>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="text-xs text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition">Full security scan →</a>
                <a href="{{ route('scan.compare') }}" class="text-xs text-gray-400 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">Compare two sites</a>
                <a href="{{ route('api.docs') }}" class="text-xs text-gray-400 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">API docs</a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 border-t border-white/5">
    <h2 class="text-2xl font-bold text-white mb-8 text-center">Frequently Asked Questions</h2>
    <div class="space-y-4 max-w-2xl mx-auto">
        @foreach($data['faq'] as $faq)
        <div class="bg-white/3 border border-white/8 rounded-xl overflow-hidden" x-data="{ open: false }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 text-left">
                <span class="text-sm font-semibold text-white pr-4">{{ $faq['q'] }}</span>
                <svg class="w-4 h-4 text-gray-500 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="px-5 pb-4">
                <p class="text-sm text-gray-400 leading-relaxed">{{ $faq['a'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- CTA --}}
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-gradient-to-br from-indigo-600/15 to-purple-600/10 border border-indigo-500/20 rounded-2xl p-8 text-center">
        <h2 class="text-2xl font-bold text-white mb-3">Need a deeper analysis?</h2>
        <p class="text-sm text-gray-400 mb-6 max-w-lg mx-auto">Our Pro and Deep scans include OWASP Top 10 analysis, malware detection, exposed files, and up to 27 security scanners with a professional PDF report.</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">View all scan options →</a>
            <a href="https://budgetpixels.nl" target="_blank" rel="noopener" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold px-6 py-3 rounded-xl transition">Professional services →</a>
        </div>
    </div>
</section>

@endsection
