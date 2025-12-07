<div class="space-y-4">
    @forelse(array_reverse($logs ?? []) as $log)
        <div class="rounded-lg border border-gray-200 p-3">
            <div class="flex items-center justify-between text-sm">
                <div class="font-semibold">{{ ucfirst($log['action'] ?? 'log') }}</div>
                <div class="text-xs uppercase tracking-wide {{ ($log['status'] ?? '') === 'success' ? 'text-green-600' : 'text-red-600' }}">
                    {{ strtoupper($log['status'] ?? 'unknown') }}
                </div>
            </div>
            <div class="mt-1 text-xs text-gray-500">{{ $log['timestamp'] ?? '-' }}</div>

            @if(! empty($log['error']))
                <div class="mt-2 rounded bg-red-50 p-2 text-sm text-red-700">
                    {{ $log['error'] }}
                </div>
            @endif

            @if(! empty($log['response']))
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer text-primary-600">Lihat respons</summary>
                    <pre class="mt-1 overflow-x-auto rounded bg-gray-100 p-2 text-xs text-gray-800">{{ json_encode($log['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            @endif

            @if(! empty($log['request']))
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer text-primary-600">Lihat request</summary>
                    <pre class="mt-1 overflow-x-auto rounded bg-gray-100 p-2 text-xs text-gray-800">{{ json_encode($log['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-600">Belum ada log permintaan.</p>
    @endforelse
</div>
