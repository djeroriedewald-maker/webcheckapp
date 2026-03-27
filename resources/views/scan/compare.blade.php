@extends('layouts.app')

@section('title', $scanA && $scanB ? 'Compare: ' . $scanA->host . ' vs ' . $scanB->host . ' — WebCheckApp' : 'Compare two websites — WebCheckApp')
@section('meta_description', 'Compare the security scores of two websites side by side.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold mb-2">Compare two websites</h1>
        <p class="text-gray-400">Enter two domains to see their security scores side by side.</p>
    </div>

    {{-- Input form --}}
    <form action="{{ route('scan.compare') }}" method="GET"
          x-data="{ loading: false }" @submit.prevent="loading = true; $dispatch('scan-start', { url: $el.querySelector('[name=a]').value || $el.querySelector('[name=b]').value, form: null }); $el.submit()">
        <div class="flex flex-col sm:flex-row gap-3 max-w-3xl mx-auto mb-12">
            <input type="text" name="a" value="{{ $urlA }}" placeholder="first-domain.com"
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <div class="flex items-center justify-center text-gray-600 font-bold text-sm px-2">VS</div>
            <input type="text" name="b" value="{{ $urlB }}" placeholder="second-domain.com"
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <button type="submit" :disabled="loading"
                    class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 text-white font-semibold px-6 py-3 rounded-xl transition">
                <span x-show="!loading">Compare</span>
                <span x-show="loading">Scanning…</span>
            </button>
        </div>
    </form>

    @if($scanA || $scanB)
    {{-- Score overview --}}
    <div class="grid grid-cols-2 gap-4 mb-10">
        @foreach([[$scanA, $urlA], [$scanB, $urlB]] as [$scan, $rawUrl])
        <div class="bg-white/3 border border-white/8 rounded-2xl p-6 text-center">
            @if($scan && $scan->isCompleted())
                <p class="text-sm text-gray-500 mb-1">{{ $scan->host }}</p>
                <div class="text-5xl font-black mb-1 {{ $scan->score >= 80 ? 'text-green-400' : ($scan->score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                    {{ $scan->grade }}
                </div>
                <p class="text-2xl font-bold text-white">{{ $scan->score }}<span class="text-gray-500 text-base">/100</span></p>
                <a href="{{ route('scan.show', $scan) }}" class="text-xs text-indigo-400 hover:text-indigo-300 mt-2 inline-block">Full report &rarr;</a>
            @elseif($rawUrl)
                <p class="text-sm text-gray-500 mb-4">{{ $rawUrl }}</p>
                <p class="text-red-400 text-sm">Scan failed</p>
            @else
                <p class="text-gray-600 text-sm">No domain entered</p>
            @endif
        </div>
        @endforeach
    </div>

    @if($scanA && $scanA->isCompleted() && $scanB && $scanB->isCompleted())
    {{-- Category comparison table --}}
    <div class="border border-white/8 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-3 bg-white/5 text-sm font-medium text-gray-400 px-5 py-3">
            <div>Category</div>
            <div class="text-center">{{ $scanA->host }}</div>
            <div class="text-center">{{ $scanB->host }}</div>
        </div>

        @php
            $categories = array_unique(array_merge(array_keys($scanA->results ?? []), array_keys($scanB->results ?? [])));
            $catLabels = ['ssl' => 'SSL & HTTPS', 'headers' => 'Security Headers', 'dns' => 'DNS & Email', 'performance' => 'Performance', 'content' => 'Content & CMS', 'exposed_files' => 'Exposed Files', 'malware' => 'Malware', 'ports' => 'Open Ports', 'privacy' => 'Privacy', 'trust' => 'Trust & WHOIS', 'technology' => 'Technology'];
        @endphp

        @foreach($categories as $key)
        @php
            $catA = $scanA->results[$key] ?? null;
            $catB = $scanB->results[$key] ?? null;
            $scoreA = $catA['score'] ?? null;
            $scoreB = $catB['score'] ?? null;
            $label = $catLabels[$key] ?? ucfirst($key);
        @endphp
        <div class="grid grid-cols-3 border-t border-white/5 px-5 py-3 text-sm hover:bg-white/2 transition-colors">
            <div class="text-gray-300">{{ $label }}</div>
            <div class="text-center">
                @if($scoreA !== null)
                    <span class="font-semibold {{ $scoreA >= 80 ? 'text-green-400' : ($scoreA >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $scoreA }}</span>
                @else
                    <span class="text-gray-600">—</span>
                @endif
            </div>
            <div class="text-center">
                @if($scoreB !== null)
                    <span class="font-semibold {{ $scoreB >= 80 ? 'text-green-400' : ($scoreB >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $scoreB }}</span>
                @else
                    <span class="text-gray-600">—</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

</div>
@endsection
