@extends('layouts.app')

@section('title', 'Search: ' . $q . ' — Admin')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <a href="{{ route('admin') }}" class="text-sm text-gray-500 hover:text-gray-300 transition mb-6 inline-block">&larr; Back to admin</a>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-white">Search results for "{{ $q }}"</h1>
        <form action="{{ route('admin.search') }}" method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ $q }}" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition w-48">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-3 py-2 rounded-lg transition">Search</button>
        </form>
    </div>

    {{-- Users --}}
    @if($users->isNotEmpty())
    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Users ({{ $users->count() }})</h2>
        </div>
        <div class="divide-y divide-white/5">
            @foreach($users as $user)
            <div class="flex items-center justify-between px-5 py-3 hover:bg-white/2 transition">
                <div class="flex items-center gap-3 min-w-0">
                    <div>
                        <a href="{{ route('admin.user', $user) }}" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium transition">{{ $user->email }}</a>
                        <p class="text-xs text-gray-600">{{ $user->name }} · {{ $user->scans_count }} scans · {{ $user->payments_count }} payments</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($user->granted_tier)
                    <span class="text-[10px] font-bold {{ $user->granted_tier === 'deep' ? 'text-pink-400 bg-pink-500/10' : 'text-purple-400 bg-purple-500/10' }} px-1.5 py-0.5 rounded-full">{{ strtoupper($user->granted_tier) }}</span>
                    @endif
                    @if($user->is_admin)
                    <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-1.5 py-0.5 rounded-full">ADMIN</span>
                    @endif
                    <a href="{{ route('admin.user', $user) }}" class="text-[10px] text-indigo-400 bg-indigo-500/10 px-2 py-1 rounded hover:bg-indigo-500/20 transition">View</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Scans --}}
    @if($scans->isNotEmpty())
    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Scans ({{ $scans->count() }})</h2>
        </div>
        <div class="divide-y divide-white/5">
            @foreach($scans as $scan)
            <div class="flex items-center justify-between px-5 py-3 hover:bg-white/2 transition">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="font-bold w-8 text-center {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-yellow-400' : 'text-red-400') }}">{{ $scan->score ?? '—' }}</span>
                    <div>
                        <a href="{{ route('scan.show', $scan) }}" class="text-sm text-indigo-400 hover:text-indigo-300 transition">{{ $scan->host }}</a>
                        <p class="text-xs text-gray-600">{{ $scan->tier }} · {{ $scan->status }} · {{ $scan->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <form action="{{ route('admin.deleteScan', $scan) }}" method="POST" onsubmit="return confirm('Delete this scan?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-[10px] text-red-400 bg-red-500/10 px-2 py-1 rounded hover:bg-red-500/20 transition">Delete</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($users->isEmpty() && $scans->isEmpty())
    <div class="text-center py-16 text-gray-600">
        <p class="text-sm">No results found for "{{ $q }}".</p>
    </div>
    @endif

</div>
@endsection
