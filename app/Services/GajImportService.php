<?php

namespace App\Services;

use App\Models\Gaj;
use App\Models\GajPegawai;
use Illuminate\Support\Facades\DB;

class GajImportService
{
    /**
     * Parse & import PDF daftar gaji dari SIPD.
     * Mengembalikan ['gaj' => Gaj, 'is_new' => bool, 'jumlah' => int].
     */
    public function parseAndImport(string $pdfPath, ?string $seksiAkronim = null): array
    {
        $pdftotext = '/usr/bin/pdftotext';

        if (! file_exists($pdftotext)) {
            throw new \RuntimeException('pdftotext tidak ditemukan. Pastikan poppler-utils terinstal.');
        }

        $text = shell_exec($pdftotext . ' -layout ' . escapeshellarg($pdfPath) . ' -');

        if (empty($text)) {
            throw new \RuntimeException('Gagal membaca PDF atau file kosong.');
        }

        return $this->importFromText($text, $seksiAkronim);
    }

    /**
     * Parse PDF daftar gaji dari SIPD tanpa import.
     * Mengembalikan ['header' => array, 'pegawais' => array].
     */
    public function parseOnly(string $pdfPath): array
    {
        $pdftotext = '/usr/bin/pdftotext';

        if (! file_exists($pdftotext)) {
            throw new \RuntimeException('pdftotext tidak ditemukan. Pastikan poppler-utils terinstal.');
        }

        $text = shell_exec($pdftotext . ' -layout ' . escapeshellarg($pdfPath) . ' -');

        if (empty($text)) {
            throw new \RuntimeException('Gagal membaca PDF atau file kosong.');
        }

        $text  = preg_replace('/\r\n|\r/', "\n", $text);
        $lines = explode("\n", $text);

        $header   = $this->parseHeader($text);
        $pegawais = $this->parsePegawais($lines);

        if (empty($pegawais)) {
            throw new \RuntimeException('Tidak ada data pegawai yang dapat diparsing dari PDF ini.');
        }

        return [
            'header'   => $header,
            'pegawais' => $pegawais,
        ];
    }

    public function importFromParsedData(array $header, array $pegawais, ?string $seksiAkronim = null): array
    {
        if (empty($pegawais)) {
            throw new \RuntimeException('Tidak ada data pegawai untuk diimport.');
        }

        return DB::transaction(function () use ($header, $pegawais, $seksiAkronim) {
            $isNew = false;

            $gaj = Gaj::updateOrCreate(
                [
                    'jenis'       => $header['jenis'],
                    'kode_satker' => $header['kode_satker'],
                    'bulan'       => $header['bulan'],
                    'tahun'       => $header['tahun'],
                ],
                [
                    'nama_satker'  => $header['nama_satker'],
                    'seksi_akronim' => $seksiAkronim ? strtolower(trim($seksiAkronim)) : null,
                ]
            );

            if ($gaj->wasRecentlyCreated) {
                $isNew = true;
            } else {
                // Hapus data lama sebelum re-import
                $gaj->pegawais()->delete();
            }

            foreach ($pegawais as $p) {
                GajPegawai::create(array_merge(['gaj_id' => $gaj->id], $p));
            }

            return [
                'gaj'    => $gaj,
                'is_new' => $isNew,
                'jumlah' => count($pegawais),
            ];
        });
    }

    public function importFromText(string $text, ?string $seksiAkronim = null): array
    {
        $text  = preg_replace('/\r\n|\r/', "\n", $text);
        $lines = explode("\n", $text);

        $header   = $this->parseHeader($text);
        $pegawais = $this->parsePegawais($lines);

        if (empty($pegawais)) {
            throw new \RuntimeException('Tidak ada data pegawai yang dapat diparsing dari PDF ini.');
        }

        return $this->importFromParsedData($header, $pegawais, $seksiAkronim);
    }

    // ─── Header ──────────────────────────────────────────────────────────────

    private function parseHeader(string $text): array
    {
        $jenis = 'pppk';
        if (preg_match('/GAJI\s+INDUK\s+(PNS|PPPK)/i', $text, $m)) {
            $jenis = strtolower($m[1]);
        }

        $nama_satker = '';
        $kode_satker = '';

        // "[ KECAMATAN WATUMALANG ] KECAMATAN WATUMALANG"
        if (preg_match('/\[\s*([^\]]+)\]/i', $text, $m)) {
            $nama_satker = trim($m[1]);
        }

        // "(025) D40100702500001"
        if (preg_match('/\((\d+)\)\s+([A-Z]\d+)/i', $text, $m)) {
            $kode_satker = trim($m[2]);
        }

        $bulan = 0;
        $tahun = 0;
        if (preg_match('/BULAN\s*:\s*([A-Z]+)\s+(\d{4})/i', $text, $m)) {
            $bulan = $this->bulanToInt($m[1]);
            $tahun = (int) $m[2];
        }

        return compact('jenis', 'nama_satker', 'kode_satker', 'bulan', 'tahun');
    }

