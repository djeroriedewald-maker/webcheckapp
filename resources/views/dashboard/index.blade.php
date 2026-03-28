@extends('layouts.app')

@section('title', 'Dashboard — WebCheckApp')
@section('meta_description', 'Monitor your websites and track security scores over time.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-2xl font-bold">My monitored sites</h1>
            <p class="text-gray-400 text-sm mt-1">Logged in as {{ auth()->user()->email }}</p>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-300 transition">Sign out</button>
        </form>
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
    <div class="bg-white/3 border border-white/8 rounded-2xl p-6 mb-8">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Add a site to monitor</h2>
        <form action="{{ route('dashboard.addSite') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="domain" placeholder="example.com" value="{{ old('domain') }}"
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition whitespace-nowrap">
                Add site
            </button>
        </form>
        <p class="text-xs text-gray-600 mt-2">Up to 10 sites. An initial scan runs immediately after adding.</p>
    </div>

    {{-- Site list --}}
    @if($sites->isEmpty())
    <div class="text-center py-16 text-gray-600">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <p>No sites added yet. Add your first site above.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($sites as $site)
        <div class="bg-white/3 border border-white/8 rounded-2xl p-5" x-data="{ open: false }">
            <div class="flex items-center justify-between gap-4">

                {{-- Domain + score --}}
                <div class="flex items-center gap-4 min-w-0">
                    @if($site->last_score !== null)
                    <div class="flex-shrink-0 w-14 h-14 rounded-xl flex flex-col items-center justify-center {{ $site->last_score >= 80 ? 'bg-green-500/15' : ($site->last_score >= 60 ? 'bg-amber-500/15' : 'bg-red-500/15') }}">
                        <span class="text-xl font-black {{ $site->last_score >= 80 ? 'text-green-400' : ($site->last_score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                            {{ $site->last_grade }}
                        </span>
                        <span class="text-xs {{ $site->last_score >= 80 ? 'text-green-500' : ($site->last_score >= 60 ? 'text-amber-500' : 'text-red-500') }}">
                            {{ $site->last_score }}
                        </span>
                    </div>
                    @else
                    <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-white/5 flex items-center justify-center">
                        <span class="text-gray-600 text-xs">–</span>
                    </div>
                    @endif

                    <div class="min-w-0">
                        <p class="font-semibold text-white truncate">{{ $site->domain }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($site->last_checked_at)
                                Last checked {{ $site->last_checked_at->diffForHumans() }}
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
                        View report
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
                        Settings
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
                        Alert me when certificate expires within 30 days
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

</div>
@endsection
