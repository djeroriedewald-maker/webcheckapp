@extends('layouts.app')

@section('title', 'Score History: ' . $site->domain . ' — WebCheckApp')
@section('meta_description', 'View the security score history for ' . $site->domain . '.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-10">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold">{{ $site->domain }}</h1>
            <p class="text-gray-400 text-sm mt-0.5">Security score history</p>
        </div>
    </div>

    @if($scans->isEmpty())
    <div class="text-center py-16 text-gray-600">
        <p>No completed scans yet for this domain.</p>
    </div>
    @else

    {{-- Score chart --}}
    @php
        $chartData = $scans->sortBy('completed_at')->values();
        $scores = $chartData->pluck('score')->toArray();
        $labels = $chartData->map(fn($s) => $s->completed_at->format('M j'))->toArray();
        $minScore = max(0, min($scores) - 10);
        $maxScore = min(100, max($scores) + 10);
        $range = max($maxScore - $minScore, 1);
    @endphp

    <div class="bg-white/3 border border-white/8 rounded-2xl p-6 mb-8"
         x-data="{
            scores: {{ json_encode($scores) }},
            labels: {{ json_encode($labels) }},
            min: {{ $minScore }},
            max: {{ $maxScore }},
            tooltip: null,
            tooltipX: 0,
            tooltipY: 0,
            getY(score) {
                return 100 - ((score - this.min) / (this.max - this.min)) * 100;
            },
            getPoints() {
                const w = 100 / (this.scores.length - 1 || 1);
                return this.scores.map((s, i) => `${i * w},${this.getY(s)}`).join(' ');
            },
            getAreaPoints() {
                const w = 100 / (this.scores.length - 1 || 1);
                let pts = this.scores.map((s, i) => `${i * w},${this.getY(s)}`).join(' ');
                return `0,100 ${pts} ${100},100`;
            }
         }">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-6">Score over time</h2>

        {{-- SVG chart --}}
        <div class="relative">
            <svg viewBox="0 0 100 60" preserveAspectRatio="none" class="w-full h-40"
                 @mousemove="
                    const rect = $el.getBoundingClientRect();
                    const xPct = (event.clientX - rect.left) / rect.width;
                    const idx = Math.round(xPct * (scores.length - 1));
                    if (idx >= 0 && idx < scores.length) {
                        tooltip = { score: scores[idx], label: labels[idx] };
                        tooltipX = Math.min(Math.max(xPct * 100, 5), 95);
                        tooltipY = getY(scores[idx]);
                    }
                 "
                 @mouseleave="tooltip = null">
                {{-- Grid lines --}}
                <line x1="0" y1="25" x2="100" y2="25" stroke="white" stroke-opacity="0.05" stroke-width="0.5"/>
                <line x1="0" y1="50" x2="100" y2="50" stroke="white" stroke-opacity="0.05" stroke-width="0.5"/>
                <line x1="0" y1="75" x2="100" y2="75" stroke="white" stroke-opacity="0.05" stroke-width="0.5"/>

                {{-- Area fill --}}
                <polygon :points="getAreaPoints()"
                         fill="url(#chartGrad)" opacity="0.15"/>

                {{-- Line --}}
                <polyline :points="getPoints()"
                          fill="none" stroke="#818cf8" stroke-width="1.5"
                          stroke-linecap="round" stroke-linejoin="round"/>

                {{-- Data points --}}
                <template x-for="(score, i) in scores" :key="i">
                    <circle
                        :cx="i * (100 / (scores.length - 1 || 1))"
                        :cy="getY(score)"
                        r="1.5"
                        :fill="score >= 80 ? '#4ade80' : score >= 60 ? '#fbbf24' : '#f87171'"
                        stroke="#0f172a" stroke-width="0.5"/>
                </template>

                <defs>
                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#818cf8"/>
                        <stop offset="100%" stop-color="#818cf8" stop-opacity="0"/>
                    </linearGradient>
                </defs>
            </svg>

            {{-- Tooltip --}}
            <div x-show="tooltip !== null"
                 x-cloak
                 class="absolute -translate-x-1/2 -translate-y-full pointer-events-none bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-xs shadow-xl"
                 :style="`left: ${tooltipX}%; top: calc(${tooltipY / 60 * 100}% - 8px)`">
                <p class="font-semibold text-white" x-text="tooltip?.score + '/100'"></p>
                <p class="text-gray-400" x-text="tooltip?.label"></p>
            </div>
        </div>

        {{-- X-axis labels --}}
        <div class="flex justify-between text-xs text-gray-600 mt-2 px-0">
            <span>{{ $chartData->first()->completed_at->format('M j') }}</span>
            <span>{{ $chartData->last()->completed_at->format('M j') }}</span>
        </div>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        @php
            $latest = $scans->first();
            $oldest = $scans->last();
            $trend  = $latest->score - $oldest->score;
        @endphp
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 text-center">
            <p class="text-xs text-gray-500 mb-1">Current score</p>
            <p class="text-3xl font-black {{ $latest->score >= 80 ? 'text-green-400' : ($latest->score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                {{ $latest->grade }}
            </p>
            <p class="text-sm text-gray-400">{{ $latest->score }}/100</p>
        </div>
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 text-center">
            <p class="text-xs text-gray-500 mb-1">Best score</p>
            <p class="text-3xl font-black text-green-400">{{ $scans->max('score') }}</p>
            <p class="text-xs text-gray-600">of {{ $scans->count() }} scans</p>
        </div>
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 text-center">
            <p class="text-xs text-gray-500 mb-1">Trend</p>
            <p class="text-3xl font-black {{ $trend >= 0 ? 'text-green-400' : 'text-red-400' }}">
                {{ $trend >= 0 ? '+' : '' }}{{ $trend }}
            </p>
            <p class="text-xs text-gray-600">vs. first scan</p>
        </div>
    </div>

    {{-- Scan history table --}}
    <div class="border border-white/8 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-4 bg-white/5 text-xs font-medium text-gray-400 px-5 py-3">
            <div>Date</div>
            <div class="text-center">Score</div>
            <div class="text-center">Grade</div>
            <div class="text-right">Report</div>
        </div>
        @foreach($scans as $scan)
        <div class="grid grid-cols-4 border-t border-white/5 px-5 py-3 text-sm hover:bg-white/2 transition-colors">
            <div class="text-gray-400">{{ $scan->completed_at->format('M j, Y') }}
                <span class="text-gray-600 text-xs">{{ $scan->completed_at->format('H:i') }}</span>
            </div>
            <div class="text-center">
                <span class="font-semibold {{ $scan->score >= 80 ? 'text-green-400' : ($scan->score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                    {{ $scan->score }}
                </span>
            </div>
            <div class="text-center">
                <span class="text-xs font-bold px-2 py-0.5 rounded {{ $scan->score >= 80 ? 'bg-green-500/15 text-green-400' : ($scan->score >= 60 ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400') }}">
                    {{ $scan->grade }}
                </span>
            </div>
            <div class="text-right">
                <a href="{{ route('scan.show', $scan) }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                    View &rarr;
                </a>
            </div>
        </div>
        @endforeach
    </div>

    @endif

</div>
@endsection
