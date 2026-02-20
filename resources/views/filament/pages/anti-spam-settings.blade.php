<x-filament-panels::page>
    @php
        $status = $this->getAntiSpamStatus();
        $canSend = $status['can_send'];
        $cbActive = $status['circuit_breaker_active'];
        $hourlyPct = $status['hourly_limit'] > 0 ? round($status['hourly_count'] / $status['hourly_limit'] * 100) : 0;
        $dailyPct  = $status['daily_limit']  > 0 ? round($status['daily_count']  / $status['daily_limit']  * 100) : 0;
    @endphp

    {{-- Status Panel --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Status Anti-Spam Saat Ini</h2>
            <span @class([
                'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium',
                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $canSend,
                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'         => ! $canSend,
            ])>
                <span @class([
                    'inline-block h-2 w-2 rounded-full',
                    'bg-green-500' => $canSend,
                    'bg-red-500'   => ! $canSend,
                ])></span>
                {{ $canSend ? 'Siap Kirim' : 'Diblokir' }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {{-- Pesan sesi ini --}}
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Terkirim (Sesi)</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $status['messages_sent_session'] }}</p>
            </div>

            {{-- Kegagalan berturut-turut --}}
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Kegagalan Berturut</p>
                <p @class([
                    'mt-1 text-2xl font-bold',
                    'text-gray-900 dark:text-white' => $status['consecutive_failures'] === 0,
                    'text-yellow-600 dark:text-yellow-400' => $status['consecutive_failures'] > 0 && ! $cbActive,
                    'text-red-600 dark:text-red-400' => $cbActive,
                ])>{{ $status['consecutive_failures'] }}</p>
            </div>

            {{-- Jam ini --}}
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Pesan Jam Ini</p>
                <p @class([
                    'mt-1 text-2xl font-bold',
                    'text-gray-900 dark:text-white' => $hourlyPct < 80,
                    'text-yellow-600 dark:text-yellow-400' => $hourlyPct >= 80 && $hourlyPct < 100,
                    'text-red-600 dark:text-red-400' => $hourlyPct >= 100,
                ])>{{ $status['hourly_count'] }} <span class="text-sm font-normal text-gray-500">/ {{ $status['hourly_limit'] }}</span></p>
            </div>

            {{-- Hari ini --}}
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Pesan Hari Ini</p>
                <p @class([
                    'mt-1 text-2xl font-bold',
                    'text-gray-900 dark:text-white' => $dailyPct < 80,
                    'text-yellow-600 dark:text-yellow-400' => $dailyPct >= 80 && $dailyPct < 100,
                    'text-red-600 dark:text-red-400' => $dailyPct >= 100,
                ])>{{ $status['daily_count'] }} <span class="text-sm font-normal text-gray-500">/ {{ $status['daily_limit'] }}</span></p>
            </div>
        </div>

        @if ($cbActive)
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-400">
                <strong>Circuit Breaker Aktif</strong> — Pengiriman diblokir hingga:
                <strong>{{ $status['circuit_breaker_until'] }}</strong>
            </div>
        @endif
    </div>

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap gap-2">
                <x-filament::button
                    type="button"
                    color="danger"
                    wire:click="resetCircuitBreaker"
                    wire:confirm="Reset circuit breaker? Ini akan menghapus status kegagalan saat ini."
                >
                    Reset Circuit Breaker
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="warning"
                    wire:click="resetRateLimitCounters"
                    wire:confirm="Reset counter rate limit jam ini dan hari ini ke nol?"
                >
                    Reset Counter Rate Limit
                </x-filament::button>
            </div>

            <x-filament::button type="submit" color="primary">
                Simpan Pengaturan Anti-Spam
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
