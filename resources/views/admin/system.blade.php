@extends('layouts.app')

@section('title', 'System Status — Admin')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <a href="{{ route('admin') }}" class="text-sm text-gray-500 hover:text-gray-300 transition mb-6 inline-block">&larr; Back to admin</a>

    <h1 class="text-2xl font-bold text-white mb-8">System Status</h1>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white/3 border border-white/8 rounded-xl p-5 text-center">
            <p class="text-3xl font-black text-white">{{ number_format($scanCount) }}</p>
            <p class="text-xs text-gray-600 mt-1">Total scans</p>
        </div>
        <div class="bg-white/3 border border-white/8 rounded-xl p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $userCount }}</p>
            <p class="text-xs text-gray-600 mt-1">Users</p>
        </div>
        <div class="bg-white/3 border border-white/8 rounded-xl p-5 text-center">
            <p class="text-3xl font-black text-white">{{ $paymentCount }}</p>
            <p class="text-xs text-gray-600 mt-1">Payments</p>
        </div>
    </div>

    <div class="space-y-4">
        {{-- Queue --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Queue</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-2xl font-black {{ $pendingJobs > 0 ? 'text-yellow-400' : 'text-green-400' }}">{{ $pendingJobs }}</p>
                    <p class="text-xs text-gray-600">Pending jobs</p>
                </div>
                <div>
                    <p class="text-2xl font-black {{ $failedJobs > 0 ? 'text-red-400' : 'text-green-400' }}">{{ $failedJobs }}</p>
                    <p class="text-xs text-gray-600">Failed jobs</p>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Database</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Size</span>
                    <span class="text-white font-bold">{{ number_format($dbSize / 1024 / 1024, 1) }} MB</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Driver</span>
                    <span class="text-white">{{ config('database.default') }}</span>
                </div>
            </div>
        </div>

        {{-- Environment --}}
        <div class="bg-white/3 border border-white/8 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Environment</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">App env</span>
                    <span class="text-white">{{ app()->environment() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">PHP</span>
                    <span class="text-white">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Laravel</span>
                    <span class="text-white">{{ app()->version() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Queue driver</span>
                    <span class="text-white">{{ config('queue.default') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Stripe configured</span>
                    <span class="text-white">{{ config('services.stripe.secret') ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Google OAuth configured</span>
                    <span class="text-white">{{ config('services.google.client_id') ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
