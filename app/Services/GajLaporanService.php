<?php

namespace App\Services;

use App\Models\Gaj;
use App\Models\GajPegawai;
use Illuminate\Support\Collection;

/**
 * Komputasi data untuk laporan PERBEDAAN, RINCIAN, dan JML. PEG.
 */
class GajLaporanService
{
    // ─── Golongan helpers ────────────────────────────────────────────────────

    /** Parse golongan field → ['num' => 3, 'letter' => 'D', 'full' => 'III/D', 'roman' => 'III'] */
    public function parseGolonganPns(string $gol): ?array
    {
        // "PNS - 3D" / "CPNS - 2A" / "(PNS - 3D)"
        if (preg_match('/(?:PNS|CPNS)\s*-\s*(\d)([A-E])/i', $gol, $m)) {
            static $romMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
            $num    = (int) $m[1];
            $letter = strtoupper($m[2]);
            return [
                'num'    => $num,
                'letter' => $letter,
                'full'   => ($romMap[$num] ?? 'I') . '/' . $letter,
                'roman'  => $romMap[$num] ?? 'I',
            ];
        }
        return null;
    }

    /** Parse golongan PPPK → int (nomor gol, misal 7) */
    public function parseGolonganPppk(string $gol): ?int
    {
        if (preg_match('/PPPK\s*-\s*0?(\d+)/i', $gol, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /** Kelompok roman (I/II/III/IV) dari nomor gol PPPK */
    public function pppkGolToGroup(int $gol): string
    {
        if ($gol <= 4) return 'I';
        if ($gol <= 8) return 'II';
        if ($gol <= 12) return 'III';
        return 'IV';
    }

    /** Level eselon dari tunj_eselon */
    public function eselonLevel(int $tunjEselon): ?int
    {
        if ($tunjEselon >= 5_500_000) return 1;
        if ($tunjEselon >= 2_000_000) return 2;
        if ($tunjEselon >= 900_000)   return 3;
        if ($tunjEselon >= 400_000)   return 4;
        return null; // staf / fungsional
    }

    /** Parse istri & anak dari status_kawin ("K-2", "TK-0", dll) */
    public function parseKawin(string $sts): array
    {
        if (preg_match('/^(TK|K)-?(\d+)$/i', trim($sts), $m)) {
            return [
                'istri' => strtoupper($m[1]) === 'K' ? 1 : 0,
                'anak'  => (int) $m[2],
            ];
        }
        return ['istri' => 0, 'anak' => 0];
    }

    // ─── Bulan sebelumnya ────────────────────────────────────────────────────

    public function getGajBulanLalu(Gaj $gaj): ?Gaj
    {
        $bulan = $gaj->bulan == 1 ? 12 : $gaj->bulan - 1;
        $tahun = $gaj->bulan == 1 ? $gaj->tahun - 1 : $gaj->tahun;

        return Gaj::where('jenis', $gaj->jenis)
            ->where('kode_satker', $gaj->kode_satker)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
    }

    // ─── PERBEDAAN ───────────────────────────────────────────────────────────

    /**
     * Hitung data per golongan untuk sheet PERBEDAAN.
     *
     * Return: [
     *   'gol_key' => ['peg'=>int, 'istri'=>int, 'anak'=>int, 'jiwa'=>int, 'kotor'=>int]
     * ]
     * gol_key untuk PNS: 'I/A' ... 'IV/D'
     * gol_key untuk PPPK: 'GOL-7' dsb.
     */
    public function perbedaanPerGolongan(Gaj $gaj): array
    {
        $result = [];

        foreach ($gaj->pegawais as $p) {
            $key = $this->getPerbedaanKey($gaj->jenis, $p->golongan);
            if ($key === null) continue;

            $kawin = $this->parseKawin($p->status_kawin);

            if (! isset($result[$key])) {
                $result[$key] = ['peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0, 'kotor' => 0];
            }
            $result[$key]['peg']++;
            $result[$key]['istri'] += $kawin['istri'];
            $result[$key]['anak']  += $kawin['anak'];
            $result[$key]['jiwa']  += 1 + $kawin['istri'] + $kawin['anak'];
            $result[$key]['kotor'] += $p->jml_kotor;
        }

        return $result;
    }

    private function getPerbedaanKey(string $jenis, string $golStr): ?string
    {
        if ($jenis === 'pns') {
            $g = $this->parseGolonganPns($golStr);
            return $g ? $g['full'] : null;
        }
        $gol = $this->parseGolonganPppk($golStr);
        return $gol !== null ? "GOL-{$gol}" : null;
    }

    // ─── RINCIAN ─────────────────────────────────────────────────────────────

    /**
     * Hitung data per kelompok golongan (I/II/III/IV) untuk sheet RINCIAN.
     *
     * Return: array dengan key 'I'/'II'/'III'/'IV' berisi ringkasan.
     */
    public function rincianPerGolongan(Gaj $gaj): array
    {
        $groups = ['I' => $this->emptyRincian(), 'II' => $this->emptyRincian(), 'III' => $this->emptyRincian(), 'IV' => $this->emptyRincian()];

        foreach ($gaj->pegawais as $p) {
            $group = $this->getRincianGroup($gaj->jenis, $p->golongan);
            if ($group === null) continue;

            $kawin = $this->parseKawin($p->status_kawin);
            $g     = &$groups[$group];

            $g['peg']++;
            $g['istri']  += $kawin['istri'];
            $g['anak']   += $kawin['anak'];
            $g['jiwa']   += 1 + $kawin['istri'] + $kawin['anak'];

            if ($p->tunj_eselon > 0)    $g['eselon_struktural']++;
            if ($p->tunj_fungsional > 0) $g['eselon_fungsional']++;

            $g['gaji_pokok']      += $p->gaji_pokok;
            $g['tunj_istri']      += $p->tunj_istri;
            $g['tunj_anak']       += $p->tunj_anak;
            $g['tunj_fung_umum']  += $p->tunj_fung_umum;
            $g['tunj_eselon']     += $p->tunj_eselon;
            $g['tunj_fungsional'] += $p->tunj_fungsional;
            $g['tunj_khusus']     += $p->tunj_khusus;
            $g['tkd']             += $p->tkd;
            $g['tunj_pajak']      += $p->tunj_pajak;
            $g['pembulatan']      += $p->pembulatan;
            $g['tunj_beras']      += $p->tunj_beras;
            $g['jml_kotor']       += $p->jml_kotor;
            $g['pot_iwp_1']       += $p->pot_iwp_1;
            $g['pot_iwp_8']       += $p->pot_iwp_8;
            $g['pot_taperum']     += $p->pot_taperum;
            $g['pot_pajak']       += $p->pot_pajak;
            $g['jml_potongan']    += $p->jml_potongan;
            $g['jumlah_bersih']   += $p->jumlah_bersih;
        }

        return $groups;
    }

    private function emptyRincian(): array
    {
        return [
            'peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0,
            'eselon_struktural' => 0, 'eselon_fungsional' => 0,
            'gaji_pokok' => 0, 'tunj_istri' => 0, 'tunj_anak' => 0,
            'tunj_fung_umum' => 0, 'tunj_eselon' => 0, 'tunj_fungsional' => 0,
            'tunj_khusus' => 0, 'tkd' => 0, 'tunj_pajak' => 0, 'pembulatan' => 0,
            'tunj_beras' => 0, 'jml_kotor' => 0,
            'pot_iwp_1' => 0, 'pot_iwp_8' => 0, 'pot_taperum' => 0,
            'pot_pajak' => 0, 'jml_potongan' => 0, 'jumlah_bersih' => 0,
        ];
    }

    private function getRincianGroup(string $jenis, string $golStr): ?string
    {
        if ($jenis === 'pns') {
            $g = $this->parseGolonganPns($golStr);
            return $g ? $g['roman'] : null;
        }
        $gol = $this->parseGolonganPppk($golStr);
        return $gol !== null ? $this->pppkGolToGroup($gol) : null;
    }

    // ─── JML. PEG ────────────────────────────────────────────────────────────

    /**
     * Hitung jumlah pegawai per golongan & eselon untuk sheet JML. PEG.
     *
     * Return untuk PNS: ['I/A' => [1=>0, 2=>0, 3=>1, 4=>0], ...]
     * Return untuk PPPK: ['GOL-7' => ['I'=>0,'II'=>0,'III'=>0,'IV'=>0,'STAF'=>2], ...]
     */
    public function jmlPegPerGolEselon(Gaj $gaj): array
    {
        $result = [];

        foreach ($gaj->pegawais as $p) {
            if ($gaj->jenis === 'pns') {
                $g = $this->parseGolonganPns($p->golongan);
                if (! $g) continue;
                $key    = $g['full'];
                $eselon = $this->eselonLevel($p->tunj_eselon) ?? 'STAF';

                if (! isset($result[$key])) {
                    $result[$key] = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
                }
                if (is_int($eselon)) {
                    $result[$key][$eselon]++;
                }
                // Staf PNS tidak dimasukkan ke kolom eselon (tetap 0)
            } else {
                $gol = $this->parseGolonganPppk($p->golongan);
                if ($gol === null) continue;
                $key    = "GOL-{$gol}";
                $eselon = $this->eselonLevel($p->tunj_eselon);
                $col    = $eselon !== null ? (string) $eselon : 'STAF';

                if (! isset($result[$key])) {
                    $result[$key] = ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, 'STAF' => 0];
                }
                // Map int eselon ke roman string untuk PPPK
                $colMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
                $colKey = isset($colMap[$eselon]) ? $colMap[$eselon] : 'STAF';
                $result[$key][$colKey]++;
            }
        }

        return $result;
    }
}
