@extends('layouts.app')

@section('title', 'Security Blog — WebCheckApp')
@section('meta_description', 'Learn about website security, OWASP Top 10, SSL certificates, security headers, and more. Practical guides for developers and site owners.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="text-center mb-12">
        <h1 class="text-3xl font-black text-white mb-3">Security Blog</h1>
        <p class="text-gray-400 max-w-lg mx-auto">Practical guides on website security, OWASP, SSL, headers, and more. Written for developers and site owners.</p>
    </div>

    <div class="space-y-6">
        @foreach($articles as $article)
        <a href="{{ route('blog.show', $article['slug']) }}" class="block bg-white/3 border border-white/8 rounded-2xl p-6 hover:bg-white/5 hover:border-white/15 transition-all">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full">{{ $article['category'] }}</span>
                    <h2 class="text-lg font-bold text-white mt-2">{{ $article['title'] }}</h2>
                    <p class="text-sm text-gray-400 mt-2">{{ $article['description'] }}</p>
                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-600">
                        <span>{{ \Carbon\Carbon::parse($article['date'])->format('d M Y') }}</span>
                        <span>{{ $article['read_time'] }} min read</span>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-600 shrink-0 mt-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
        @endforeach
    </div>

    <div class="text-center mt-12">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Scan your website →
        </a>
    </div>

</div>
@endsection
