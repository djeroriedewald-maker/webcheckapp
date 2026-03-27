<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WebCheckApp — Website Security Scanner')</title>
    <meta name="description" content="@yield('meta_description', 'Instantly scan your website for security issues, performance problems and get actionable recommendations.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-950 text-white antialiased" x-data>

    <nav class="border-b border-white/5 bg-gray-900/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-lg">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-white">WebCheck<span class="text-indigo-400">App</span></span>
                </a>
                <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-white transition-colors">
                    New scan
                </a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-white/5 mt-20 py-8 text-center text-sm text-gray-600">
        &copy; {{ date('Y') }} WebCheckApp &mdash; Free website security scanner
    </footer>

</body>
</html>
