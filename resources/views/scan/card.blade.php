<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $scan->host }} — {{ $scan->grade }} {{ $scan->score }}/100 | WebCheckApp</title>

    {{-- Social sharing meta --}}
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="{{ $scan->host }} scores {{ $scan->grade }} ({{ $scan->score }}/100) on WebCheckApp">
    <meta property="og:description" content="Security scan result: Grade {{ $scan->grade }}, Score {{ $scan->score }}/100. Check your website's security for free.">
    <meta property="og:url"         content="{{ route('scan.card', $scan) }}">
    <meta name="twitter:card"       content="summary">
    <meta name="twitter:title"      content="{{ $scan->host }} — {{ $scan->grade }} {{ $scan->score }}/100 | WebCheckApp">
    <meta name="twitter:description" content="Security scan result: Grade {{ $scan->grade }}, Score {{ $scan->score }}/100.">
    <meta name="robots"             content="noindex, follow">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center p-6">

@php
    $score = $scan->score ?? 0;
    $grade = $scan->grade ?? 'F';

    $scoreColor = $score >= 80 ? 'text-green-400'  : ($score >= 60 ? 'text-amber-400'  : 'text-red-400');
    $scoreBg    = $score >= 80 ? 'bg-green-500/15'  : ($score >= 60 ? 'bg-amber-500/15'  : 'bg-red-500/15');
    $scoreBorder= $score >= 80 ? 'border-green-500/25' : ($score >= 60 ? 'border-amber-500/25' : 'border-red-500/25');
    $scoreGlow  = $score >= 80 ? 'shadow-green-500/20'  : ($score >= 60 ? 'shadow-amber-500/20'  : 'shadow-red-500/20');

    $categories = collect($scan->results ?? [])
        ->filter(fn($c) => isset($c['score']))
        ->sortByDesc('score')
        ->take(3)
        ->values();

    $allChecks = collect($scan->results ?? [])->flatMap(fn($c) => $c['checks'] ?? []);
    $failures  = $allChecks->where('status', 'fail')->take(3)->values();
@endphp

<div class="w-full max-w-md mx-auto">

    {{-- Card --}}
    <div class="relative bg-gray-900 border border-white/10 rounded-3xl overflow-hidden shadow-2xl shadow-black/60">

        {{-- Top glow --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-80 h-40 {{ str_replace('text-', 'bg-', $scoreColor) }}/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative p-8">

            {{-- Brand --}}
            <div class="flex items-center gap-2 text-xs text-gray-600 mb-8">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                WebCheckApp Security Score
            </div>

            {{-- Domain + score --}}
            <div class="flex items-center justify-between gap-6 mb-8">
                <div class="min-w-0">
                    <p class="text-2xl font-black text-white truncate">{{ $scan->host }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $scan->completed_at->format('d M Y') }}</p>
                </div>
                <div class="flex-shrink-0 w-20 h-20 rounded-2xl {{ $scoreBg }} border {{ $scoreBorder }} flex flex-col items-center justify-center shadow-xl {{ $scoreGlow }}">
                    <span class="text-3xl font-black {{ $scoreColor }} leading-none">{{ $grade }}</span>
                    <span class="text-xs {{ $scoreColor }} mt-1">{{ $score }}/100</span>
                </div>
            </div>

            {{-- Top category scores --}}
            @if($categories->isNotEmpty())
            <div class="grid grid-cols-3 gap-3 mb-8">
                @foreach($categories as $cat)
                @php
                    $cs = $cat['score'] ?? 0;
                    $cc = $cs >= 80 ? 'text-green-400 bg-green-500/10 border-green-500/20'
                                    : ($cs >= 60 ? 'text-amber-400 bg-amber-500/10 border-amber-500/20'
                                                 : 'text-red-400 bg-red-500/10 border-red-500/20');
                @endphp
                <div class="bg-white/3 border border-white/8 rounded-xl p-3 text-center">
                    <p class="text-xs text-gray-500 truncate mb-1">{{ Str::before($cat['name'], ' &') }}</p>
                    <p class="text-base font-bold {{ explode(' ', $cc)[0] }}">{{ $cs }}</p>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Top issues --}}
            @if($failures->isNotEmpty())
            <div class="space-y-2 mb-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">Top issues</p>
                @foreach($failures as $issue)
                <div class="flex items-start gap-2.5 text-sm">
                    <span class="flex-shrink-0 w-4 h-4 rounded-full bg-red-500/20 flex items-center justify-center mt-0.5">
                        <svg class="w-2.5 h-2.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                    <span class="text-gray-400">{{ $issue['label'] }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex items-center gap-2 text-sm text-green-400 bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 mb-8">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                No critical security issues found
            </div>
            @endif

            {{-- CTA --}}
            <div class="flex flex-col sm:flex-row items-stretch gap-3">
                <a href="{{ route('scan.show', $scan) }}"
                   class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-5 py-3 rounded-xl transition text-center">
                    View full report →
                </a>
                <a href="{{ route('home') }}"
                   class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 text-sm px-5 py-3 rounded-xl transition text-center">
                    Scan your site
                </a>
            </div>
        </div>
    </div>

    {{-- Copy link --}}
    <div class="mt-6 text-center">
        <button
            onclick="navigator.clipboard.writeText(window.location.href).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy share link', 2000) })"
            class="text-sm text-gray-600 hover:text-gray-400 transition">
            Copy share link
        </button>
    </div>
</div>

</body>
</html>
