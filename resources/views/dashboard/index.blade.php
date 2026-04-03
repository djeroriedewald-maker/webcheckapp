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
        <div class="flex items-center gap-3">
            @if($sites->isNotEmpty())
            <form action="{{ route('dashboard.refreshAll') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 px-3 py-1.5 rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Scan all sites
                </button>
            </form>
            @endif
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-300 transition">Sign out</button>
            </form>
        </div>
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

    {{-- SSL Warnings --}}
    @if(!empty($sslWarnings))
    <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <h2 class="text-sm font-semibold text-red-400 uppercase tracking-wider">SSL Certificate Warnings</h2>
        </div>
        @foreach($sslWarnings as $warn)
        <div class="flex items-start gap-2 text-sm mb-1.5">
            <span class="text-red-400 font-semibold shrink-0">{{ $warn['domain'] }}:</span>
            <span class="text-gray-400">{{ $warn['description'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Score trend + Grade distribution --}}
    @if($stats['total'] > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        {{-- Score trend chart --}}
        <div class="lg:col-span-2 bg-white/3 border border-white/8 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Score trend (30 days)</h2>
            @if(!empty($scoreTrend) && max($scoreTrend) > 0)
            @php
                $maxVal = max(max(array_values($scoreTrend)), 1);
                $vals = array_values($scoreTrend);
                $days = array_keys($scoreTrend);
            @endphp
            <svg viewBox="0 0 600 120" class="w-full" preserveAspectRatio="none">
                {{-- Grid lines --}}
                @foreach([25, 50, 75, 100] as $line)
                <line x1="0" y1="{{ 110 - ($line / 100 * 100) }}" x2="600" y2="{{ 110 - ($line / 100 * 100) }}" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                @endforeach
                {{-- Area fill --}}
                <path d="M0,110 @foreach($vals as $i => $v)L{{ $i * (600 / (count($vals) - 1)) }},{{ $v > 0 ? 110 - ($v / 100 * 100) : 110 }} @endforeach L600,110 Z" fill="url(#trendGrad)" opacity="0.3"/>
                {{-- Line --}}
                <polyline fill="none" stroke="#6366f1" stroke-width="2" stroke-linejoin="round"
                    points="@foreach($vals as $i => $v){{ $i * (600 / (count($vals) - 1)) }},{{ $v > 0 ? 110 - ($v / 100 * 100) : 110 }} @endforeach"/>
                {{-- Dots for non-zero days --}}
                @foreach($vals as $i => $v)
                @if($v > 0)
                <circle cx="{{ $i * (600 / (count($vals) - 1)) }}" cy="{{ 110 - ($v / 100 * 100) }}" r="3" fill="#6366f1">
                    <title>{{ $days[$i] }}: {{ $v }}/100</title>
                </circle>
                @endif
                @endforeach
                <defs><linearGradient id="trendGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#6366f1" stop-opacity="0"/></linearGradient></defs>
            </svg>
            <div class="flex justify-between text-xs text-gray-600 mt-2">
                <span>{{ \Carbon\Carbon::parse($days[0])->format('d M') }}</span>
                <span>{{ \Carbon\Carbon::parse($days[count($days)-1])->format('d M') }}</span>
            </div>
            @else
            <p class="text-sm text-gray-600 py-8 text-center">Not enough data yet. Scores will appear here after your sites are scanned.</p>
            @endif
        </div>

        {{-- Grade distribution --}}
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Grade distribution</h2>
            @if(!empty($gradeDistribution))
            @php
                $gradeColors = [
                    'A+' => 'bg-emerald-500', 'A' => 'bg-emerald-500', 'A-' => 'bg-emerald-500',
                    'B+' => 'bg-green-500', 'B' => 'bg-green-500', 'B-' => 'bg-green-500',
                    'C+' => 'bg-yellow-500', 'C' => 'bg-yellow-500', 'C-' => 'bg-yellow-500',
                    'D+' => 'bg-orange-500', 'D' => 'bg-orange-500', 'D-' => 'bg-orange-500',
                    'F' => 'bg-red-500',
                ];
                $gradeOrder = ['A+','A','A-','B+','B','B-','C+','C','C-','D+','D','D-','F'];
                $gradeTotal = max(array_sum($gradeDistribution), 1);
            @endphp
            <div class="space-y-2">
                @foreach($gradeOrder as $grade)
                @if(isset($gradeDistribution[$grade]))
                @php $pct = round($gradeDistribution[$grade] / $gradeTotal * 100); @endphp
                <div class="flex items-center gap-3">
                    <span class="w-6 text-sm font-bold text-white text-right">{{ $grade }}</span>
                    <div class="flex-1 h-4 bg-white/5 rounded-full overflow-hidden">
                        <div class="{{ $gradeColors[$grade] ?? 'bg-gray-500' }} h-full rounded-full transition-all" style="width: {{ max($pct, 6) }}%"></div>
                    </div>
                    <span class="w-6 text-xs text-gray-400 text-right">{{ $gradeDistribution[$grade] }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-600 py-4 text-center">No grades yet.</p>
            @endif
        </div>
    </div>
    @endif

    {{-- Top issues + Tips row --}}
    @if($topIssuesSummary->isNotEmpty() || $tips->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        {{-- Top issues --}}
        @if($topIssuesSummary->isNotEmpty())
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Top issues across your sites</h2>
            <div class="space-y-3">
                @foreach($topIssuesSummary as $issue)
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $issue['status'] === 'fail' ? 'bg-red-500/15 text-red-400' : 'bg-yellow-500/15 text-yellow-400' }}">
                        {{ $issue['count'] }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-white">{{ $issue['label'] }}</p>
                        <p class="text-xs text-gray-600">{{ $issue['category'] }} — {{ $issue['domains'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Personalized tips --}}
        @if($tips->isNotEmpty())
        <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-indigo-400 uppercase tracking-wider mb-4">Quick wins</h2>
            <div class="space-y-3">
                @foreach($tips as $tip)
                <div class="flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-indigo-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-sm text-gray-300">{{ $tip }}</p>
                </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-600 mt-4">Fixing these issues will have the biggest impact on your overall scores.</p>
        </div>
        @endif
    </div>
    @endif

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

        <form x-show="!bulk" action="{{ route('dashboard.addSite') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="domain" placeholder="example.com" value="{{ old('domain') }}"
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition whitespace-nowrap">
                Add site
            </button>
        </form>

        <form x-show="bulk" x-cloak action="{{ route('dashboard.bulkImport') }}" method="POST">
            @csrf
            <textarea name="domains" rows="4" placeholder="example.com&#10;mysite.nl&#10;another-domain.com"
                      class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none text-sm mb-3"></textarea>
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-600">One domain per line.</p>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm whitespace-nowrap">Import sites</button>
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
                   ? $site->last_score - $site->previous_score : null;
        @endphp
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5 hover:bg-white/4 transition-colors" x-data="{ open: false }">
            <div class="flex items-center justify-between gap-4">
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
                            @if($delta !== null)
                                @if($delta > 0)
                                <span class="inline-flex items-center gap-0.5 text-xs text-green-400 bg-green-500/10 px-1.5 py-0.5 rounded-md font-medium shrink-0">↑ +{{ $delta }}</span>
                                @elseif($delta < 0)
                                <span class="inline-flex items-center gap-0.5 text-xs text-red-400 bg-red-500/10 px-1.5 py-0.5 rounded-md font-medium shrink-0">↓ {{ $delta }}</span>
                                @endif
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">
                            @if($site->last_checked_at) Checked {{ $site->last_checked_at->diffForHumans() }}
                            @else Not yet scanned @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($site->lastScan)
                    <a href="{{ route('scan.show', $site->lastScan) }}" class="text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition">Report</a>
                    @endif
                    <a href="{{ route('dashboard.history', $site->domain) }}" class="text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">History</a>
                    <form action="{{ route('dashboard.refresh', $site) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">Rescan</button>
                    </form>
                    <button @click="open = !open" class="text-xs text-gray-500 hover:text-gray-300 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition">···</button>
                    <form action="{{ route('dashboard.removeSite', $site) }}" method="POST" onsubmit="return confirm('Remove {{ $site->domain }} from monitoring?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-400 bg-red-500/5 hover:bg-red-500/10 px-3 py-1.5 rounded-lg transition">Remove</button>
                    </form>
                </div>
            </div>
            <div x-show="open" x-cloak class="border-t border-white/5 mt-4 pt-4">
                <form action="{{ route('dashboard.notifications', $site) }}" method="POST" class="flex flex-wrap items-center gap-6">
                    @csrf @method('PATCH')
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                        <input type="checkbox" name="notify_score_drop" value="1" {{ $site->notify_score_drop ? 'checked' : '' }} class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        Alert me when score drops
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                        <input type="checkbox" name="notify_cert_expiry" value="1" {{ $site->notify_cert_expiry ? 'checked' : '' }} class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        Alert me 30 days before SSL expiry
                    </label>
                    <button type="submit" class="text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition">Save</button>
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
                    <span class="text-sm font-bold w-10 text-center {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $scan->score }}</span>
                    <span class="text-sm text-white truncate">{{ $scan->host }}</span>
                    @if($scan->tier !== 'free')
                    <span class="text-[10px] font-bold {{ $scan->tier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-1.5 py-0.5 rounded-full shrink-0">{{ strtoupper($scan->tier) }}</span>
                    @endif
                </div>
                <span class="text-xs text-gray-600 shrink-0">{{ $scan->completed_at->diffForHumans() }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Upgrade CTA for free users --}}
    @if(!$grantedTier)
    <div class="mt-8 bg-gradient-to-r from-purple-600/10 to-indigo-600/10 border border-purple-500/20 rounded-2xl p-6 text-center">
        <h3 class="text-lg font-bold text-white mb-2">Get deeper insights with Pro or Deep scans</h3>
        <p class="text-sm text-gray-400 mb-4 max-w-lg mx-auto">Unlock OWASP Top 10 analysis, malware detection, exposed file scanning, and 20+ additional security checks for any website.</p>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold px-6 py-3 rounded-xl transition shadow-lg shadow-purple-500/25">
            View scan options →
        </a>
    </div>
    @endif

</div>
@endsection
