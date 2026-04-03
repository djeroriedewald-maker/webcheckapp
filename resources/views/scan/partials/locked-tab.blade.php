{{-- Locked tab overlay for free scans — shows blurred preview + upgrade CTA --}}
@props(['title', 'description', 'icon' => 'shield-exclamation', 'features' => []])

<div class="relative">
    {{-- Blurred fake preview to create curiosity --}}
    <div class="pointer-events-none select-none" aria-hidden="true">
        <div class="filter blur-[6px] opacity-40">
            <div class="bg-white/2 border border-white/8 rounded-2xl overflow-hidden mb-4">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                    <h3 class="font-semibold text-white">{{ $title }}</h3>
                    <span class="text-sm font-bold text-yellow-400">??/100</span>
                </div>
                <div class="divide-y divide-white/5">
                    @for($i = 0; $i < 4; $i++)
                    <div class="flex items-start gap-4 px-5 py-4">
                        <svg class="w-5 h-5 text-gray-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="h-4 bg-white/10 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-white/5 rounded w-full"></div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Upgrade CTA overlay --}}
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="bg-gray-900/90 border border-purple-500/20 backdrop-blur-sm rounded-2xl p-8 max-w-md text-center shadow-2xl shadow-purple-500/10">
            <div class="w-14 h-14 rounded-2xl bg-purple-500/15 border border-purple-500/25 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">{{ $title }}</h3>
            <p class="text-sm text-gray-400 mb-4">{{ $description }}</p>

            @if(!empty($features))
            <div class="flex flex-wrap justify-center gap-2 mb-5">
                @foreach($features as $feature)
                <span class="text-xs bg-purple-500/10 text-purple-300 border border-purple-500/20 px-2.5 py-1 rounded-full">{{ $feature }}</span>
                @endforeach
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-2 justify-center">
                <form action="{{ route('checkout.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="url" value="{{ $scan->url }}">
                    <input type="hidden" name="tier" value="pro">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition whitespace-nowrap">
                        Unlock with Pro &euro;9,99
                    </button>
                </form>
                <form action="{{ route('checkout.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="url" value="{{ $scan->url }}">
                    <input type="hidden" name="tier" value="deep">
                    <button type="submit" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition whitespace-nowrap">
                        Deep Scan &euro;29,99
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
