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

    {{-- ═══ Revenue cards ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-10">
        <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
            <p class="text-xs text-emerald-400 uppercase tracking-wider">Revenue total</p>
            <p class="text-2xl font-black text-emerald-400 mt-1">&euro;{{ number_format($totalRevenue / 100, 2, ',', '.') }}</p>
        </div>
        <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
            <p class="text-xs text-emerald-400 uppercase tracking-wider">Revenue month</p>
            <p class="text-2xl font-black text-emerald-400 mt-1">&euro;{{ number_format($revenueMonth / 100, 2, ',', '.') }}</p>
        </div>
        <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
            <p class="text-xs text-emerald-400 uppercase tracking-wider">Revenue week</p>
            <p class="text-2xl font-black text-emerald-400 mt-1">&euro;{{ number_format($revenueWeek / 100, 2, ',', '.') }}</p>
        </div>
        <div class="bg-purple-500/5 border border-purple-500/20 rounded-xl p-4">
            <p class="text-xs text-purple-400 uppercase tracking-wider">Payments total</p>
            <p class="text-2xl font-black text-purple-400 mt-1">{{ $totalPayments }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $paymentsMonth }} this month</p>
        </div>
        <div class="bg-purple-500/5 border border-purple-500/20 rounded-xl p-4">
            <p class="text-xs text-purple-400 uppercase tracking-wider">Pro scans</p>
            <p class="text-2xl font-black text-purple-400 mt-1">{{ $proScansMonth }}</p>
            <p class="text-xs text-gray-500 mt-0.5">this month</p>
        </div>
        <div class="bg-pink-500/5 border border-pink-500/20 rounded-xl p-4">
            <p class="text-xs text-pink-400 uppercase tracking-wider">Deep scans</p>
            <p class="text-2xl font-black text-pink-400 mt-1">{{ $deepScansMonth }}</p>
            <p class="text-xs text-gray-500 mt-0.5">this month</p>
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

    {{-- ═══ Top 3 Best & Worst ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10" x-data="{ period: 'week' }">

        {{-- Period selector --}}
        <div class="lg:col-span-2 flex items-center gap-2">
            <span class="text-xs text-gray-500 uppercase tracking-wider mr-2">Period:</span>
            @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
            <button @click="period = '{{ $key }}'"
                    :class="period === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-white/5 text-gray-400 hover:text-white'"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg transition">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Top 3 Best --}}
        <div class="bg-green-500/5 border border-green-500/15 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-green-400 uppercase tracking-wider mb-4">Top 3 Best Scores</h2>
            @foreach(['today', 'week', 'month', 'year'] as $p)
            <div x-show="period === '{{ $p }}'" class="space-y-3">
                @forelse($topBest[$p] as $i => $scan)
                <div class="flex items-center gap-3">
                    <span class="text-lg font-black {{ $i === 0 ? 'text-amber-400' : 'text-gray-600' }} w-6">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('scan.show', $scan) }}" class="text-sm text-white hover:text-indigo-400 transition truncate block">{{ $scan->host }}</a>
                        <span class="text-xs text-gray-600">{{ $scan->completed_at?->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-bold text-white bg-white/5 px-2 py-0.5 rounded-full">{{ $scan->grade }}</span>
                        <span class="text-lg font-black text-green-400">{{ $scan->score }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-600">No scans in this period.</p>
                @endforelse
            </div>
            @endforeach
        </div>

        {{-- Top 3 Worst --}}
        <div class="bg-red-500/5 border border-red-500/15 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-red-400 uppercase tracking-wider mb-4">Top 3 Worst Scores</h2>
            @foreach(['today', 'week', 'month', 'year'] as $p)
            <div x-show="period === '{{ $p }}'" class="space-y-3">
                @forelse($topWorst[$p] as $i => $scan)
                <div class="flex items-center gap-3">
                    <span class="text-lg font-black {{ $i === 0 ? 'text-red-400' : 'text-gray-600' }} w-6">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('scan.show', $scan) }}" class="text-sm text-white hover:text-indigo-400 transition truncate block">{{ $scan->host }}</a>
                        <span class="text-xs text-gray-600">{{ $scan->completed_at?->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-bold text-white bg-white/5 px-2 py-0.5 rounded-full">{{ $scan->grade }}</span>
                        <span class="text-lg font-black text-red-400">{{ $scan->score }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-600">No scans in this period.</p>
                @endforelse
            </div>
            @endforeach
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
                        <th class="px-5 py-3">Tier</th>
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
                            @if($scan->tier === 'deep')
                            <span class="text-[10px] font-bold text-pink-400 bg-pink-500/10 px-1.5 py-0.5 rounded-full">DEEP</span>
                            @elseif($scan->tier === 'pro')
                            <span class="text-[10px] font-bold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full">PRO</span>
                            @else
                            <span class="text-[10px] font-bold text-gray-500 bg-white/5 px-1.5 py-0.5 rounded-full">FREE</span>
                            @endif
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

    {{-- ═══ Manage user tiers ═══ --}}
    <div class="bg-purple-500/5 border border-purple-500/20 rounded-xl p-5 mb-10 mt-10">
        <h2 class="text-sm font-semibold text-purple-400 uppercase tracking-wider mb-2">Manage User Tiers</h2>
        <p class="text-xs text-gray-500 mb-4">Grant unlimited Pro or Deep scan access to a user. They can scan any domain without paying. Set to "Revoke" to remove access.</p>

        @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 mb-4 text-sm text-green-400">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-4 text-sm text-red-400">
            {{ session('error') }}
        </div>
        @endif

        <form action="{{ route('admin.updateTier') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <select name="user_id" required style="background:#111827;color:#fff" class="border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition min-w-[280px]">
                <option value="">Select user...</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" style="background:#111827;color:#fff">
                    {{ $user->email }} ({{ $user->name }})
                    @if($user->granted_tier) — current: {{ strtoupper($user->granted_tier) }} @endif
                </option>
                @endforeach
            </select>
            <select name="tier" required style="background:#111827;color:#fff" class="border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                <option value="pro" style="background:#111827;color:#fff">Unlimited Pro</option>
                <option value="deep" style="background:#111827;color:#fff">Unlimited Deep</option>
                <option value="none" style="background:#111827;color:#ef4444">Revoke (back to free)</option>
            </select>
            <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition whitespace-nowrap">
                Update tier
            </button>
        </form>
    </div>

    {{-- ═══ Recent payments ═══ --}}
    <div class="bg-white/3 border border-white/8 rounded-xl overflow-hidden mb-10">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Recent Payments</h2>
        </div>
        @if($recentPayments->isEmpty())
        <div class="px-5 py-8 text-center text-gray-500 text-sm">No payments yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Domain</th>
                        <th class="px-5 py-3">Tier</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($recentPayments as $payment)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-5 py-3 text-white">{{ $payment->user->email ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if($payment->scan)
                            <a href="{{ route('scan.show', $payment->scan) }}" class="text-indigo-400 hover:text-indigo-300 transition">{{ $payment->domain }}</a>
                            @else
                            <span class="text-gray-400">{{ $payment->domain }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $payment->tier === 'deep' ? 'bg-pink-500/10 text-pink-400' : 'bg-purple-500/10 text-purple-400' }}">
                                {{ ucfirst($payment->tier) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-emerald-400 font-bold">&euro;{{ number_format($payment->amount_cents / 100, 2, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            @if($payment->status === 'completed')
                            <span class="inline-flex items-center gap-1 text-green-400 text-xs"><span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span> Paid</span>
                            @elseif($payment->status === 'failed')
                            <span class="inline-flex items-center gap-1 text-red-400 text-xs"><span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span> Failed</span>
                            @else
                            <span class="inline-flex items-center gap-1 text-yellow-400 text-xs"><span class="w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></span> Pending</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $payment->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ═══ Users ═══ --}}
    <div class="bg-white/3 border border-white/8 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Users ({{ $totalUsers }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Auth</th>
                        <th class="px-5 py-3">Tier</th>
                        <th class="px-5 py-3">Scans</th>
                        <th class="px-5 py-3">Payments</th>
                        <th class="px-5 py-3">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($users as $user)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-5 py-3 text-white">
                            {{ $user->name }}
                            @if($user->is_admin)
                            <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-1.5 py-0.5 rounded-full ml-1">ADMIN</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-400">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            @if($user->google_id)
                            <span class="text-xs text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full">Google</span>
                            @endif
                            @if($user->password)
                            <span class="text-xs text-gray-400 bg-white/5 px-2 py-0.5 rounded-full">Email</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($user->granted_tier === 'deep')
                            <span class="text-xs font-bold text-pink-400 bg-pink-500/10 px-2 py-0.5 rounded-full">DEEP</span>
                            @elseif($user->granted_tier === 'pro')
                            <span class="text-xs font-bold text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-full">PRO</span>
                            @else
                            <span class="text-xs text-gray-600">Free</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-white font-bold">{{ $user->scans_count }}</td>
                        <td class="px-5 py-3 text-white font-bold">{{ $user->payments_count }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
