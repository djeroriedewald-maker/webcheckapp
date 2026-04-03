@extends('layouts.app')

@section('title', 'Sign In — WebCheckApp')
@section('meta_description', 'Sign in to your WebCheckApp account to view your monitored sites and alerts.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">

    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold mb-2">Sign in</h1>
        <p class="text-gray-400">Access your monitoring dashboard.</p>
    </div>

    <div class="bg-white/3 border border-white/8 rounded-2xl p-8">

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-red-400">
            {{ $errors->first() }}
        </div>
        @endif

        @if(session('status'))
        <div class="bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-green-400">
            {{ session('status') }}
        </div>
        @endif

        <a href="{{ route('auth.google') }}"
           class="w-full flex items-center justify-center gap-3 bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold py-3 rounded-xl transition mb-6">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Sign in with Google
        </a>

        <div class="flex items-center gap-4 mb-6">
            <div class="flex-1 border-t border-white/10"></div>
            <span class="text-xs text-gray-500 uppercase">or</span>
            <div class="flex-1 border-t border-white/10"></div>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="you@example.com">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="password">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="Your password">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500">
                    Remember me
                </label>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 rounded-xl transition">
                Sign in
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            No account yet?
            <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300">Create one free</a>
        </p>

    </div>
</div>
@endsection
