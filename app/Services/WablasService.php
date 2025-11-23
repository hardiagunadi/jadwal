<?php

namespace App\Services;

use App\Models\Kegiatan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WablasService
{
    protected string $baseUrl;
    protected string $token;
    protected ?string $secretKey;
    protected string $groupId;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('wablas.base_url', 'https://solo.wablas.com'), '/');
        $this->token     = (string) config('wablas.token', '');
        $this->secretKey = config('wablas.secret_key');      // boleh null / kosong
        $this->groupId   = (string) config('wablas.group_id', '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' &&
            $this->token !== '' &&
            $this->groupId !== '';
    }

    protected function getAuthHeaderValue(): string
    {
        // Kalau secret key diisi, pakai "token.secret"
        // Kalau tidak, pakai token saja (beberapa device Solo Wablas pakai ini)
        if (! empty($this->secretKey)) {
            return $this->token . '.' . $this->secretKey;
        }

        return $this->token;
    }

    protected function client()
    {
        return Http::withHeaders([
                'Authorization' => $this->getAuthHeaderValue(),
                'Content-Type'  => 'application/json',
            ])
            // kalau SSL sudah rapi, boleh dihapus verify=false
            ->withOptions(['verify' => false]);
    }

    /**
     * Format pesan rekap untuk 1 / banyak kegiatan (untuk grup WA).
     *
     * @param iterable<Kegiatan> $kegiatans
     */
    protected function buildGroupMessage(iterable $kegiatans): string
    {
        $items = $kegiatans instanceof Collection ? $kegiatans : collect($kegiatans);
        $items = $items->sortBy('tanggal');

        $messageLines = [];

        $messageLines[] = '*REKAP AGENDA KEGIATAN KANTOR*';
        $messageLines[] = '';

        $messageLines[] = 'Tanggal rekap: *' .
            optional($items->first()->tanggal)->format('d-m-Y') . '*';
        $messageLines[] = '';

        $no = 1;

        /** @var \App\Models\Kegiatan $kegiatan */
        foreach ($items as $kegiatan) {
            $messageLines[] = $no . '. *' . ($kegiatan->nama_kegiatan ?? '-') . '*';
            $messageLines[] = '   🆔 No      : ' . ($kegiatan->nomor ?? '-');
            $messageLines[] = '   📅 Tanggal : ' . ($kegiatan->tanggal_label ?? '-');
            $messageLines[] = '   ⏰ Waktu   : ' . ($kegiatan->waktu ?? '-');
            $messageLines[] = '   📍 Tempat  : ' . ($kegiatan->tempat ?? '-');

            $personils = $kegiatan->personils ?? collect();
            if ($personils->isNotEmpty()) {
                $messageLines[] = '   👥 Personil Hadir:';
                foreach ($personils as $p) {
                    $label = $p->label ?? $p->nama;
                    $noWa  = $p->no_wa ?: '-';
                    $messageLines[] = '      - ' . $label . ' [' . $noWa . ']';
                }
            } else {
                $messageLines[] = '   👥 Personil Hadir: -';
            }

            if (! empty($kegiatan->keterangan)) {
                $messageLines[] = '   📝 Ket     : ' . $kegiatan->keterangan;
            }

            $messageLines[] = '';
            $no++;
        }

        $messageLines[] = '_Pesan ini dikirim otomatis dari sistem rekap agenda kantor._';

        return implode("\n", $messageLines);
    }

    /**
     * Kirim rekap ke GRUP WA.
     *
     * @param iterable<Kegiatan> $kegiatans
     */
    public function sendGroupRekap(iterable $kegiatans): bool
    {
        if (! $this->isConfigured()) {
            Log::error('WablasService: konfigurasi belum lengkap', [
                'base_url' => $this->baseUrl,
                'token_set' => $this->token !== '',
                'group_id' => $this->groupId,
            ]);

            return false;
        }

        $items = $kegiatans instanceof Collection ? $kegiatans : collect($kegiatans);

        if ($items->isEmpty()) {
            Log::warning('WablasService: sendGroupRekap dipanggil tanpa data kegiatan');

            return false;
        }

        $message = $this->buildGroupMessage($items);

        $payload = [
            'data' => [
                [
                    'phone'   => $this->groupId,   // HARUS group id, contoh: 62878xxx-16321xxxx
                    'message' => $message,
                    'isGroup' => 'true',          // per dokumentasi: string 'true'
                ],
            ],
        ];

        $response = $this->client()
            ->post($this->baseUrl . '/api/v2/send-message', $payload);

        if (! $response->successful()) {
            Log::error('WablasService: HTTP error kirim group', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        }

        $json = $response->json();

        // log untuk debug: lihat di storage/logs/laravel.log
        Log::info('WablasService: response sendGroupRekap', [
            'response' => $json,
        ]);

        return (bool) data_get($json, 'status', false);
    }

    // === metode sendToPersonils tetap pakai yg lama (boleh copy dari versi sebelumnya) ===
}
