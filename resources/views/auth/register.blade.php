@extends('layouts.app')

@section('title', 'Create Account — WebCheckApp')
@section('meta_description', 'Create a free account to monitor your websites and get alerts when security scores drop.')
@section('robots', 'noindex, follow')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">

    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold mb-2">Create account</h1>
        <p class="text-gray-400">Monitor your sites and get security alerts.</p>
    </div>

    <div class="bg-white/3 border border-white/8 rounded-2xl p-8">

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-6 text-sm text-red-400">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="Your name">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="you@example.com">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="password">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="Min. 8 characters">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5" for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                       placeholder="Repeat password">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 rounded-xl transition">
                Create account
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300">Sign in</a>
        </p>

    </div>
</div>
@endsection
