<?php

namespace App\Console\Commands;

use App\Models\Kegiatan;
use App\Models\Personil;
use App\Services\WaGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendDisposisiHMinusOneReminder extends Command
{
    protected $signature = 'kegiatan:remind-disposisi-h-1 {--stage=all : Tahap pengingat (arsiparis|group|all)}';

    protected $description = 'Kirim pengingat Mohon Disposisi H-1 ke arsiparis (13:00) dan/atau grup WA (17:00).';

    public function handle(): int
    {
        /** @var WaGatewayService $waGateway */
        $waGateway = app(WaGatewayService::class);

        if (! $waGateway->isConfigured()) {
            $this->warn('WA Gateway belum dikonfigurasi.');

            return self::FAILURE;
        }

        $stage = $this->option('stage');

        if ($stage === 'arsiparis' || $stage === 'all') {
            $this->sendArsiparis($waGateway);
        }

        if ($stage === 'group' || $stage === 'all') {
            $this->sendGroup($waGateway);
        }

        return self::SUCCESS;
    }

    protected function getItemsBelumDisposisi(): Collection
    {
        $targetDate = Carbon::today()->addDay();

        return Kegiatan::query()
            ->whereDate('tanggal', $targetDate->toDateString())
            ->where('sudah_disposisi', false)
            ->orderBy('tanggal')
            ->get();
    }

    protected function sendArsiparis(WaGatewayService $waGateway): void
    {
        $items = $this->getItemsBelumDisposisi();

        if ($items->isEmpty()) {
            $this->info('Tidak ada agenda H-1 yang menunggu disposisi (arsiparis).');

            return;
        }

        $arsiparisNumbers = Personil::query()
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->where('jabatan', 'like', '%arsiparis%')
            ->pluck('no_wa')
            ->map(fn ($noWa) => trim((string) $noWa))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($arsiparisNumbers)) {
            $this->warn('Tidak ada personil arsiparis dengan nomor WA.');

            return;
        }

        $message = $this->buildArsiparisMessage($items);
        $result = $waGateway->sendPersonalText($arsiparisNumbers, $message);
        $success = (bool) ($result['success'] ?? false);

        if ($success) {
            $this->info("Pengingat cek disposisi terkirim ke arsiparis: {$items->count()} agenda.");
        } else {
            Log::warning('Gagal mengirim pengingat disposisi ke arsiparis.', [
                'count' => $items->count(),
                'error' => $result['error'] ?? null,
            ]);
            $this->warn('Gagal mengirim pengingat disposisi ke arsiparis.');
        }
    }

    protected function sendGroup(WaGatewayService $waGateway): void
    {
        $items = $this->getItemsBelumDisposisi();

        if ($items->isEmpty()) {
            $this->info('Tidak ada agenda H-1 yang menunggu disposisi (group).');

            return;
        }

        $items->loadMissing('personils');

        $success = $waGateway->sendGroupBelumDisposisi($items);

        if ($success) {
            $this->info("Pengingat Mohon Disposisi H-1 terkirim ke group: {$items->count()} agenda.");
        } else {
            Log::warning('Gagal mengirim pengingat Mohon Disposisi H-1 ke group.', [
                'count' => $items->count(),
            ]);
            $this->warn('Gagal mengirim pengingat Mohon Disposisi H-1 ke group.');
        }
    }

    protected function buildArsiparisMessage(Collection $items): string
    {
        $targetDate = Carbon::today()->addDay();
        $tanggalLabel = $targetDate->locale('id')->isoFormat('dddd, D MMMM Y');

        $lines = [];
        $lines[] = '*PENGINGAT CEK DISPOSISI PIMPINAN*';
        $lines[] = '';
        $lines[] = "Terdapat *{$items->count()} agenda* untuk tanggal *{$tanggalLabel}* yang belum mendapatkan disposisi pimpinan.";
        $lines[] = '';

        $no = 1;

        foreach ($items as $kegiatan) {
            $lines[] = "*{$no}. " . ($kegiatan->nama_kegiatan ?? '-') . '*';
            $lines[] = '   Waktu  : ' . ($kegiatan->waktu ?? '-');
            $lines[] = '   Tempat : ' . ($kegiatan->tempat ?? '-');
            $no++;
        }

        $lines[] = '';
        $lines[] = 'Mohon segera cek dan koordinasikan disposisi ke pimpinan agar agenda besok dapat berjalan lancar.';

        return implode("\n", $lines);
    }
}
