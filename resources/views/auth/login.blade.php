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
