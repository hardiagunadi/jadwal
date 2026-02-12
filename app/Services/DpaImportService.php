<?php

namespace App\Services;

use App\Models\Dpa;
use App\Models\DpaRincianBelanja;
use App\Models\DpaSubKegiatan;
use Illuminate\Support\Facades\DB;

class DpaImportService
{
    /**
     * Parse PDF DPA dari SIPD menggunakan pdftotext CLI.
     * Mengembalikan array terstruktur atau melempar exception jika gagal.
     */
    public function parse(string $pdfPath): array
    {
        $pdftotext = '/usr/bin/pdftotext';

        if (! file_exists($pdftotext)) {
            throw new \RuntimeException('pdftotext tidak ditemukan. Pastikan poppler-utils terinstal.');
        }

        $escapedPath = escapeshellarg($pdfPath);
        $text = shell_exec("{$pdftotext} -layout {$escapedPath} -");

        if (empty($text)) {
            throw new \RuntimeException('Gagal membaca PDF. Pastikan file tidak terenkripsi.');
        }

        return $this->parseText($text);
    }

    /**
     * Parse teks hasil pdftotext menjadi array terstruktur.
     */
    public function parseText(string $text): array
    {
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $lines = explode("\n", $text);

        $header = $this->extractHeader($text);
        $subKegiatans = $this->extractSubKegiatans($text, $lines);

        return [
            'header' => $header,
            'sub_kegiatans' => $subKegiatans,
        ];
    }

    private function extractHeader(string $text): array
    {
        $header = [];

        // Nomor DPA
        if (preg_match('/Nomor\s+DPA\s*:\s*(DPA\/\S+)/i', $text, $m)) {
            $header['nomor_dpa'] = trim($m[1]);
        }

        // Tahun Anggaran
        if (preg_match('/TAHUN ANGGARAN\s+(\d{4})/i', $text, $m)) {
            $header['tahun'] = (int) $m[1];
        }

        // Urusan Pemerintahan
        if (preg_match('/Urusan Pemerintahan\s*:\s*(.+)/i', $text, $m)) {
            $header['urusan'] = trim($m[1]);
        }

        // Bidang Urusan
        if (preg_match('/Bidang Urusan\s*:\s*(.+)/i', $text, $m)) {
            $header['bidang_urusan'] = trim($m[1]);
        }

        // Program
        if (preg_match('/^Program\s*:\s*(.+)/im', $text, $m)) {
            $header['program'] = trim($m[1]);
        }

        // Kegiatan (bisa multi-baris, ambil sampai baris kosong/berikutnya)
        if (preg_match('/^Kegiatan\s*:\s*(.+)/im', $text, $m)) {
            $header['kegiatan'] = trim($m[1]);
        }

        // Organisasi
        if (preg_match('/^Organisasi\s*:\s*(.+)/im', $text, $m)) {
            $header['organisasi'] = trim($m[1]);
        }

        // Pagu (Alokasi Tahun tanpa -1 / +1)
        if (preg_match('/^Alokasi Tahun\s*:\s*Rp([\d.,]+)/im', $text, $m)) {
            $header['pagu_anggaran'] = $this->parseRupiah($m[1]);
        }

        return $header;
    }

