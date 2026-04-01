@extends('layouts.app')

@section('title', 'Admin Dashboard — WebCheckApp')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-3xl font-black text-white">Admin Dashboard</h1>
            <p class="text-gray-500 mt-1">Real-time platform metrics</p>
        </div>
        <span class="text-xs text-gray-600">Last updated: {{ now()->format('d M Y, H:i') }}</span>
    </div>

    {{-- ═══ Stat cards ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-10">

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Scans today</p>
            <p class="text-2xl font-black text-white mt-1">{{ number_format($scansToday) }}</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">This week</p>
            <p class="text-2xl font-black text-white mt-1">{{ number_format($scansWeek) }}</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">This month</p>
            <p class="text-2xl font-black text-white mt-1">{{ number_format($scansMonth) }}</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total scans</p>
            <p class="text-2xl font-black text-white mt-1">{{ number_format($totalScans) }}</p>
        </div>

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Users</p>
            <p class="text-2xl font-black text-white mt-1">{{ $totalUsers }}</p>
            @if($newUsersWeek > 0)
            <p class="text-xs text-green-400 mt-0.5">+{{ $newUsersWeek }} this week</p>
            @endif
        </div>

        <div class="bg-white/3 border border-white/8 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Avg score</p>
            <p class="text-2xl font-black {{ $avgScore >= 75 ? 'text-green-400' : ($avgScore >= 50 ? 'text-yellow-400' : 'text-red-400') }} mt-1">{{ $avgScore }}/100</p>
        </div>

    </div>

    {{-- ═══ Visitors + Status row ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

        {{-- Unique visitors --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Unique Visitors (by IP)</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-3xl font-black text-white">{{ number_format($visitorsToday) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Today</p>
                </div>
                <div>
                    <p class="text-3xl font-black text-white">{{ number_format($visitorsWeek) }}</p>
                    <p class="text-xs text-gray-500 mt-1">7 days</p>
                </div>
                <div>
                    <p class="text-3xl font-black text-white">{{ number_format($visitorsMonth) }}</p>
                    <p class="text-xs text-gray-500 mt-1">30 days</p>
                </div>
            </div>
        </div>

        {{-- Scan status breakdown --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Scan Status (all time)</h2>
            <div class="space-y-3">
                @php
                    $statusTotal = max($completedAll + $failedAll + $pendingNow, 1);
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-green-400">Completed</span>
                        <span class="text-gray-400">{{ number_format($completedAll) }} ({{ round($completedAll / $statusTotal * 100) }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-white/5 rounded-full"><div class="h-2 bg-green-500 rounded-full" style="width: {{ $completedAll / $statusTotal * 100 }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-red-400">Failed</span>
                        <span class="text-gray-400">{{ number_format($failedAll) }} ({{ round($failedAll / $statusTotal * 100) }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-white/5 rounded-full"><div class="h-2 bg-red-500 rounded-full" style="width: {{ $failedAll / $statusTotal * 100 }}%"></div></div>
                </div>
                @if($pendingNow > 0)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-yellow-400">In progress</span>
                        <span class="text-gray-400">{{ $pendingNow }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ═══ Scans per day chart (SVG) ═══ --}}
    <div class="bg-white/3 border border-white/8 rounded-xl p-5 mb-10">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Scans per day (last 30 days)</h2>
        @php
            $maxVal  = max(max(array_values($chart)), 1);
            $days    = array_keys($chart);
            $vals    = array_values($chart);
            $barW    = 100 / count($chart);
        @endphp
        <div class="overflow-x-auto">
            <svg viewBox="0 0 900 200" class="w-full min-w-[600px]" preserveAspectRatio="none">
                @foreach($vals as $i => $val)
                @php
                    $x = $i * (900 / count($vals));
                    $w = 900 / count($vals) - 2;
                    $h = ($val / $maxVal) * 170;
                    $y = 190 - $h;
                @endphp
                <rect x="{{ $x + 1 }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="2" fill="#6366f1" opacity="0.7">
                    <title>{{ $days[$i] }}: {{ $val }} scans</title>
                </rect>
                @endforeach
                {{-- Baseline --}}
                <line x1="0" y1="190" x2="900" y2="190" stroke="#ffffff" stroke-opacity="0.1" stroke-width="1"/>
            </svg>
        </div>
        <div class="flex justify-between text-xs text-gray-600 mt-2">
            <span>{{ \Carbon\Carbon::parse($days[0])->format('d M') }}</span>
            <span>{{ \Carbon\Carbon::parse($days[count($days)-1])->format('d M') }}</span>
        </div>
    </div>

    {{-- ═══ Grade distribution + Top domains ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

        {{-- Grade distribution --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Grade Distribution</h2>
            @php
                $gradeColors = [
                    'A+' => 'bg-emerald-500', 'A' => 'bg-emerald-500', 'A-' => 'bg-emerald-500',
                    'B+' => 'bg-green-500', 'B' => 'bg-green-500', 'B-' => 'bg-green-500',
                    'C+' => 'bg-yellow-500', 'C' => 'bg-yellow-500', 'C-' => 'bg-yellow-500',
                    'D+' => 'bg-orange-500', 'D' => 'bg-orange-500', 'D-' => 'bg-orange-500',
                    'F' => 'bg-red-500',
                ];
                $gradeTotal = max(array_sum($gradeDistribution), 1);
                $orderedGrades = ['A+','A','A-','B+','B','B-','C+','C','C-','D+','D','D-','F'];
            @endphp
            <div class="space-y-2">
                @foreach($orderedGrades as $grade)
                @if(isset($gradeDistribution[$grade]))
                @php $pct = round($gradeDistribution[$grade] / $gradeTotal * 100); @endphp
                <div class="flex items-center gap-3">
                    <span class="w-8 text-sm font-bold text-white text-right">{{ $grade }}</span>
                    <div class="flex-1 h-5 bg-white/5 rounded-full overflow-hidden">
                        <div class="{{ $gradeColors[$grade] ?? 'bg-gray-500' }} h-full rounded-full" style="width: {{ max($pct, 1) }}%"></div>
                    </div>
                    <span class="w-16 text-xs text-gray-400 text-right">{{ $gradeDistribution[$grade] }} ({{ $pct }}%)</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- Top scanned domains --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Top Domains (30 days)</h2>
            <div class="space-y-2">
                @foreach($topDomains as $domain)
                <div class="flex items-center justify-between py-1.5 border-b border-white/5 last:border-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-sm text-white truncate">{{ $domain->host }}</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        @if($domain->best_score)
                        <span class="text-xs {{ $domain->best_score >= 75 ? 'text-green-400' : ($domain->best_score >= 50 ? 'text-yellow-400' : 'text-red-400') }}">{{ $domain->best_score }}/100</span>
                        @endif
                        <span class="text-xs text-gray-500 w-12 text-right">{{ $domain->total }}×</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ═══ Recent scans ═══ --}}
    <div class="bg-white/3 border border-white/8 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Recent Scans</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-3">Host</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Score</th>
                        <th class="px-5 py-3">Grade</th>
                        <th class="px-5 py-3">IP</th>
                        <th class="px-5 py-3">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($recentScans as $scan)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('scan.show', $scan) }}" class="text-indigo-400 hover:text-indigo-300 transition">{{ $scan->host }}</a>
                        </td>
                        <td class="px-5 py-3">
                            @if($scan->status === 'completed')
                                <span class="inline-flex items-center gap-1 text-green-400 text-xs"><span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span> Done</span>
                            @elseif($scan->status === 'failed')
                                <span class="inline-flex items-center gap-1 text-red-400 text-xs"><span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span> Failed</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-yellow-400 text-xs"><span class="w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></span> {{ ucfirst($scan->status) }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 {{ $scan->score !== null ? ($scan->score >= 75 ? 'text-green-400' : ($scan->score >= 50 ? 'text-yellow-400' : 'text-red-400')) : 'text-gray-600' }} font-bold">
                            {{ $scan->score ?? '—' }}
                        </td>
                        <td class="px-5 py-3 font-bold text-white">{{ $scan->grade ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs font-mono">{{ $scan->ip_address }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $scan->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