    private function bulanToInt(string $nama): int
    {
        $map = [
            'januari' => 1, 'februari' => 2, 'maret' => 3,
            'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];
        return $map[strtolower(trim($nama))] ?? 0;
    }

    // ─── Pegawai ─────────────────────────────────────────────────────────────

    private function parsePegawais(array $lines): array
    {
        $results = [];

        foreach ($lines as $i => $line) {
            // Baris NIP: "  N     XXXXXXXXXXXXXXXXXX" (NO + NIP 15-18 digit)
            if (! preg_match('/^\s{0,6}(\d{1,3})\s{3,10}(\d{15,18})\s*/', $line, $m)) {
                continue;
            }

            $noUrut = (int) $m[1];
            $nip    = trim($m[2]);

            if ($i < 4 || $i + 5 >= count($lines)) {
                continue;
            }

            // Nama row (i-4): gaji_pokok(C1) tunj_eselon(C2) tunj_terpencil(C3)
            //                  tunj_bpjskes_4(C4) pot_pajak(C5) tapera_pk_pot(C6)
            $namaLine = $lines[$i - 4] ?? '';

            // Validasi: baris NAMA harus mengandung teks nama
            $namaCand = trim(substr($namaLine, 8, 32));
            if (empty($namaCand) || preg_match('/^\d/', $namaCand)) {
                continue;
            }

            // Kawin row (i-3): tunj_istri(C1) tunj_fung_umum(C2) tkd(C3)
            //                   tunj_jkk(C4) pot_bpjs_kes(C5) tapera_peg(C6)
            $kawinLine = $lines[$i - 3] ?? '';

            // Tgl lahir row (i-2): hanya tanggal
            $tglLine = $lines[$i - 2] ?? '';

            // Anak row (i-1): tunj_anak(C1) tunj_fungsional(C2) tunj_beras(C3)
            //                  tunj_jkm(C4) pot_iwp_1(C5) hutang_lain2(C6)
            $anakLine = $lines[$i - 1] ?? '';

            // NIP row (i): tapera_pk_pgh(C4) pot_iwp_8(C5) bulog(C6)
            $nipLine = $line;

            // Jumlah row (i+1): jumlah_penghasilan(C1) tunj_khusus(C2) tunj_pajak(C3)
            $jumlahLine = $lines[$i + 1] ?? '';

            // Golongan row (i+2): pembulatan(C4) pot_taperum(C5) sewa_rumah(C6)
            $statusLine = $lines[$i + 2] ?? '';

            // Kotor row (i+3): jml_kotor(C4) pot_jkk(C5) jml_potongan(C6) no_rekening(C7)
            $kotorLine = $lines[$i + 3] ?? '';

            // NPWP row (i+4)
            $npwpLine = $lines[$i + 4] ?? '';

            // Bersih row (i+5): pot_jkm(C5) jumlah_bersih(C6)
            $bersihLine = $lines[$i + 5] ?? '';

            $pegawai = [
                'no_urut'       => $noUrut,
                'nama'          => $this->parseName($namaLine),
                'tanggal_lahir' => $this->parseTanggalLahir($tglLine),
                'nip'           => $nip,
                'npwp'          => $this->parseNpwp($npwpLine),
                'status_kawin'  => $this->parseStatusKawin($namaLine),
                'golongan'      => $this->parseGolongan($statusLine),
                // Penghasilan
                'gaji_pokok'         => $this->c(1, $namaLine),
                'tunj_eselon'        => $this->c(2, $namaLine),
                'tunj_terpencil'     => $this->c(3, $namaLine),
                'tunj_bpjskes_4'     => $this->c(4, $namaLine),
                'tunj_istri'         => $this->c(1, $kawinLine),
                'tunj_fung_umum'     => $this->c(2, $kawinLine),
                'tkd'                => $this->c(3, $kawinLine),
                'tunj_jkk'           => $this->c(4, $kawinLine),
                'tunj_anak'          => $this->c(1, $anakLine),
                'tunj_fungsional'    => $this->c(2, $anakLine),
                'tunj_beras'         => $this->c(3, $anakLine),
                'tunj_jkm'           => $this->c(4, $anakLine),
                'jumlah_penghasilan' => $this->c(1, $jumlahLine),
                'tunj_khusus'        => $this->c(2, $jumlahLine),
                'tunj_pajak'         => $this->c(3, $jumlahLine),
                'tapera_pk_pgh'      => $this->c(4, $nipLine),
                'pembulatan'         => $this->c(4, $statusLine),
                'jml_kotor'          => $this->c(4, $kotorLine),
                // Potongan
                'pot_pajak'    => $this->c(5, $namaLine),
                'tapera_pk_pot'=> $this->c(6, $namaLine),
                'pot_bpjs_kes' => $this->c(5, $kawinLine),
                'tapera_peg'   => $this->c(6, $kawinLine),
                'pot_iwp_1'    => $this->c(5, $anakLine),
                'hutang_lain2' => $this->c(6, $anakLine),
                'pot_iwp_8'    => $this->c(5, $nipLine),
                'bulog'        => $this->c(6, $nipLine),
                'pot_taperum'  => $this->c(5, $statusLine),
                'sewa_rumah'   => $this->c(6, $statusLine),
                'pot_jkk'      => $this->c(5, $kotorLine),
                'jml_potongan' => $this->c(6, $kotorLine),
                'pot_jkm'      => $this->c(5, $bersihLine),
                'jumlah_bersih'=> $this->c(6, $bersihLine),
                'no_rekening'  => $this->parseNoRekening($kotorLine),
            ];

            if ($pegawai['gaji_pokok'] === 0 && $pegawai['jumlah_bersih'] === 0) {
                continue;
            }

            $results[] = $pegawai;
        }

        return $results;
    }

    /**
     * Ekstrak nilai dari kolom ke-N pada satu baris.
     *
     * Kolom: 1=C1(44-66) 2=C2(66-84) 3=C3(84-103) 4=C4(103-121) 5=C5(121-139) 6=C6(139-157)
     */
    private function c(int $col, string $line): int
    {
        $ranges = [
            1 => [44, 66],
            2 => [66, 84],
            3 => [84, 103],
            4 => [103, 121],
            5 => [121, 139],
            6 => [139, 157],
        ];

        [$start, $end] = $ranges[$col];

        return $this->extractLastNumInRange($line, $start, $end);
    }

    // ─── Field parsers ───────────────────────────────────────────────────────

    private function parseName(string $line): string
    {
        // Nama di pos 9, kolom STS kawin mulai ~pos 38
        $raw = substr($line, 9, 32);
        // Hapus status kawin (TK, K) dan seterusnya jika masuk dalam window
        $raw = preg_replace('/\s+(?:TK|K)\b.*$/i', '', $raw);
        return trim($raw);
    }

    private function parseTanggalLahir(string $line): string
    {
        // Format: dd-mm-yyyy di pos ~8
        if (preg_match('/(\d{2}-\d{2}-\d{4})/', $line, $m)) {
            return $m[1];
        }
        return '';
    }

    private function parseNpwp(string $line): string
    {
        // NPWP: 15 digit di pos ~8
        if (preg_match('/\b(\d{15})\b/', $line, $m)) {
            return $m[1];
        }
        return '';
    }

    private function parseStatusKawin(string $line): string
    {
        // Ambil STS dari pos 38-52 (TK - 0, K-1, K-2, dll)
        $segment = substr($line, 35, 18);
        if (preg_match('/(TK\s*-\s*\d|K\s*-\s*\d)/i', $segment, $m)) {
            return preg_replace('/\s+/', '', $m[0]);
        }
        return '';
    }

    private function parseGolongan(string $line): string
    {
        // STATUS PEGAWAI: "( PPPK - 07 ) MKG: 3" atau "( PNS - 4A ) MKG: 22"
        // Ekstrak konten dalam kurung: "PPPK - 07" atau "PNS - 4A"
        if (preg_match('/\(\s*((?:PPPK|PNS|CPNS)\s*-\s*\w+)\s*\)/i', $line, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function parseNoRekening(string $line): string
    {
        // No rekening di pos 155+ (minimal 8 digit)
        $segment = substr($line, 155);
        if (preg_match('/(\d{8,})/', $segment, $m)) {
            return $m[1];
        }
        return '';
    }

    // ─── Utilities ───────────────────────────────────────────────────────────

    /**
     * Ekstrak angka terakhir yang MULAI di rentang [$start, $end) pada baris.
     *
     * Tidak menggunakan substr agar angka yang melewati batas kolom
     * tidak terpotong (misal "3,291,900" di pos 151 melewati batas 157).
     */
    private function extractLastNumInRange(string $line, int $start, int $end): int
    {
        if ($start >= strlen($line)) {
            return 0;
        }

        // Cari semua angka di seluruh baris beserta posisinya
        preg_match_all('/\d[\d,]*/', $line, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return 0;
        }

        // Ambil angka terakhir yang posisi awalnya dalam rentang kolom
        $result = 0;
        foreach ($matches[0] as [$numStr, $offset]) {
            if ($offset >= $start && $offset < $end) {
                $result = (int) str_replace(',', '', $numStr);
            }
        }

        return $result;
    }
}
