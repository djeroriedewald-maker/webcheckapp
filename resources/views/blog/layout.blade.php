@extends('layouts.app')

@section('title', $article['title'] . ' — WebCheckApp Blog')
@section('meta_description', $article['description'])

@section('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $article['title'] }}",
    "description": "{{ $article['description'] }}",
    "datePublished": "{{ $article['date'] }}",
    "author": { "@@type": "Organization", "name": "WebCheckApp" },
    "publisher": { "@@type": "Organization", "name": "WebCheckApp", "url": "{{ url('/') }}" }
}
</script>
@endsection

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <a href="{{ route('blog') }}" class="text-sm text-gray-500 hover:text-gray-300 transition mb-6 inline-block">&larr; Back to blog</a>

    <header class="mb-10">
        <span class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full">{{ $article['category'] }}</span>
        <h1 class="text-3xl sm:text-4xl font-black text-white mt-3 leading-tight">{{ $article['title'] }}</h1>
        <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
            <span>{{ \Carbon\Carbon::parse($article['date'])->format('d M Y') }}</span>
            <span>{{ $article['read_time'] }} min read</span>
        </div>
    </header>

    <div class="prose prose-invert prose-sm max-w-none
                prose-headings:text-white prose-headings:font-bold
                prose-p:text-gray-300 prose-p:leading-relaxed
                prose-a:text-indigo-400 prose-a:no-underline hover:prose-a:text-indigo-300
                prose-strong:text-white
                prose-code:text-indigo-300 prose-code:bg-indigo-500/10 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-xs
                prose-pre:bg-gray-900/80 prose-pre:border prose-pre:border-white/10 prose-pre:rounded-xl
                prose-li:text-gray-300
                prose-blockquote:border-indigo-500/30 prose-blockquote:text-gray-400
                space-y-6">
        @yield('article_content')
    </div>

    {{-- Scan CTA --}}
    <div class="mt-12 bg-gradient-to-br from-indigo-600/15 to-purple-600/10 border border-indigo-500/20 rounded-2xl p-8 text-center">
        <h2 class="text-xl font-bold text-white mb-2">Check your website now</h2>
        <p class="text-sm text-gray-400 mb-5">Run a free security scan to see how your website scores on the topics covered in this article.</p>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Free security scan →
        </a>
    </div>

    {{-- Related articles --}}
    @if($related->isNotEmpty())
    <div class="mt-12">
        <h3 class="text-lg font-bold text-white mb-4">Related articles</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($related as $rel)
            <a href="{{ route('blog.show', $rel['slug']) }}" class="bg-white/3 border border-white/8 rounded-xl p-5 hover:bg-white/5 transition">
                <h4 class="text-sm font-semibold text-white mb-1">{{ $rel['title'] }}</h4>
                <p class="text-xs text-gray-500">{{ $rel['read_time'] }} min read</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</article>
@endsection
