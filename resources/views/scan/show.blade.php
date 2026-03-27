@extends('layouts.app')

@section('title', $scan->isCompleted() ? 'Security Report — ' . $scan->host : 'Scanning ' . $scan->host)

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
     x-data="scanPoller({{ $scan->id }}, '{{ route('scan.status', $scan) }}', {{ $scan->isCompleted() ? 'true' : 'false' }})"
     x-init="init()">

    {{-- Loading state --}}
    <div x-show="!completed && !failed" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-indigo-500/10 border border-indigo-500/20 mb-6">
            <svg class="animate-spin w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2">Scanning <span class="text-indigo-400">{{ $scan->host }}</span></h2>
        <p class="text-gray-400">Running security checks... this usually takes 15-30 seconds.</p>
        <div class="mt-8 flex justify-center gap-2">
            @foreach(['SSL & HTTPS', 'Security Headers', 'DNS & Email', 'Performance', 'Content', 'Technology'] as $i => $label)
            <div class="flex flex-col items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: {{ $i * 0.15 }}s"></div>
                <span class="text-xs text-gray-600 hidden sm:block">{{ $label }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Failed state --}}
    <div x-show="failed" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 mb-6">
            <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2">Scan failed</h2>
        <p class="text-gray-400 mb-6">We could not scan <strong>{{ $scan->host }}</strong>. The website may be unreachable.</p>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Try another URL
        </a>
    </div>

    {{-- Completed report --}}
    @if($scan->isCompleted())
    <div x-show="completed">

        {{-- Header with score --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-10">
            <div>
                <p class="text-sm text-gray-500 mb-1">Security report for</p>
                <h1 class="text-2xl font-bold text-white">{{ $scan->host }}</h1>
                <p class="text-sm text-gray-500 mt-1">Scanned {{ $scan->completed_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-6">
                {{-- Score circle --}}
                <div class="relative w-24 h-24">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 96 96">
                        <circle cx="48" cy="48" r="40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle cx="48" cy="48" r="40" fill="none"
                            stroke="{{ $scan->score >= 75 ? '#22c55e' : ($scan->score >= 50 ? '#eab308' : '#ef4444') }}"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-dasharray="{{ round(251.2 * $scan->score / 100) }} 251.2"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold">{{ $scan->score }}</span>
                        <span class="text-xs text-gray-400">/100</span>
                    </div>
                </div>
                {{-- Grade badge --}}
                <div class="text-center">
                    <div class="text-6xl font-black {{ $scan->getGradeColorClass() }}">{{ $scan->grade }}</div>
                    <div class="text-xs text-gray-500 mt-1">Overall grade</div>
                </div>
            </div>
        </div>

        {{-- Category scores (scored categories only) --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-10">
            @foreach($scan->results as $key => $category)
            @if($category['score'] !== null)
            <div class="bg-white/3 border border-white/8 rounded-xl p-4">
                <div class="text-xs text-gray-500 mb-2 truncate">{{ $category['category'] }}</div>
                <div class="text-2xl font-bold {{ $category['score'] >= 75 ? 'text-green-400' : ($category['score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                    {{ $category['score'] }}<span class="text-sm font-normal text-gray-500">/100</span>
                </div>
                <div class="mt-2 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $category['score'] >= 75 ? 'bg-green-500' : ($category['score'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                         style="width: {{ $category['score'] }}%"></div>
                </div>
            </div>
            @endif
            @endforeach
        </div>

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

        {{-- Action items (failures first) --}}
        @php
            // Only include scored categories in the action items list
            $allChecks = collect($scan->results)
                ->filter(fn($c) => $c['score'] !== null)
                ->flatMap(fn($c) => collect($c['checks'])->map(fn($ch) => array_merge($ch, ['_category' => $c['category']])));
            $failures = $allChecks->where('status', 'fail');
            $warnings = $allChecks->where('status', 'warn');
        @endphp

        @if($failures->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Critical issues ({{ $failures->count() }})
            </h2>
            <div class="space-y-3">
                @foreach($failures as $check)
                <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-white">{{ $check['label'] }}</span>
                                <span class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">{{ $check['_category'] }}</span>
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
                    </div>
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
                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-yellow-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-white">{{ $check['label'] }}</span>
                                <span class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">{{ $check['_category'] }}</span>
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
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- All checks per category (scored categories only) --}}
        <div class="space-y-6">
            <h2 class="text-lg font-semibold">Full report</h2>
            @foreach($scan->results as $key => $category)
            @if($category['score'] === null) @continue @endif
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
            </div>
            @endforeach
        </div>

        {{-- Scan another --}}
        <div class="mt-12 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-6 py-3 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Scan another website
            </a>
        </div>

    </div>
    @endif

</div>

<script>
function scanPoller(scanId, statusUrl, alreadyCompleted) {
    return {
        completed: alreadyCompleted,
        failed: false,
        interval: null,

        init() {
            if (!this.completed) {
                this.interval = setInterval(() => this.poll(), 3000);
            }
        },

        async poll() {
            try {
                const res = await fetch(statusUrl);
                const data = await res.json();

                if (data.completed) {
                    clearInterval(this.interval);
                    window.location.reload();
                } else if (data.failed) {
                    clearInterval(this.interval);
                    this.failed = true;
                }
            } catch (e) {
                // keep polling
            }
        }
    }
}
</script>

@endsection
