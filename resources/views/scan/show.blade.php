@use('App\Support\CheckKnowledge')
@extends('layouts.app')

@section('title', $scan->isCompleted() ? 'Security Report: ' . $scan->host . ' — Score ' . $scan->score . '/100 (Grade ' . $scan->grade . ')' : 'Scanning ' . $scan->host . ' — WebCheckApp')
@section('meta_description', $scan->isCompleted() ? 'Free security scan for ' . $scan->host . '. Score: ' . $scan->score . '/100, Grade ' . $scan->grade . '. Checks SSL, headers, DNS, malware, open ports, exposed files and more.' : 'Scanning ' . $scan->host . ' for security vulnerabilities...')
@section('canonical', route('scan.show', $scan))
@section('robots', $scan->isCompleted() ? 'index, follow' : 'noindex, follow')

@if($scan->isCompleted())
@section('og_title', 'Security Report: ' . $scan->host . ' — Score ' . $scan->score . '/100 (Grade ' . $scan->grade . ')')
@section('og_description', $scan->host . ' scored ' . $scan->score . '/100 (Grade ' . $scan->grade . ') on WebCheckApp. Free security scan covering SSL, headers, DNS, malware, ports, and more.')
@section('og_url', route('scan.show', $scan))
@section('structured_data')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Security Report: {{ $scan->host }}",
  "description": "Security scan for {{ $scan->host }} — Score {{ $scan->score }}/100, Grade {{ $scan->grade }}",
  "url": "{{ route('scan.show', $scan) }}",
  "datePublished": "{{ $scan->completed_at->toIso8601String() }}",
  "publisher": {
    "@type": "Organization",
    "name": "WebCheckApp",
    "url": "{{ url('/') }}"
  }
}
</script>
@endsection
@endif

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
     x-data="scanPoller({{ $scan->id }}, '{{ route('scan.status', $scan) }}', {{ $scan->isCompleted() ? 'true' : 'false' }})"
     x-init="init()">

    {{-- Loading state — server-side rendered so it's immediately visible,
         no Alpine dependency. Alpine hides it once the scan completes/fails. --}}
    @if(!$scan->isCompleted() && !$scan->isFailed())
    <div id="scan-loading" class="fixed inset-0 z-[9999] bg-gray-950 flex flex-col items-center justify-center px-6"
         x-show="!completed && !failed">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px] bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative w-full max-w-md text-center">
            {{-- Logo --}}
            <div class="flex items-center justify-center gap-2 mb-12 text-lg font-bold">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>WebCheck<span class="text-indigo-400">App</span></span>
            </div>

            {{-- Host --}}
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Analyzing security for</p>
            <h2 class="text-2xl font-bold text-white truncate mb-10">{{ $scan->host }}</h2>

            {{-- Progress percentage --}}
            <div class="flex items-end justify-between mb-2 px-1">
                <span id="scan-current-label" class="text-sm text-indigo-300 font-medium truncate pr-4">Starting scan&hellip;</span>
                <span id="scan-percent" class="text-sm font-bold text-white tabular-nums flex-shrink-0">0%</span>
            </div>

            {{-- Progress bar --}}
            <div class="w-full h-3 bg-white/5 rounded-full overflow-hidden mb-4">
                <div id="scan-progress-bar"
                     class="h-full rounded-full transition-all duration-700 ease-out"
                     style="width: 2%; background: linear-gradient(90deg, #6366f1, #a855f7, #6366f1); background-size: 200% 100%; animation: scanShimmer 1.8s linear infinite;"></div>
            </div>

            {{-- Completed count --}}
            <p class="text-xs text-gray-600"><span id="scan-done-count">0</span> of {{ app(\App\Services\ScanService::class)->scannerCountForTier($scan->tier ?? 'free') }} checks completed</p>
        </div>
    </div>
    <style>
    @keyframes scanShimmer {
        0%   { background-position: 200% center; }
        100% { background-position: -200% center; }
    }
    </style>
    <script nonce="{{ Vite::cspNonce() }}">
    var _scanners = ['SSL & HTTPS','Security Headers','DNS & Email Security','Performance & SEO','Content & CMS','Technology Stack','Malware & Reputation','Open Ports','Exposed Files','Privacy & GDPR','Trust & WHOIS','Accessibility','TLS / Cipher Suite','Robots & Crawling','API Security','Carbon Footprint','Broken Links','Branding','Subdomain Takeover'];
    var _lastRendered  = -1;
    var _realPct       = 0;
    var _pseudoPct     = 2;
    var _pseudoTimer   = null;

    // Slowly crawl from 2% → 35% while waiting for the first real update.
    // Gives visual feedback during the slow SSL scanner phase.
    function startPseudo() {
        _pseudoTimer = setInterval(function () {
            if (_realPct > 0) { clearInterval(_pseudoTimer); return; }
            if (_pseudoPct < 35) {
                _pseudoPct += 0.4;
                var bar     = document.getElementById('scan-progress-bar');
                var percent = document.getElementById('scan-percent');
                var label   = document.getElementById('scan-current-label');
                if (bar)     bar.style.width       = _pseudoPct.toFixed(1) + '%';
                if (percent) percent.textContent    = Math.round(_pseudoPct) + '%';
                if (label && label.textContent === 'Starting scan\u2026')
                    label.textContent = 'Scanning: SSL & HTTPS\u2026';
            } else {
                clearInterval(_pseudoTimer);
            }
        }, 500);
    }

    function updateProgress(completedCount) {
        if (completedCount > 0 && completedCount <= _lastRendered) return;

        var bar       = document.getElementById('scan-progress-bar');
        var percent   = document.getElementById('scan-percent');
        var label     = document.getElementById('scan-current-label');
        var doneCount = document.getElementById('scan-done-count');
        if (!bar) return;

        var total = _scanners.length;

        if (completedCount > 0) {
            // Real data arrived — stop pseudo and switch to real percentage
            if (_pseudoTimer) { clearInterval(_pseudoTimer); _pseudoTimer = null; }
            var pct = Math.round((completedCount / total) * 100);
            _realPct = pct;
            bar.style.width = pct + '%';
            percent.textContent = pct + '%';
            doneCount.textContent = completedCount;
            label.textContent = completedCount < total
                ? 'Scanning: ' + _scanners[completedCount] + '\u2026'
                : 'Finalizing\u2026';
            _lastRendered = completedCount;
        }
    }

    startPseudo();
    </script>
    @endif

    {{-- Failed state --}}
    <div x-show="failed" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 mb-6">
            <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2">Scan failed</h2>
        <p class="text-gray-400 mb-2">We could not scan <strong>{{ $scan->host }}</strong>. The website may be unreachable.</p>
        <p x-show="errorMessage" x-text="'Error: ' + errorMessage" class="text-red-400 text-sm mb-4 font-mono"></p>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Try another URL
        </a>
    </div>

    {{-- Completed report --}}
    @if($scan->isCompleted())
    <div x-show="completed" x-data="{ tab: 'technologie' }">

        {{-- Header with score --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-10">
            <div>
                <p class="text-sm text-gray-500 mb-1">Security report for</p>
                <h1 class="text-2xl font-bold text-white">{{ $scan->host }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Scanned {{ $scan->completed_at->diffForHumans() }}
                </p>

                {{-- Cached result notice --}}
                @if($scan->completed_at < now()->subMinutes(5))
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-amber-400/80 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Cached result
                    </span>
                    <form action="{{ route('scan.store') }}" method="POST" class="inline"
                          x-data @submit="$dispatch('scan-start', { url: '{{ addslashes($scan->url) }}' })">
                        @csrf
                        <input type="hidden" name="url" value="{{ $scan->url }}">
                        <button type="submit" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                            Run fresh scan &rarr;
                        </button>
                    </form>
                </div>
                @endif

                @isset($newerScan)
                <div class="mt-2 text-xs text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 rounded-lg px-3 py-2">
                    A newer scan is available.
                    <a href="{{ route('scan.show', $newerScan) }}" class="underline hover:text-white">View latest &rarr;</a>
                </div>
                @endisset

                {{-- Share buttons --}}
                <div class="flex items-center gap-2 mt-3" x-data="{ copied: false }">
                    <button
                        @click="navigator.clipboard.writeText('{{ route('scan.show', $scan) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-lg transition"
                    >
                        <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="copied" class="w-3.5 h-3.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                    </button>

                    <a href="https://x.com/intent/tweet?text={{ urlencode('I scanned ' . $scan->host . ' with WebCheckApp — security score ' . $scan->score . '/100 (Grade ' . $scan->grade . ')') }}&url={{ urlencode(route('scan.show', $scan)) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.912-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        Share on X
                    </a>

                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('scan.show', $scan)) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        LinkedIn
                    </a>

                    <a href="{{ route('scan.card', $scan) }}"
                       class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Score card
                    </a>
                </div>
            </div>
            <div class="flex items-center gap-6">
                {{-- Animated score circle --}}
                <div class="relative w-24 h-24"
                     x-data="{ score: 0 }"
                     x-init="setTimeout(() => {
                         let target = {{ $scan->score }};
                         let duration = 1200;
                         let start = performance.now();
                         let easeOut = t => 1 - Math.pow(1 - t, 3);
                         let tick = now => {
                             let p = Math.min((now - start) / duration, 1);
                             score = Math.round(easeOut(p) * target);
                             $refs.ring.style.strokeDasharray = (easeOut(p) * {{ round(251.2 * $scan->score / 100) }}).toFixed(1) + ' 251.2';
                             if (p < 1) requestAnimationFrame(tick);
                         };
                         requestAnimationFrame(tick);
                     }, 200)">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 96 96">
                        <circle cx="48" cy="48" r="40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle x-ref="ring" cx="48" cy="48" r="40" fill="none"
                            stroke="{{ $scan->score >= 75 ? '#22c55e' : ($scan->score >= 50 ? '#eab308' : '#ef4444') }}"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-dasharray="0 251.2"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold" x-text="score">0</span>
                        <span class="text-xs text-gray-400">/100</span>
                    </div>
                </div>
                {{-- Grade badge + percentile --}}
                <div class="text-center">
                    <div class="text-6xl font-black {{ $scan->getGradeColorClass() }}">{{ $scan->grade }}</div>
                    <div class="text-xs text-gray-500 mt-1">Overall grade</div>
                    @if(isset($percentile) && $percentile !== null)
                    <div class="mt-2 text-xs bg-white/5 border border-white/10 rounded-full px-3 py-1 text-gray-400">
                        Better than <span class="{{ $scan->getGradeColorClass() }} font-semibold">{{ $percentile }}%</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick scan bar --}}
        <form action="{{ route('scan.store') }}" method="POST" class="mb-8"
              x-data="{ loading: false }" @submit="loading = true; $dispatch('scan-start', { url: $el.querySelector('[name=url]').value })">
            @csrf
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="url"
                           placeholder="Scan another domain…"
                           class="w-full bg-white/5 border border-white/10 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
                <button type="submit" :disabled="loading"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-all shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/40">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-show="!loading">Scan</span>
                    <span x-show="loading">Scanning…</span>
                </button>
            </div>
        </form>

        {{-- What changed since last scan --}}
        @if(!empty($diff) && ($diff['score_delta'] !== 0 || !empty($diff['fixed']) || !empty($diff['broken'])))
        <div class="mb-8 bg-white/3 border border-white/8 rounded-2xl overflow-hidden" x-data="{ open: true }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-white">What changed</span>
                    <span class="text-xs text-gray-500">vs scan from {{ $diff['scan_date']->format('d M Y') }}</span>
                    @if($diff['score_delta'] > 0)
                    <span class="text-xs font-medium text-green-400 bg-green-500/10 px-2 py-0.5 rounded-full">↑ +{{ $diff['score_delta'] }} pts</span>
                    @elseif($diff['score_delta'] < 0)
                    <span class="text-xs font-medium text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full">↓ {{ $diff['score_delta'] }} pts</span>
                    @else
                    <span class="text-xs text-gray-600 bg-white/5 px-2 py-0.5 rounded-full">Score unchanged</span>
                    @endif
                </div>
                <svg class="w-4 h-4 text-gray-600 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="border-t border-white/5 px-5 py-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($diff['fixed']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-green-500 mb-2">Fixed</p>
                        <div class="space-y-1.5">
                            @foreach($diff['fixed'] as $item)
                            <div class="flex items-center gap-2 text-sm text-gray-300">
                                <span class="w-4 h-4 flex-shrink-0 rounded-full bg-green-500/20 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                {{ $item }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if(!empty($diff['broken']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-red-500 mb-2">New issues</p>
                        <div class="space-y-1.5">
                            @foreach($diff['broken'] as $item)
                            <div class="flex items-center gap-2 text-sm text-gray-300">
                                <span class="w-4 h-4 flex-shrink-0 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                </span>
                                {{ $item }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if(empty($diff['fixed']) && empty($diff['broken']))
                    <div class="sm:col-span-2 text-sm text-gray-500">No individual checks changed status since the last scan.</div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Pre-compute counts for tab badges --}}
        @php
            $allChecks = collect($scan->results)
                ->filter(fn($c) => $c['score'] !== null)
                ->flatMap(fn($c) => collect($c['checks'])->map(fn($ch) => array_merge($ch, ['_category' => $c['category']])));
            $failures = $allChecks->where('status', 'fail');
            $warnings = $allChecks->where('status', 'warn');
            $issueCount   = $failures->count() + $warnings->count();
            $malwareCount = $scan->results['malware']['threat_count'] ?? 0;
            $portCount    = $scan->results['ports']['open_danger'] ?? 0;
            $expCount     = collect($scan->results['exposed_files']['checks'] ?? [])->where('status', 'fail')->count();
            $tlsCount     = collect($scan->results['tls']['checks'] ?? [])->whereIn('status', ['fail','warn'])->count();
            $apiCount     = collect($scan->results['api_security']['checks'] ?? [])->whereIn('status', ['fail','warn'])->count();
            $subCount     = collect($scan->results['subdomain_takeover']['checks'] ?? [])->whereIn('status', ['fail','warn'])->count();
            $secCount     = $portCount + $expCount + $tlsCount + $apiCount + $subCount;
            $privCount    = collect($scan->results['privacy']['checks'] ?? [])->whereIn('status', ['fail','warn'])->count();
            // Tab map: which tab does each scored category link to?
            $tabMap = [
                'ssl'                => 'technologie',
                'headers'            => 'technologie',
                'dns'                => 'technologie',
                'performance'        => 'technologie',
                'content'            => 'technologie',
                'exposed_files'      => 'beveiliging',
                'tls'                => 'beveiliging',
                'api_security'       => 'beveiliging',
                'subdomain_takeover' => 'beveiliging',
                'accessibility'      => 'kwaliteit',
                'robots'             => 'kwaliteit',
                'branding'           => 'kwaliteit',
            ];
        @endphp

        {{-- Category scores (scored categories only) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
            @foreach($scan->results as $key => $category)
            @if($category['score'] !== null)
            @php $goTab = $tabMap[$key] ?? 'technologie'; @endphp
            <button type="button"
                    @click="tab = '{{ $goTab }}'; $nextTick(() => document.getElementById('tab-nav').scrollIntoView({behavior: 'smooth', block: 'start'}))"
                    class="bg-white/3 border border-white/8 rounded-xl p-4 text-left hover:bg-white/6 hover:border-white/15 transition cursor-pointer group">
                <div class="text-xs text-gray-500 mb-2 truncate group-hover:text-gray-300 transition">{{ $category['category'] }}</div>
                <div class="text-2xl font-bold {{ $category['score'] >= 75 ? 'text-green-400' : ($category['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                    {{ $category['score'] }}<span class="text-sm font-normal text-gray-500">/100</span>
                </div>
                <div class="mt-2 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $category['score'] >= 75 ? 'bg-green-500' : ($category['score'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                         style="width: {{ $category['score'] }}%"></div>
                </div>
            </button>
            @endif
            @endforeach
        </div>

        {{-- Sticky tab navigation --}}
        <div id="tab-nav" class="sticky top-0 z-30 -mx-4 sm:-mx-6 lg:-mx-8 mb-8">
            <div class="bg-[#0b0b12]/95 backdrop-blur-md border-b border-white/8 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1 flex-wrap py-2">
                    @php
                        $qualityCount = collect($scan->results['accessibility']['checks'] ?? [])
                            ->merge($scan->results['robots']['checks'] ?? [])
                            ->merge($scan->results['branding']['checks'] ?? [])
                            ->whereIn('status', ['fail','warn'])->count();
                        $tabs = [
                            ['id' => 'technologie',  'label' => 'Full Report',   'count' => 0,              'countColor' => ''],
                            ['id' => 'overzicht',    'label' => 'Overview',      'count' => $issueCount,    'countColor' => 'bg-red-500/20 text-red-400'],
                            ['id' => 'trust',        'label' => 'Trust & WHOIS', 'count' => 0,              'countColor' => ''],
                            ['id' => 'malware',      'label' => 'Malware',       'count' => $malwareCount,  'countColor' => 'bg-red-500/20 text-red-400'],
                            ['id' => 'beveiliging',  'label' => 'Security',      'count' => $secCount,      'countColor' => 'bg-red-500/20 text-red-400'],
                            ['id' => 'privacy',      'label' => 'Privacy',       'count' => $privCount,     'countColor' => 'bg-yellow-500/20 text-yellow-400'],
                            ['id' => 'kwaliteit',    'label' => 'Quality',       'count' => $qualityCount,  'countColor' => 'bg-yellow-500/20 text-yellow-400'],
                        ];
                    @endphp
                    @php
                        $premiumTabs = ['trust', 'malware', 'beveiliging', 'privacy', 'kwaliteit'];
                    @endphp
                    @foreach($tabs as $t)
                    @php $isLocked = $scan->isFree() && in_array($t['id'], $premiumTabs); @endphp
                    <button type="button"
                            @click="tab = '{{ $t['id'] }}'"
                            :class="tab === '{{ $t['id'] }}' ? 'bg-indigo-600 text-white border-transparent' : 'text-gray-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="shrink-0 flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border transition-all whitespace-nowrap">
                        @if($isLocked)
                        <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        @endif
                        {{ $t['label'] }}
                        @if($t['count'] > 0)
                        <span class="text-xs font-bold {{ $t['countColor'] }} px-1.5 py-0.5 rounded-full">{{ $t['count'] }}</span>
                        @endif
                        @if($isLocked)
                        <span class="text-[10px] font-bold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full">PRO</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Trust & Reputation panel --}}
        @if($scan->isFree() && empty($scan->results['trust']))
        <div class="mb-10" x-show="tab === 'trust'">
            @include('scan.partials.locked-tab', [
                'title' => 'Trust & WHOIS',
                'description' => 'See domain age, registrar, expiry date, server location, and reputation checks across security databases.',
                'features' => ['Domain Age', 'WHOIS Data', 'Server Location', 'Reputation Check', 'Expiry Alert'],
            ])
        </div>
        @endif
        @if(!empty($scan->results['trust']))
        @php
            $trust        = $scan->results['trust'];
            $whois        = $trust['whois'] ?? null;
            $verdictLevel = $trust['verdict']['level'] ?? 'safe';
            $verdictText  = $trust['verdict']['text'] ?? 'Unknown';
            $verdictColors = [
                'safe'    => ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/30', 'text' => 'text-emerald-400', 'icon_color' => 'text-emerald-400'],
                'warning' => ['bg' => 'bg-yellow-500/10',  'border' => 'border-yellow-500/30',  'text' => 'text-yellow-400',  'icon_color' => 'text-yellow-400'],
                'danger'  => ['bg' => 'bg-red-500/10',     'border' => 'border-red-500/30',     'text' => 'text-red-400',     'icon_color' => 'text-red-400'],
            ];
            $vc = $verdictColors[$verdictLevel] ?? $verdictColors['safe'];
        @endphp
        <div class="mb-10" x-show="tab === 'trust'">

            {{-- Verdict banner --}}
            <div class="{{ $vc['bg'] }} {{ $vc['border'] }} border rounded-2xl p-5 mb-4 flex items-center gap-4">
                @if($verdictLevel === 'safe')
                    <svg class="w-8 h-8 {{ $vc['icon_color'] }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                @elseif($verdictLevel === 'warning')
                    <svg class="w-8 h-8 {{ $vc['icon_color'] }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @else
                    <svg class="w-8 h-8 {{ $vc['icon_color'] }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-lg font-bold {{ $vc['text'] }}">{{ $verdictText }}</p>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 mt-1 text-sm text-gray-400">
                        @if(!empty($trust['location']['city']) && !empty($trust['location']['country']))
                        <span>
                            <span class="text-gray-500">Server:</span>
                            {{ $trust['location']['city'] }}, {{ $trust['location']['country'] }}
                            @if(!empty($trust['location']['ip']))
                            <span class="text-xs text-gray-600 ml-1">({{ $trust['location']['ip'] }})</span>
                            @endif
                        </span>
                        @elseif(!empty($trust['location']['ip']))
                        <span><span class="text-gray-500">IP:</span> {{ $trust['location']['ip'] }}</span>
                        @endif
                        @if($whois)
                        <span>
                            <span class="text-gray-500">Registered:</span>
                            {{ $whois['registered'] }}
                            <span class="text-gray-600">({{ $whois['age_text'] }} ago)</span>
                        </span>
                        @if(!empty($whois['expires']))
                        <span class="{{ ($whois['expires_soon'] ?? false) ? 'text-yellow-400' : '' }}">
                            <span class="text-gray-500">Expires:</span>
                            {{ $whois['expires'] }}
                            @if(!empty($whois['expires_in']))
                            <span class="{{ ($whois['expires_soon'] ?? false) ? 'text-yellow-500' : 'text-gray-600' }}">
                                (in {{ $whois['expires_in'] }})
                            </span>
                            @endif
                        </span>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- WHOIS / Domain information card --}}
            @if($whois)
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden mb-4">
                <div class="px-5 py-3 border-b border-white/5 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Domain Registration (WHOIS)</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-white/5 sm:divide-y-0">
                    {{-- Left column --}}
                    <div class="sm:border-r border-white/5 divide-y divide-white/5">
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Registered</span>
                            <div>
                                <span class="text-sm text-gray-200">{{ $whois['registered'] }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ $whois['age_text'] }} ago</span>
                            </div>
                        </div>
                        @if(!empty($whois['expires']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Expires</span>
                            <div>
                                <span class="text-sm {{ ($whois['expires_soon'] ?? false) ? 'text-yellow-300 font-medium' : 'text-gray-200' }}">{{ $whois['expires'] }}</span>
                                @if(!empty($whois['expires_in']))
                                <span class="text-xs {{ ($whois['expires_soon'] ?? false) ? 'text-yellow-500' : 'text-gray-500' }} ml-2">
                                    {{ $whois['expires_in'] === 'expired' ? 'EXPIRED' : 'in ' . $whois['expires_in'] }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(!empty($whois['updated']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Last updated</span>
                            <span class="text-sm text-gray-200">{{ $whois['updated'] }}</span>
                        </div>
                        @endif
                        @if(!empty($whois['registrar']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Registrar</span>
                            <span class="text-sm text-gray-200">{{ $whois['registrar'] }}</span>
                        </div>
                        @endif
                    </div>
                    {{-- Right column --}}
                    <div class="divide-y divide-white/5">
                        @if(!empty($whois['nameservers']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Nameservers</span>
                            <div class="space-y-0.5">
                                @foreach($whois['nameservers'] as $ns)
                                <div class="text-sm text-gray-200 font-mono">{{ $ns }}</div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(!empty($trust['location']['org']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Hosting</span>
                            <span class="text-sm text-gray-200">{{ $trust['location']['org'] }}</span>
                        </div>
                        @endif
                        @if(!empty($whois['status']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Status</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($whois['status'] as $s)
                                <span class="text-xs bg-white/5 text-gray-400 border border-white/10 px-2 py-0.5 rounded-full">{{ $s }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(!empty($trust['location']['city']) && !empty($trust['location']['country']))
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="text-xs text-gray-500 w-24 shrink-0 pt-0.5">Server</span>
                            <span class="text-sm text-gray-200">
                                {{ $trust['location']['city'] }}, {{ $trust['location']['country'] }}
                                @if(!empty($trust['location']['ip']))
                                <span class="text-xs text-gray-500 ml-1">({{ $trust['location']['ip'] }})</span>
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Security database checks --}}
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-white/5">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Security Database Checks</h3>
                </div>
                <div class="divide-y divide-white/5">
                    @foreach($trust['checks'] as $check)
                    @if($check['id'] === 'trust_location') @continue @endif
                    <div class="flex items-center gap-3 px-5 py-3">
                        @if($check['status'] === 'pass')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @elseif($check['status'] === 'fail')
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        @elseif($check['status'] === 'warn')
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-300">{{ $check['label'] }}</span>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $check['description'] }}</p>
                            @if(!empty($check['recommendation']))
                            <p class="text-xs text-indigo-400 mt-0.5">{{ $check['recommendation'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Malware & Virus Scan panel --}}
        @if($scan->isFree() && empty($scan->results['malware']))
        <div class="mb-10" x-show="tab === 'malware'">
            @include('scan.partials.locked-tab', [
                'title' => 'Malware & Reputation',
                'description' => 'Check if your site is flagged by malware databases, blacklists, and antivirus vendors worldwide.',
                'features' => ['VirusTotal', 'URLhaus', 'Spamhaus', 'PhishTank', 'Cloudflare DNS'],
            ])
        </div>
        @endif
        @if(!empty($scan->results['malware']))
        @php
            $malware     = $scan->results['malware'];
            $hasThreats  = ($malware['threat_count'] ?? 0) > 0;
            $vtCheck     = collect($malware['checks'])->firstWhere('id', 'malware_virustotal');
            $basicChecks = collect($malware['checks'])->filter(fn($c) => $c['id'] !== 'malware_virustotal');
        @endphp
        <div class="mb-10" x-show="tab === 'malware'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ $hasThreats ? 'text-red-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h2 class="text-lg font-semibold">Malware &amp; Virus Scan</h2>
                @if($hasThreats)
                    <span class="text-xs font-medium bg-red-500/15 text-red-400 border border-red-500/25 px-2 py-0.5 rounded-full">{{ $malware['threat_count'] }} threat{{ $malware['threat_count'] > 1 ? 's' : '' }} found</span>
                @else
                    <span class="text-xs font-medium bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 px-2 py-0.5 rounded-full">Clean</span>
                @endif
            </div>

            {{-- Basic checks with expandable details --}}
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden mb-4 divide-y divide-white/5">
                @foreach($basicChecks as $check)
                @php $knowledge = CheckKnowledge::get($check['id']); @endphp
                <div x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left flex items-center gap-3 px-5 py-3.5 {{ $knowledge ? 'cursor-pointer hover:bg-white/3 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        @if($check['status'] === 'pass')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @elseif($check['status'] === 'fail')
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-300">{{ $check['label'] }}</span>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $check['description'] }}</p>
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-600 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse class="border-t border-white/5 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- VirusTotal vendor results --}}
            @if($vtCheck)
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-white/5">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white">VirusTotal</span>
                        <span class="text-xs text-gray-500">— antivirus engine aggregator</span>
                    </div>
                    <span class="text-xs font-bold {{ $vtCheck['status'] === 'fail' ? 'text-red-400' : 'text-emerald-400' }}">
                        {{ $vtCheck['description'] }}
                    </span>
                </div>
                @if(!empty($vtCheck['vendors']))
                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y divide-white/5 sm:divide-y-0">
                    @foreach($vtCheck['vendors'] as $name => $vendor)
                    @php
                        $cat = $vendor['category'] ?? 'undetected';
                        $isBad = in_array($cat, ['malicious', 'suspicious']);
                    @endphp
                    <div class="flex items-center gap-3 px-5 py-2.5 {{ !$loop->last ? 'sm:border-b border-white/5' : '' }}">
                        @if($isBad)
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @elseif($cat === 'harmless' || $cat === 'clean')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                        <span class="text-sm font-medium text-gray-300 w-32 shrink-0">{{ $name }}</span>
                        <span class="text-sm {{ $isBad ? 'text-red-400' : 'text-gray-500' }}">
                            {{ $isBad ? ($vendor['result'] ?? $cat) : 'Clean' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="px-5 py-3 text-sm text-gray-500">No vendor-specific results available yet from VirusTotal.</div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Security locked overlay for free scans --}}
        @if($scan->isFree() && empty($scan->results['ports']))
        <div class="mb-10" x-show="tab === 'beveiliging'">
            @include('scan.partials.locked-tab', [
                'title' => 'Advanced Security Checks',
                'description' => 'Detect open ports, exposed files, API vulnerabilities, TLS weaknesses, and subdomain takeover risks.',
                'features' => ['Open Ports', 'Exposed Files', 'API Security', 'TLS Ciphers', 'Subdomain Takeover'],
            ])
        </div>
        @endif

        {{-- Open Ports panel --}}
        @if(!empty($scan->results['ports']))
        @php
            $ports      = $scan->results['ports'];
            $openDanger = $ports['open_danger'] ?? 0;
        @endphp
        <div class="mb-10" x-show="tab === 'beveiliging'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ $openDanger > 0 ? 'text-red-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <h2 class="text-lg font-semibold">Open Ports</h2>
                @if($openDanger > 0)
                    <span class="text-xs font-medium bg-red-500/15 text-red-400 border border-red-500/25 px-2 py-0.5 rounded-full">{{ $openDanger }} dangerous port{{ $openDanger > 1 ? 's' : '' }} open</span>
                @else
                    <span class="text-xs font-medium bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 px-2 py-0.5 rounded-full">No dangerous ports exposed</span>
                @endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($ports['checks'] as $check)
                @php $knowledge = CheckKnowledge::get($check['id']); @endphp
                <div x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left flex items-center gap-3 px-5 py-3.5 {{ $knowledge ? 'cursor-pointer hover:bg-white/3 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        @if($check['status'] === 'pass')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @elseif($check['status'] === 'fail')
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium {{ $check['status'] === 'fail' ? 'text-red-300' : ($check['status'] === 'warn' ? 'text-yellow-300' : 'text-gray-300') }}">
                                {{ $check['label'] }}
                            </span>
                            @if($check['status'] !== 'pass')
                            <p class="text-xs text-gray-500 mt-0.5">{{ $check['description'] }}</p>
                            @endif
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-600 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse
                         class="border-t border-white/5 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line font-mono text-xs leading-relaxed">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Privacy locked overlay for free scans --}}
        @if($scan->isFree() && empty($scan->results['privacy']))
        <div class="mb-10" x-show="tab === 'privacy'">
            @include('scan.partials.locked-tab', [
                'title' => 'Privacy & GDPR',
                'description' => 'Analyze cookie consent, privacy policy presence, third-party trackers, and GDPR compliance signals.',
                'features' => ['Cookie Consent', 'Privacy Policy', 'Tracker Detection', 'GDPR Compliance'],
            ])
        </div>
        @endif

        {{-- Privacy & GDPR panel --}}
        @if(!empty($scan->results['privacy']))
        @php $privacy = $scan->results['privacy']; @endphp
        <div class="mb-10" x-show="tab === 'privacy'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <h2 class="text-lg font-semibold">Privacy &amp; GDPR</h2>
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($privacy['checks'] as $check)
                @php $knowledge = CheckKnowledge::get($check['id']); @endphp
                <div x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left flex items-center gap-3 px-5 py-3.5 {{ $knowledge ? 'cursor-pointer hover:bg-white/3 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        @if($check['status'] === 'pass')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @elseif($check['status'] === 'fail')
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium {{ $check['status'] === 'fail' ? 'text-red-300' : ($check['status'] === 'warn' ? 'text-yellow-300' : 'text-gray-300') }}">{{ $check['label'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $check['description'] }}</p>
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-600 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse class="border-t border-white/5 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Exposed Files panel --}}
        @if(!empty($scan->results['exposed_files']))
        @php
            $exposedFiles = $scan->results['exposed_files'];
            $exposedCount = collect($exposedFiles['checks'])->where('status', 'fail')->count();
        @endphp
        <div class="mb-10" x-show="tab === 'beveiliging'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ $exposedCount > 0 ? 'text-red-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <h2 class="text-lg font-semibold">Exposed Files</h2>
                @if($exposedCount > 0)
                    <span class="text-xs font-medium bg-red-500/15 text-red-400 border border-red-500/25 px-2 py-0.5 rounded-full">{{ $exposedCount }} file{{ $exposedCount > 1 ? 's' : '' }} exposed</span>
                @else
                    <span class="text-xs font-medium bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 px-2 py-0.5 rounded-full">No sensitive files exposed</span>
                @endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($exposedFiles['checks'] as $check)
                @php $knowledge = CheckKnowledge::get($check['id']); @endphp
                <div x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left flex items-center gap-3 px-5 py-3.5 {{ $knowledge ? 'cursor-pointer hover:bg-white/3 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        @if($check['status'] === 'pass')
                            <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium {{ $check['status'] === 'fail' ? 'text-red-300' : 'text-gray-300' }}">{{ $check['label'] }}</span>
                            @if($check['status'] === 'fail')
                            <p class="text-xs text-gray-500 mt-0.5">{{ $check['description'] }}</p>
                            @endif
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-600 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse class="border-t border-white/5 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line font-mono text-xs leading-relaxed">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- TLS / Cipher panel (beveiliging) --}}
        @if(!empty($scan->results['tls']))
        @php $tls = $scan->results['tls']; @endphp
        <div class="mb-10" x-show="tab === 'beveiliging'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ ($tls['score'] ?? 100) >= 75 ? 'text-emerald-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h2 class="text-lg font-semibold">TLS / Cipher Strength</h2>
                @if(isset($tls['score']))<span class="text-sm font-bold ml-auto {{ $tls['score'] >= 75 ? 'text-green-400' : ($tls['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $tls['score'] }}/100</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($tls['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- API Security panel (beveiliging) --}}
        @if(!empty($scan->results['api_security']))
        @php $apiSec = $scan->results['api_security']; @endphp
        <div class="mb-10" x-show="tab === 'beveiliging'">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ ($apiSec['score'] ?? 100) >= 75 ? 'text-emerald-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <h2 class="text-lg font-semibold">API Security</h2>
                @if(isset($apiSec['score']))<span class="text-sm font-bold ml-auto {{ $apiSec['score'] >= 75 ? 'text-green-400' : ($apiSec['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $apiSec['score'] }}/100</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($apiSec['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5 whitespace-pre-line font-mono text-xs">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Subdomain Takeover panel (beveiliging) --}}
        @if(!empty($scan->results['subdomain_takeover']))
        @php $subTakeover = $scan->results['subdomain_takeover']; @endphp
        <div class="mb-10" x-show="tab === 'beveiliging'">
            <div class="flex items-center gap-3 mb-4">
                @php $takeoverFails = collect($subTakeover['checks'])->where('status','fail')->count(); @endphp
                <svg class="w-5 h-5 {{ $takeoverFails > 0 ? 'text-red-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <h2 class="text-lg font-semibold">Subdomain Takeover</h2>
                @if($takeoverFails > 0)<span class="text-xs font-bold bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full">{{ $takeoverFails }} vulnerable</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($subTakeover['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ═══ Kwaliteit (Quality) tab ═══ --}}
        <div x-show="tab === 'kwaliteit'">

        {{-- Quality locked overlay for free scans --}}
        @if($scan->isFree() && empty($scan->results['accessibility']))
            @include('scan.partials.locked-tab', [
                'title' => 'Quality & Accessibility',
                'description' => 'Check accessibility compliance, robots.txt, branding, broken links, and carbon footprint.',
                'features' => ['Accessibility', 'Robots & SEO', 'Branding', 'Broken Links', 'Carbon Footprint'],
            ])
        @endif

        {{-- Accessibility panel --}}
        @if(!empty($scan->results['accessibility']))
        @php $a11y = $scan->results['accessibility']; @endphp
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ ($a11y['score'] ?? 0) >= 75 ? 'text-emerald-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <h2 class="text-lg font-semibold">Accessibility</h2>
                @if(isset($a11y['score']))<span class="text-sm font-bold ml-auto {{ $a11y['score'] >= 75 ? 'text-green-400' : ($a11y['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $a11y['score'] }}/100</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($a11y['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Robots & Sitemap panel --}}
        @if(!empty($scan->results['robots']))
        @php $robots = $scan->results['robots']; @endphp
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ ($robots['score'] ?? 0) >= 75 ? 'text-emerald-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2 class="text-lg font-semibold">Robots.txt &amp; Sitemap</h2>
                @if(isset($robots['score']))<span class="text-sm font-bold ml-auto {{ $robots['score'] >= 75 ? 'text-green-400' : ($robots['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $robots['score'] }}/100</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($robots['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5 whitespace-pre-line font-mono text-xs">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Branding & Social panel --}}
        @if(!empty($scan->results['branding']))
        @php $branding = $scan->results['branding']; @endphp
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 {{ ($branding['score'] ?? 0) >= 75 ? 'text-emerald-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-lg font-semibold">Branding &amp; Social</h2>
                @if(isset($branding['score']))<span class="text-sm font-bold ml-auto {{ $branding['score'] >= 75 ? 'text-green-400' : ($branding['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $branding['score'] }}/100</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($branding['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        </div>{{-- end kwaliteit tab --}}

        {{-- ═══ Technologie tab ═══ --}}
        <div x-show="tab === 'technologie'">

        {{-- Technology Stack panel --}}
        @if(!empty($scan->results['technology']))
        @php $tech = $scan->results['technology']; @endphp
        <div class="bg-white/2 border border-white/8 rounded-2xl p-5 mb-10">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Detected Technologies</h2>
            @if(!empty($tech['technologies']))
            @php
                $byType = collect($tech['technologies'])->groupBy('type');
            @endphp
            <div class="flex flex-wrap gap-4">
                @foreach($byType as $type => $items)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-600 w-full sm:w-auto">{{ $type }}</span>
                    @foreach($items as $item)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                        {{ $item['name'] }}
                    </span>
                    @endforeach
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500">No technologies detected from the page source and response headers.</p>
            @endif

            {{-- HTTP/2 inline result --}}
            @foreach($tech['checks'] as $check)
            <div class="mt-4 pt-4 border-t border-white/5 flex items-center gap-2 text-sm">
                @if($check['status'] === 'pass')
                    <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-green-400 font-medium">{{ $check['label'] }}</span>
                    <span class="text-gray-500">— {{ $check['description'] }}</span>
                @elseif($check['status'] === 'warn')
                    <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-yellow-400 font-medium">{{ $check['label'] }}</span>
                    <span class="text-gray-500">— {{ $check['description'] }}</span>
                @else
                    <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-400">{{ $check['label'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- Carbon & Sustainability panel --}}
        @if(!empty($scan->results['carbon']))
        @php $carbon = $scan->results['carbon']; @endphp
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-lg font-semibold">Carbon &amp; Sustainability</h2>
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($carbon['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($check['status'] === 'warn')<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @else<svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Broken Links panel --}}
        @if(!empty($scan->results['broken_links']))
        @php $brokenLinks = $scan->results['broken_links']; @endphp
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                @php $brokenCount = collect($brokenLinks['checks'])->where('status','warn')->count(); @endphp
                <svg class="w-5 h-5 {{ $brokenCount > 0 ? 'text-yellow-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <h2 class="text-lg font-semibold">Broken Links</h2>
                @if($brokenCount > 0)<span class="text-xs font-bold bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded-full">{{ $brokenCount }} broken</span>@endif
            </div>
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden divide-y divide-white/5">
                @foreach($brokenLinks['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')<svg class="w-4 h-4 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else<svg class="w-4 h-4 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>@endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))<p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Tier badge --}}
        @if($scan->tier !== 'free')
        <div class="inline-flex items-center gap-1.5 mb-4 text-xs font-semibold px-3 py-1 rounded-full {{ $scan->tier === 'deep' ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            {{ $scan->tierLabel() }}
        </div>
        @endif

        {{-- Upgrade CTA for free scans --}}
        @if($scan->isFree())
        <div class="bg-gradient-to-r from-purple-600/10 to-indigo-600/10 border border-purple-500/20 rounded-2xl p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex-1">
                    <h3 class="font-bold text-white mb-1">Unlock the full security report</h3>
                    <p class="text-sm text-gray-400">This Quick Scan covers 5 categories. Upgrade to Pro for OWASP Top 10 analysis, malware detection, exposed files, and 16 more scanners.</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <form action="{{ route('checkout.create') }}" method="POST">
                        @csrf
                        <input type="hidden" name="url" value="{{ $scan->url }}">
                        <input type="hidden" name="tier" value="pro">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition whitespace-nowrap">
                            Pro Scan &euro;9,99
                        </button>
                    </form>
                    <form action="{{ route('checkout.create') }}" method="POST">
                        @csrf
                        <input type="hidden" name="url" value="{{ $scan->url }}">
                        <input type="hidden" name="tier" value="deep">
                        <button type="submit" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition whitespace-nowrap">
                            Deep Scan &euro;29,99
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Full Report: all scored categories --}}
        <div class="space-y-6 mt-4">
        <h2 class="text-lg font-semibold">Full report</h2>
        @foreach($scan->results as $key => $category)
        @if($category['score'] === null) @continue @endif
        @if(in_array($key, ['exposed_files','tls','api_security','subdomain_takeover','accessibility','robots','branding'])) @continue @endif
        <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                <h3 class="font-semibold text-white">{{ $category['category'] }}</h3>
                <span class="text-sm font-bold {{ $category['score'] >= 75 ? 'text-green-400' : ($category['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                    {{ $category['score'] }}/100
                </span>
            </div>
            <div class="divide-y divide-white/5">
                @foreach($category['checks'] as $check)
                <div class="flex items-start gap-4 px-5 py-4">
                    @if($check['status'] === 'pass')
                        <svg class="w-5 h-5 text-green-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @elseif($check['status'] === 'warn')
                        <svg class="w-5 h-5 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    @elseif($check['status'] === 'info')
                        <svg class="w-5 h-5 text-gray-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white">{{ $check['label'] }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">{{ $check['description'] }}</p>
                        @if(!empty($check['recommendation']))
                        <p class="text-xs text-indigo-400 mt-1.5">Fix: {{ $check['recommendation'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @if(!empty($category['raw_headers']))
            <div class="border-t border-white/5" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-3 text-sm text-gray-500 hover:text-gray-300 transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Response headers ({{ count($category['raw_headers']) }})
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="px-5 pb-4">
                    <div class="bg-gray-900/60 border border-white/5 rounded-lg p-4 font-mono text-xs leading-relaxed overflow-x-auto">
                        @foreach($category['raw_headers'] as $name => $value)
                        <div class="flex gap-2 py-0.5">
                            <span class="text-indigo-400 shrink-0">{{ $name }}:</span>
                            <span class="text-gray-300 break-all">{{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endforeach
        </div>

        </div>{{-- end technologie tab --}}

        {{-- ═══ Overzicht tab ═══ --}}
        <div x-show="tab === 'overzicht'">

        {{-- Action items (failures first) — already computed above as $failures/$warnings --}}
        @if($failures->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Critical issues ({{ $failures->count() }})
            </h2>
            <div class="space-y-3">
                @foreach($failures as $check)
                @php $knowledge = !empty($check['id']) ? CheckKnowledge::get($check['id']) : null; @endphp
                <div class="bg-red-500/5 border border-red-500/20 rounded-xl overflow-hidden"
                     x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left p-4 flex items-start gap-3 {{ $knowledge ? 'cursor-pointer hover:bg-red-500/5 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-white">{{ $check['label'] }}</span>
                                <span class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">{{ $check['_category'] }}</span>
                                @if($knowledge)
                                <span class="text-xs text-indigo-400/60 ml-auto hidden sm:inline">click to expand</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-400">{{ $check['description'] }}</p>
                            @if(!empty($check['recommendation']))
                            <p class="text-sm text-indigo-300 mt-2 flex items-start gap-1.5">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                {{ $check['recommendation'] }}
                            </p>
                            @endif
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-500 mt-0.5 shrink-0 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse class="border-t border-red-500/15 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($warnings->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                Warnings ({{ $warnings->count() }})
            </h2>
            <div class="space-y-3">
                @foreach($warnings as $check)
                @php $knowledge = !empty($check['id']) ? CheckKnowledge::get($check['id']) : null; @endphp
                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-xl overflow-hidden"
                     x-data="{ open: false }">
                    <button type="button"
                            class="w-full text-left p-4 flex items-start gap-3 {{ $knowledge ? 'cursor-pointer hover:bg-yellow-500/5 transition' : 'cursor-default' }}"
                            @if($knowledge) @click="open = !open" @endif>
                        <svg class="w-5 h-5 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-white">{{ $check['label'] }}</span>
                                <span class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">{{ $check['_category'] }}</span>
                                @if($knowledge)
                                <span class="text-xs text-indigo-400/60 ml-auto hidden sm:inline">click to expand</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-400">{{ $check['description'] }}</p>
                            @if(!empty($check['recommendation']))
                            <p class="text-sm text-indigo-300 mt-2 flex items-start gap-1.5">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                {{ $check['recommendation'] }}
                            </p>
                            @endif
                        </div>
                        @if($knowledge)
                        <svg class="w-4 h-4 text-gray-500 mt-0.5 shrink-0 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </button>
                    @if($knowledge)
                    <div x-show="open" x-collapse class="border-t border-yellow-500/15 bg-black/20 px-5 py-4 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">What is this?</p>
                            <p class="text-gray-300">{{ $knowledge['what'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Why does it matter?</p>
                            <p class="text-gray-300">{{ $knowledge['why'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How to fix it</p>
                            <p class="text-gray-300 whitespace-pre-line">{{ $knowledge['how'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        </div>{{-- end overzicht tab --}}


        {{-- Bottom actions --}}
        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-3 flex-wrap">
            {{-- Re-scan same URL --}}
            <form action="{{ route('scan.store') }}" method="POST"
                  x-data @submit="$dispatch('scan-start', { url: '{{ addslashes($scan->url) }}' })">
                @csrf
                <input type="hidden" name="url" value="{{ $scan->url }}">
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-scan {{ $scan->host }}
                </button>
            </form>

            {{-- Download PDF (paid scans only) --}}
            @if(!$scan->isFree())
            <a href="{{ route('scan.pdf', $scan) }}"
               class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-6 py-3 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
            @else
            <span class="inline-flex items-center gap-2 bg-white/3 border border-white/8 text-gray-500 font-semibold px-6 py-3 rounded-xl cursor-not-allowed" title="PDF reports are available with Pro and Deep scans">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                PDF <span class="text-xs text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full ml-1">PRO</span>
            </span>
            @endif

            {{-- Scan another --}}
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-6 py-3 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                Scan another website
            </a>
        </div>

        {{-- Embed badge section --}}
        <div class="mt-10 border border-white/8 rounded-2xl p-6 bg-white/2"
             x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center justify-between w-full text-left">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-sm">Add a security badge to your website</span>
                </div>
                <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-5">
                <p class="text-sm text-gray-400 mb-4">Show visitors your security score with an embeddable badge. It updates automatically when you rescan.</p>

                {{-- Badge preview --}}
                <div class="flex items-center gap-4 mb-5">
                    <a href="{{ route('scan.show', $scan) }}" target="_blank">
                        <img src="{{ route('scan.badge', $scan) }}" alt="WebCheckApp security badge" class="h-5">
                    </a>
                    <span class="text-xs text-gray-500">Preview</span>
                </div>

                {{-- HTML snippet --}}
                <div x-data="{ copied: false }" class="relative">
                    <pre class="text-xs bg-gray-900 border border-white/10 rounded-xl p-4 overflow-x-auto text-gray-300 leading-relaxed"><code>&lt;a href="{{ route('scan.show', $scan) }}"&gt;
  &lt;img src="{{ route('scan.badge', $scan) }}" alt="Security score: {{ $scan->score }}/100"&gt;
&lt;/a&gt;</code></pre>
                    <button
                        @click="navigator.clipboard.writeText(`<a href='{{ route('scan.show', $scan) }}'>\n  <img src='{{ route('scan.badge', $scan) }}' alt='Security score: {{ $scan->score }}/100'>\n</a>`); copied = true; setTimeout(() => copied = false, 2000)"
                        class="absolute top-3 right-3 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-2.5 py-1 rounded-lg transition"
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-green-400">Copied!</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
    @endif

</div>

<script nonce="{{ Vite::cspNonce() }}">
function scanPoller(scanId, statusUrl, alreadyCompleted) {
    return {
        completed: alreadyCompleted,
        failed: false,
        errorMessage: null,
        interval: null,
        retries: 0,
        maxRetries: 100, // ~5 minutes at 3s intervals

        init() {
            if (!this.completed) {
                this.interval = setInterval(() => this.poll(), 3000);
            }
        },

        async poll() {
            this.retries++;
            if (this.retries > this.maxRetries) {
                clearInterval(this.interval);
                this.failed = true;
                return;
            }
            try {
                const res = await fetch(statusUrl);
                if (!res.ok) return;
                const data = await res.json();

                if (typeof updateProgress === 'function' && data.completed_scanners !== undefined) {
                    updateProgress(data.completed_scanners);
                }

                if (data.completed) {
                    clearInterval(this.interval);
                    window.location.reload();
                } else if (data.failed) {
                    clearInterval(this.interval);
                    this.errorMessage = data.error || null;
                    this.failed = true;
                }
            } catch (e) {
                // network error — keep retrying until maxRetries
            }
        }
    }
}
</script>

@endsection
