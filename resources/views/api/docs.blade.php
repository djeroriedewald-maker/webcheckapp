@extends('layouts.app')

@section('title', 'API Documentation — WebCheckApp')
@section('meta_description', 'Free JSON API to scan any website for security issues. Get SSL, headers, DNS, malware, port and exposed files results in one call.')
@section('robots', 'index, follow')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="mb-12">
        <div class="inline-flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full px-4 py-1.5 text-sm text-indigo-300 mb-6">
            <span class="w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></span>
            Free &amp; open API
        </div>
        <h1 class="text-4xl font-bold mb-4">API Documentation</h1>
        <p class="text-lg text-gray-400">Integrate WebCheckApp security scans into your own tools, dashboards, or CI/CD pipelines.</p>
    </div>

    {{-- Endpoint --}}
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4">Scan endpoint</h2>
        <div class="bg-gray-900 border border-white/10 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-3 border-b border-white/5">
                <span class="text-xs font-bold text-green-400 bg-green-500/10 px-2 py-0.5 rounded">GET</span>
                <code class="text-sm text-gray-200">/api/v1/scan?url=example.com</code>
            </div>
            <div class="px-5 py-4 text-sm text-gray-400 space-y-2">
                <p>Runs a full security scan and returns JSON results. Results are cached for <strong class="text-white">1 hour</strong> — repeat calls within that window return instantly.</p>
                <p>Rate limit: <strong class="text-white">5 requests per minute</strong> per IP.</p>
            </div>
        </div>
    </section>

    {{-- Parameters --}}
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4">Parameters</h2>
        <div class="border border-white/10 rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-white/3 text-gray-400 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Parameter</th>
                        <th class="px-5 py-3 font-medium">Type</th>
                        <th class="px-5 py-3 font-medium">Required</th>
                        <th class="px-5 py-3 font-medium">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr>
                        <td class="px-5 py-3"><code class="text-indigo-300">url</code></td>
                        <td class="px-5 py-3 text-gray-400">string</td>
                        <td class="px-5 py-3 text-green-400">Yes</td>
                        <td class="px-5 py-3 text-gray-400">The domain or URL to scan. e.g. <code>example.com</code> or <code>https://example.com</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Example request --}}
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4">Example request</h2>
        <div x-data="{ copied: false }" class="relative">
            <pre class="bg-gray-900 border border-white/10 rounded-2xl p-5 text-sm text-gray-300 overflow-x-auto"><code>curl "{{ url('/api/v1/scan') }}?url=example.com"</code></pre>
            <button @click="navigator.clipboard.writeText(`curl &quot;{{ url('/api/v1/scan') }}?url=example.com&quot;`); copied = true; setTimeout(() => copied = false, 2000)"
                    class="absolute top-4 right-4 text-xs text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 px-2.5 py-1 rounded-lg transition">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" class="text-green-400">Copied!</span>
            </button>
        </div>
    </section>

    {{-- Example response --}}
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4">Example response</h2>
        <div class="bg-gray-900 border border-white/10 rounded-2xl p-5 text-sm text-gray-300 overflow-x-auto">
<pre><code>{
  "url": "https://example.com",
  "host": "example.com",
  "score": 74,
  "grade": "B-",
  "scanned_at": "2025-03-27T14:22:01+00:00",
  "cached": false,
  "report_url": "{{ url('/scan') }}/abc123",
  "categories": {
    "ssl": {
      "category": "SSL &amp; HTTPS",
      "score": 95,
      "checks": [
        {
          "id": "ssl_available",
          "label": "HTTPS / SSL enabled",
          "status": "pass",
          "description": "The website is accessible over HTTPS.",
          "recommendation": null
        },
        ...
      ]
    },
    "headers": { ... },
    "dns": { ... },
    "performance": { ... },
    "content": { ... },
    "technology": { ... },
    "malware": { ... },
    "exposed_files": { ... },
    "ports": { ... },
    "privacy": { ... },
    "trust": { ... }
  }
}</code></pre>
        </div>
    </section>

    {{-- Status codes --}}
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4">Response status codes</h2>
        <div class="border border-white/10 rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-white/3 text-gray-400 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Code</th>
                        <th class="px-5 py-3 font-medium">Meaning</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr><td class="px-5 py-3 text-green-400">200</td><td class="px-5 py-3 text-gray-400">Scan completed successfully</td></tr>
                    <tr><td class="px-5 py-3 text-amber-400">422</td><td class="px-5 py-3 text-gray-400">Invalid or missing URL parameter</td></tr>
                    <tr><td class="px-5 py-3 text-red-400">429</td><td class="px-5 py-3 text-gray-400">Rate limit exceeded (5 req/min)</td></tr>
                    <tr><td class="px-5 py-3 text-red-400">500</td><td class="px-5 py-3 text-gray-400">Host unreachable or scan failed</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- CTA --}}
    <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-8 text-center">
        <h2 class="text-xl font-bold mb-2">Ready to try it?</h2>
        <p class="text-gray-400 mb-5">The API is free with no authentication required. Just start making requests.</p>
        <a href="{{ url('/api/v1/scan') }}?url=example.com"
           target="_blank"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-6 py-3 rounded-xl transition">
            Try a live request
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>
    </div>

</div>
@endsection
