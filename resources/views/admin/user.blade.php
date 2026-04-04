@extends('layouts.app')

@section('title', 'User: ' . $user->email . ' — Admin')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <a href="{{ route('admin') }}" class="text-sm text-gray-500 hover:text-gray-300 transition mb-6 inline-block">&larr; Back to admin</a>

    {{-- User header --}}
    <div class="bg-white/3 border border-white/8 rounded-2xl p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $user->name ?? 'No name' }}</h1>
                <p class="text-gray-400 mt-1">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-3 flex-wrap">
                    @if($user->is_admin)
                    <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">ADMIN</span>
                    @endif
                    @if($user->granted_tier)
                    <span class="text-[10px] font-bold {{ $user->granted_tier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-2 py-0.5 rounded-full">{{ strtoupper($user->granted_tier) }}</span>
                    @endif
                    @if($user->google_id)
                    <span class="text-[10px] text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full">Google</span>
                    @endif
                    @if($user->password)
                    <span class="text-[10px] text-gray-400 bg-white/5 px-2 py-0.5 rounded-full">Email/Password</span>
                    @endif
                </div>
                <p class="text-xs text-gray-600 mt-2">Joined {{ $user->created_at->format('d M Y, H:i') }} ({{ $user->created_at->diffForHumans() }})</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                {{-- Toggle admin --}}
                <form action="{{ route('admin.toggleAdmin', $user) }}" method="POST" onsubmit="return confirm('{{ $user->is_admin ? 'Remove admin status?' : 'Make this user admin?' }}')">
                    @csrf
                    <button type="submit" class="text-xs {{ $user->is_admin ? 'text-amber-400 bg-amber-500/10 hover:bg-amber-500/20' : 'text-gray-400 bg-white/5 hover:bg-white/10' }} px-3 py-1.5 rounded-lg transition">
                        {{ $user->is_admin ? 'Remove admin' : 'Make admin' }}
                    </button>
                </form>
                {{-- Delete user --}}
                @if(!$user->is_admin)
                <form action="{{ route('admin.deleteUser', $user) }}" method="POST" onsubmit="return confirm('Delete {{ $user->email }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 bg-red-500/10 hover:bg-red-500/20 px-3 py-1.5 rounded-lg transition">Delete user</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-green-400">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-red-400">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white/3 border border-white/8 rounded-xl p-4 text-center">
            <p class="text-2xl font-black text-white">{{ $scans->count() }}</p>
            <p class="text-xs text-gray-600 mt-1">Scans</p>
        </div>
        <div class="bg-white/3 border border-white/8 rounded-xl p-4 text-center">
            <p class="text-2xl font-black text-white">{{ $payments->count() }}</p>
            <p class="text-xs text-gray-600 mt-1">Payments</p>
        </div>
        <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-center">
            <p class="text-2xl font-black text-emerald-400">&euro;{{ number_format($payments->where('status', 'completed')->sum('amount_cents') / 100, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-600 mt-1">Revenue</p>
        </div>
    </div>

    {{-- Payments --}}
    @if($payments->isNotEmpty())
    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Payments</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-3">Domain</th>
                        <th class="px-5 py-3">Tier</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-5 py-3 text-white">{{ $payment->domain }}</td>
                        <td class="px-5 py-3">
                            <span class="text-[10px] font-bold {{ $payment->tier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-1.5 py-0.5 rounded-full">{{ strtoupper($payment->tier) }}</span>
                        </td>
                        <td class="px-5 py-3 text-emerald-400 font-bold">&euro;{{ number_format($payment->amount_cents / 100, 2, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            @if($payment->status === 'completed')
                            <span class="text-xs text-green-400">Paid</span>
                            @else
                            <span class="text-xs text-yellow-400">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $payment->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Scans --}}
    <form action="{{ route('admin.bulkDeleteScans') }}" method="POST" onsubmit="return confirm('Delete selected scans? This cannot be undone.')"
          x-data="{ selected: [], get allIds() { return [{{ $scans->pluck('id')->implode(',') }}] }, get allSelected() { return this.selected.length === this.allIds.length && this.allIds.length > 0 } }">
    @csrf
    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Scans ({{ $scans->count() }})</h2>
            <button type="submit" x-show="selected.length > 0" x-cloak
                    class="text-xs text-red-400 bg-red-500/10 hover:bg-red-500/20 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Delete selected (<span x-text="selected.length"></span>)
            </button>
        </div>
        @if($scans->isEmpty())
        <div class="px-5 py-8 text-center text-gray-600 text-sm">No scans yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" @click="selected = allSelected ? [] : [...allIds]" :checked="allSelected"
                                   class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        </th>
                        <th class="px-3 py-3">Host</th>
                        <th class="px-3 py-3">Tier</th>
                        <th class="px-3 py-3">Score</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($scans as $scan)
                    <tr class="hover:bg-white/2 transition" :class="selected.includes({{ $scan->id }}) && 'bg-red-500/5'">
                        <td class="px-3 py-3">
                            <input type="checkbox" name="scan_ids[]" value="{{ $scan->id }}"
                                   x-model.number="selected"
                                   class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('scan.show', $scan) }}" class="text-indigo-400 hover:text-indigo-300 transition">{{ $scan->host }}</a>
                        </td>
                        <td class="px-3 py-3">
                            @if($scan->tier === 'deep')
                            <span class="text-[10px] font-bold text-pink-400 bg-pink-500/10 px-1.5 py-0.5 rounded-full">DEEP</span>
                            @elseif($scan->tier === 'pro')
                            <span class="text-[10px] font-bold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full">PRO</span>
                            @else
                            <span class="text-[10px] text-gray-500 bg-white/5 px-1.5 py-0.5 rounded-full">FREE</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 font-bold {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-yellow-400' : 'text-red-400') }}">
                            {{ $scan->score ?? '—' }}
                        </td>
                        <td class="px-3 py-3">
                            @if($scan->status === 'completed')
                            <span class="text-xs text-green-400">Done</span>
                            @elseif($scan->status === 'failed')
                            <span class="text-xs text-red-400">Failed</span>
                            @else
                            <span class="text-xs text-yellow-400">{{ ucfirst($scan->status) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-gray-500 text-xs">{{ $scan->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    </form>

</div>
@endsection
