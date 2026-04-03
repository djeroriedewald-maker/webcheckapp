@extends('layouts.app')

@section('title', 'Recently Scanned Websites — WebCheckApp')
@section('meta_description', 'Browse the latest website security scans. See how websites score on SSL, headers, DNS, malware, and more security checks.')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="text-center mb-12">
        <h1 class="text-3xl font-black text-white mb-3">Recently Scanned Websites</h1>
        <p class="text-gray-400 max-w-lg mx-auto">Browse the latest security scans performed on WebCheckApp. Click any result to view the full report.</p>
    </div>

    <div class="bg-white/3 border border-white/8 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-4">Website</th>
                        <th class="px-5 py-4">Score</th>
                        <th class="px-5 py-4">Grade</th>
                        <th class="px-5 py-4">Tier</th>
                        <th class="px-5 py-4">Scanned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($scans as $scan)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('scan.show', $scan) }}" class="text-indigo-400 hover:text-indigo-300 transition font-medium">{{ $scan->host }}</a>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-bold {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-yellow-400' : 'text-red-400') }}">
                                {{ $scan->score }}/100
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-black text-lg {{ ($scan->score ?? 0) >= 80 ? 'text-green-400' : (($scan->score ?? 0) >= 60 ? 'text-yellow-400' : 'text-red-400') }}">
                                {{ $scan->grade }}
                            </span>
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
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $scan->completed_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Scan your website →
        </a>
    </div>

</div>
@endsection
