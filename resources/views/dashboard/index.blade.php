@extends('layouts.app')

@section('title', 'Dashboard — WebCheckApp')
@section('meta_description', 'Monitor your websites and track security scores over time.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ auth()->user()->name ?? auth()->user()->email }}
                @if($grantedTier)
                <span class="ml-2 text-xs font-bold {{ $grantedTier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-2 py-0.5 rounded-full">
                    {{ strtoupper($grantedTier) }} account
                </span>
                @endif
            </p>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-300 transition">Sign out</button>
        </form>
    </div>

    {{-- Stats bar --}}
    @php
        $avg = $stats['avg_score'] ? (int) round($stats['avg_score']) : null;
        $avgColor = $avg === null ? 'text-gray-400'
                  : ($avg >= 80 ? 'text-green-400' : ($avg >= 60 ? 'text-amber-400' : 'text-red-400'));
        $avgBg    = $avg === null ? 'bg-white/3'
                  : ($avg >= 80 ? 'bg-green-500/8' : ($avg >= 60 ? 'bg-amber-500/8' : 'bg-red-500/8'));
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="{{ $avgBg }} border border-white/8 rounded-2xl p-5 text-center">
            <p class="text-3xl font-black {{ $avgColor }}">{{ $avg ?? '—' }}</p>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider">Avg. score</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $stats['total'] }}<span class="text-gray-700 text-lg font-normal">/10</span></p>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider">Sites</p>
        </div>

        <div class="{{ $stats['healthy'] > 0 ? 'bg-green-500/8 border-green-500/20' : 'bg-white/3 border-white/8' }} border rounded-2xl p-5 text-center">
            <p class="text-3xl font-black {{ $stats['healthy'] > 0 ? 'text-green-400' : 'text-gray-400' }}">{{ $stats['healthy'] }}</p>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider">Healthy (80+)</p>
        </div>

        <div class="{{ $stats['critical'] > 0 ? 'bg-red-500/8 border-red-500/20' : 'bg-white/3 border-white/8' }} border rounded-2xl p-5 text-center">
            <p class="text-3xl font-black {{ $stats['critical'] > 0 ? 'text-red-400' : 'text-gray-400' }}">{{ $stats['critical'] }}</p>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider">Critical (&lt;60)</p>
        </div>

        <div class="bg-indigo-500/8 border border-indigo-500/20 rounded-2xl p-5 text-center">
            <p class="text-3xl font-black text-indigo-400">{{ $totalUserScans }}</p>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider">Total scans</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-green-400">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-red-400">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Add site form --}}
    <div class="bg-white/3 border border-white/8 rounded-2xl p-6 mb-4" x-data="{ bulk: false }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Add a site to monitor</h2>
            <button @click="bulk = !bulk" class="text-xs text-gray-600 hover:text-gray-400 transition" x-text="bulk ? 'Single domain' : 'Import multiple'"></button>
        </div>

        {{-- Single domain --}}
        <form x-show="!bulk" action="{{ route('dashboard.addSite') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="domain" placeholder="example.com" value="{{ old('domain') }}"
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition whitespace-nowrap">
                Add site
            </button>
        </form>

        {{-- Bulk import --}}
        <form x-show="bulk" x-cloak action="{{ route('dashboard.bulkImport') }}" method="POST">
            @csrf
            <textarea name="domains" rows="4" placeholder="example.com&#10;mysite.nl&#10;another-domain.com"
                      class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none text-sm mb-3"></textarea>
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-600">One domain per line. No scans run immediately on bulk import.</p>
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm whitespace-nowrap">
                    Import sites
                </button>
            </div>
        </form>

        <p class="text-xs text-gray-700 mt-3" x-show="!bulk">Up to 10 sites. An initial scan runs immediately after adding.</p>
    </div>

    {{-- Site list --}}
    @if($sites->isEmpty())
    <div class="text-center py-20 text-gray-600">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <p class="text-sm">No sites added yet. Add your first site above.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($sites as $site)
        @php
            $s     = $site->last_score;
            $sBg   = $s !== null ? ($s >= 80 ? 'bg-green-500/15 border-green-500/20' : ($s >= 60 ? 'bg-amber-500/15 border-amber-500/20' : 'bg-red-500/15 border-red-500/20')) : 'bg-white/5 border-white/8';
            $sTxt  = $s !== null ? ($s >= 80 ? 'text-green-400' : ($s >= 60 ? 'text-amber-400' : 'text-red-400')) : 'text-gray-600';
            $sSub  = $s !== null ? ($s >= 80 ? 'text-green-500' : ($s >= 60 ? 'text-amber-500' : 'text-red-500')) : '';

            $delta = ($site->previous_score !== null && $site->last_score !== null)
                   ? $site->last_score - $site->previous_score
                   : null;
        @endphp
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 hover:bg-white/4 transition-colors" x-data="{ open: false }">
            <div class="flex items-center justify-between gap-4">

                {{-- Score badge --}}
                <div class="flex items-center gap-4 min-w-0">
                    <div class="flex-shrink-0 w-14 h-14 rounded-xl border {{ $sBg }} flex flex-col items-center justify-center">
                        @if($s !== null)
                        <span class="text-xl font-black {{ $sTxt }} leading-none">{{ $site->last_grade }}</span>
                        <span class="text-[10px] {{ $sSub }} mt-0.5">{{ $s }}</span>
                        @else
                        <span class="text-gray-600 text-xs">–</span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-white truncate">{{ $site->domain }}</p>
                            {{-- Trend arrow --}}
                            @if($delta !== null)
                                @if($delta > 0)
                                <span class="inline-flex items-center gap-0.5 text-xs text-green-400 bg-green-500/10 px-1.5 py-0.5 rounded-md font-medium flex-shrink-0">
                                    ↑ +{{ $delta }}
                                </span>
                                @elseif($delta < 0)
                                <span class="inline-flex items-center gap-0.5 text-xs text-red-400 bg-red-500/10 px-1.5 py-0.5 rounded-md font-medium flex-shrink-0">
                                    ↓ {{ $delta }}
                                </span>
                                @endif
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">
                            @if($site->last_checked_at)
                                Checked {{ $site->last_checked_at->diffForHumans() }}
                            @else
                                Not yet scanned
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($site->lastScan)
                    <a href="{{ route('scan.show', $site->lastScan) }}"
                       class="text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition">
                        Report
                    </a>
                    <a href="{{ route('scan.card', $site->lastScan) }}"
                       class="text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">
                        Share
                    </a>
                    @endif
                    <a href="{{ route('dashboard.history', $site->domain) }}"
                       class="text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">
                        History
                    </a>

                    <form action="{{ route('dashboard.refresh', $site) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">
                            Rescan
                        </button>
                    </form>

                    <button @click="open = !open"
                            class="text-xs text-gray-500 hover:text-gray-300 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">
                        ···
                    </button>

                    <form action="{{ route('dashboard.removeSite', $site) }}" method="POST"
                          onsubmit="return confirm('Remove {{ $site->domain }} from monitoring?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-xs text-red-500 hover:text-red-400 bg-red-500/5 hover:bg-red-500/10 px-3 py-1.5 rounded-lg transition">
                            Remove
                        </button>
                    </form>
                </div>
            </div>

            {{-- Notifications settings panel --}}
            <div x-show="open" x-cloak class="border-t border-white/5 mt-4 pt-4">
                <form action="{{ route('dashboard.notifications', $site) }}" method="POST"
                      class="flex flex-wrap items-center gap-6">
                    @csrf
                    @method('PATCH')
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                        <input type="checkbox" name="notify_score_drop" value="1"
                               {{ $site->notify_score_drop ? 'checked' : '' }}
                               class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        Alert me when score drops
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                        <input type="checkbox" name="notify_cert_expiry" value="1"
                               {{ $site->notify_cert_expiry ? 'checked' : '' }}
                               class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        Alert me 30 days before SSL expiry
                    </label>
                    <button type="submit"
                            class="text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition">
                        Save
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Recent scan history --}}
    @if($recentScans->isNotEmpty())
    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden mt-8">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Recent scans</h2>
        </div>
        <div class="divide-y divide-white/5">
            @foreach($recentScans as $scan)
            <a href="{{ route('scan.show', $scan) }}" class="flex items-center justify-between px-5 py-3 hover:bg-white/2 transition">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-sm font-bold w-10 text-center {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $scan->score }}
                    </span>
                    <span class="text-sm text-white truncate">{{ $scan->host }}</span>
                    @if($scan->tier !== 'free')
                    <span class="text-[10px] font-bold {{ $scan->tier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-1.5 py-0.5 rounded-full shrink-0">
                        {{ strtoupper($scan->tier) }}
                    </span>
                    @endif
                </div>
                <span class="text-xs text-gray-600 shrink-0">{{ $scan->completed_at->diffForHumans() }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
