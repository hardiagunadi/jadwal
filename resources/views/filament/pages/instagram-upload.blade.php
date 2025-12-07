<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" color="primary">
                Kirim ke Instagram
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="generateCaption">
                Buat Narasi dengan Gemini
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-8" icon="heroicon-o-rectangle-group" icon-color="primary">
        <x-slot name="heading">Catatan & Batasan</x-slot>
        <div class="space-y-2 text-sm text-gray-700">
            <p>• Pastikan token akses long-lived memiliki izin <code>instagram_business_content_publish</code> dan belum kedaluwarsa (60 hari).</p>
            <p>• Konten diunggah ke feed (foto/video). Stories dan Reels belum didukung untuk penjadwalan.</p>
            <p>• Batas API: maksimal 100 posting per hari, gambar JPEG, video feed maksimal 10 menit, dan file harus dapat diakses publik saat membuat container.</p>
        </div>
    </x-filament::section>

    <x-filament::section class="mt-8" icon="heroicon-o-clock" icon-color="gray">
        <x-slot name="heading">Riwayat terbaru</x-slot>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Waktu</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Jenis</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Template</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Container</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentPosts as $post)
                        <tr>
                            <td class="px-3 py-2">{{ $post->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2 uppercase">{{ $post->type }}</td>
                            <td class="px-3 py-2">{{ $post->template?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $post->status }}</td>
                            <td class="px-3 py-2">{{ $post->container_id ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-500">Belum ada riwayat unggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