    /**
     * Extract sub kegiatan beserta rincian belanjanya.
     */
    private function extractSubKegiatans(string $text, array $lines): array
    {
        $subKegiatans = [];

        // Cari semua "Sub Kegiatan : KODE - Nama"
        preg_match_all(
            '/Sub\s+Kegiatan\s*:\s*([\d.]+)\s*[-–]\s*(.+?)(?=\n)/im',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (empty($matches[0])) {
            return $subKegiatans;
        }

        $textLen = strlen($text);

        foreach ($matches[0] as $idx => $match) {
            $kode = trim($matches[1][$idx][0]);
            $nama = trim($matches[2][$idx][0]);
            $startPos = $match[1];

            // Tentukan batas akhir: posisi sub kegiatan berikutnya atau akhir teks
            $endPos = $idx + 1 < count($matches[0])
                ? $matches[0][$idx + 1][1]
                : $textLen;

            $block = substr($text, $startPos, $endPos - $startPos);

            // Sumber pendanaan
            $sumberPendanaan = null;
            if (preg_match('/Sumber\s+Pendanaan\s*:\s*(.+)/i', $block, $sm)) {
                $sumberPendanaan = trim($sm[1]);
            }

            // Pagu sub kegiatan dari "Jumlah Anggaran Sub Kegiatan Rp..."
            $pagu = 0;
            if (preg_match('/Jumlah\s+Anggaran\s+Sub\s+Kegiatan\s+Rp([\d.,]+)/i', $block, $pm)) {
                $pagu = $this->parseRupiah($pm[1]);
            }

            $rincians = $this->extractRincianBelanja($block);

            $subKegiatans[] = [
                'kode' => $kode,
                'nama' => $nama,
                'sumber_pendanaan' => $sumberPendanaan,
                'pagu' => $pagu,
                'rincian_belanjas' => $rincians,
            ];
        }

        return $subKegiatans;
    }

    /**
     * Extract rincian belanja dari blok teks sub kegiatan.
     * Hanya ambil kode rekening level detail (minimal 4 segmen, mis. 5.1.02.01.001.00052).
     */
    private function extractRincianBelanja(string $block): array
    {
        $rincians = [];
        $blockLines = explode("\n", $block);

        foreach ($blockLines as $line) {
            $line = trim($line);

            // Pattern: kode_rekening uraian ... Rp jumlah
            // Kode rekening: angka dengan titik, minimal 3 segmen (mis. 5.1.02)
            if (! preg_match('/^(\d+(?:\.\d+){2,})\s+(.+?)\s{2,}Rp([\d.,]+)$/u', $line, $m)) {
                continue;
            }

            $kode = trim($m[1]);
            $uraian = trim($m[2]);
            $jumlah = $this->parseRupiah($m[3]);

            // Lewati kode parent (kurang dari 4 segmen): 5, 5.1, 5.1.02
            $segCount = substr_count($kode, '.') + 1;
            if ($segCount < 4) {
                continue;
            }

            // Coba parse volume/satuan/harga dari baris berikutnya atau dari uraian
            // Format umum detail: "Uraian   VOLUME Satuan Rpharga 0% Rpjumlah"
            $volume = null;
            $satuan = null;
            $harga = null;
            $ppnPersen = 0.0;

            if (preg_match(
                '/^(.+?)\s+([\d.,]+)\s+(\S+(?:\s*\/\s*\S+)?)\s+Rp([\d.,]+)\s+([\d.]+)%\s+Rp([\d.,]+)$/u',
                $line,
                $dm
            )) {
                $volume = (float) str_replace(['.', ','], ['', '.'], $dm[2]);
                $satuan = trim($dm[3]);
                $harga = $this->parseRupiah($dm[4]);
                $ppnPersen = (float) $dm[5];
            }

            $rincians[] = [
                'kode_rekening' => $kode,
                'uraian' => $uraian,
                'volume' => $volume,
                'satuan' => $satuan,
                'harga' => $harga,
                'ppn_persen' => $ppnPersen,
                'jumlah' => $jumlah,
            ];
        }

        return $rincians;
    }

    /**
     * Parse "1.234.567,00" atau "1.234.567" menjadi integer rupiah.
     */
    private function parseRupiah(string $value): int
    {
        // Hapus titik ribuan, ganti koma desimal dengan titik
        $clean = str_replace('.', '', $value);
        $clean = str_replace(',', '.', $clean);

        return (int) round((float) $clean);
    }

    /**
     * Import data hasil parse ke database (upsert).
     * Return array ['dpa' => Dpa, 'is_new' => bool, 'sub_baru' => int, 'sub_update' => int].
     */
    public function importFromArray(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $header = $data['header'];

            $existingDpa = Dpa::where('nomor_dpa', $header['nomor_dpa'] ?? 'UNKNOWN')->first();
            $isNew = $existingDpa === null;

            /** @var Dpa $dpa */
            $dpa = Dpa::updateOrCreate(
                ['nomor_dpa' => $header['nomor_dpa'] ?? 'UNKNOWN'],
                [
                    'tahun' => $header['tahun'] ?? now()->year,
                    'urusan' => $header['urusan'] ?? null,
                    'bidang_urusan' => $header['bidang_urusan'] ?? null,
                    'program' => $header['program'] ?? null,
                    'kegiatan' => $header['kegiatan'] ?? null,
                    'organisasi' => $header['organisasi'] ?? null,
                    'pagu_anggaran' => $header['pagu_anggaran'] ?? 0,
                ]
            );

            $subBaru = 0;
            $subUpdate = 0;

            foreach ($data['sub_kegiatans'] as $subData) {
                $subExisting = DpaSubKegiatan::where('dpa_id', $dpa->id)
                    ->where('kode', $subData['kode'])
                    ->exists();

                /** @var DpaSubKegiatan $sub */
                $sub = DpaSubKegiatan::updateOrCreate(
                    ['dpa_id' => $dpa->id, 'kode' => $subData['kode']],
                    [
                        'nama' => $subData['nama'],
                        'sumber_pendanaan' => $subData['sumber_pendanaan'] ?? null,
                        'pagu' => $subData['pagu'] ?? 0,
                    ]
                );

                $subExisting ? $subUpdate++ : $subBaru++;

                foreach ($subData['rincian_belanjas'] as $rincian) {
                    DpaRincianBelanja::updateOrCreate(
                        [
                            'dpa_sub_kegiatan_id' => $sub->id,
                            'kode_rekening' => $rincian['kode_rekening'],
                        ],
                        [
                            'uraian' => $rincian['uraian'],
                            'volume' => $rincian['volume'],
                            'satuan' => $rincian['satuan'],
                            'harga' => $rincian['harga'],
                            'ppn_persen' => $rincian['ppn_persen'],
                            'jumlah' => $rincian['jumlah'],
                        ]
                    );
                }
            }

            return [
                'dpa' => $dpa->fresh(['subKegiatans.rincianBelanjas']),
                'is_new' => $isNew,
                'sub_baru' => $subBaru,
                'sub_update' => $subUpdate,
            ];
        });
    }

    /**
     * Parse dan langsung import ke DB.
     */
    public function parseAndImport(string $pdfPath): array
    {
        $data = $this->parse($pdfPath);

        return $this->importFromArray($data);
    }
}
