<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * Tarif PPn standar. Berlaku untuk semua transaksi yang kena PPn.
     * Jika ternyata vendor bukan PKP, PPn dihapus manual oleh operator.
     *
     * Ref: PMK No.62/PMK.03/2022 → PPN 11%
     */
    protected float $ppnRate = 11.0;

    /**
     * Kode rekening yang meskipun masuk 5.1.02.01.* (barang), tapi
     * secara hukum pajak dikenakan PPh 23 (bukan PPh 22) karena
     * sifatnya adalah jasa (katering, konsumsi, dsb).
     *
     * Ref: PMK No.141/PMK.03/2015 – "jasa boga atau katering" → PPh 23 2%
     */
    protected array $kodeJasaDalamBarang = [
        '5.1.02.01.001.00052', // Belanja Makanan dan Minuman Rapat
    ];

    /**
     * Hitung pajak berdasarkan kode rekening dan jumlah belanja.
     *
     * Aturan:
     *   5.1.02.01.*  → PPn 11% + PPh 22 1,5% (belanja barang)
     *     — kecuali kode yang masuk $kodeJasaDalamBarang → PPn 11% + PPh 23 2%
     *   5.1.02.02.001.00003 → PPh 21 5% (honorarium narasumber/tamu, tanpa PPn)
     *   5.1.02.02.*  → PPn 11% + PPh 23 2% (belanja jasa/sewa)
     *   5.1.02.04.*  → Bebas pajak (perjalanan dinas)
     *
     * PPn selalu dihitung 11% (semua vendor dianggap PKP).
     * Jika vendor bukan PKP, operator hapus nilai PPn secara manual.
     * PPh 23 tidak memiliki batas pengecualian minimum (berbeda dengan PPh 22).
     *
     * @param  int  $jumlah        Jumlah kotor (Rp)
     * @param  string  $kodeRekening  Kode rekening DPA
     * @return array{ppn:int,pph21:int,pph22:int,pph23:int,total_pajak:int}
     */
    public function calculate(int $jumlah, string $kodeRekening): array
    {
        $ppn = 0;
        $pph21 = 0;
        $pph22 = 0;
        $pph23 = 0;

        if ($jumlah <= 0) {
            return compact('ppn', 'pph21', 'pph22', 'pph23') + ['total_pajak' => 0];
        }

        if (in_array($kodeRekening, $this->kodeJasaDalamBarang, true)) {
            // Kode barang tapi sifatnya jasa (katering, makan minum rapat, dll): PPn 11% + PPh 23 2%
            $ppn  = (int) round($jumlah * $this->ppnRate / 100);
            $pph23 = (int) round($jumlah * 2 / 100);

        } elseif (str_starts_with($kodeRekening, '5.1.02.01')) {
            // Belanja barang: PPn 11% + PPh 22 1,5%
            $ppn  = (int) round($jumlah * $this->ppnRate / 100);
            $pph22 = (int) round($jumlah * 1.5 / 100);

        } elseif ($kodeRekening === '5.1.02.02.001.00003') {
            // Honorarium narasumber/tamu: PPh 21 5% (tidak dikenakan PPn)
            $pph21 = (int) round($jumlah * 5 / 100);

        } elseif (str_starts_with($kodeRekening, '5.1.02.02')) {
            // Belanja jasa/sewa: PPn 11% + PPh 23 2%
            $ppn  = (int) round($jumlah * $this->ppnRate / 100);
            $pph23 = (int) round($jumlah * 2 / 100);

        }
        // 5.1.02.04.* (perjalanan dinas) dan lainnya: bebas pajak

        $total = $ppn + $pph21 + $pph22 + $pph23;

        return [
            'ppn' => $ppn,
            'pph21' => $pph21,
            'pph22' => $pph22,
            'pph23' => $pph23,
            'total_pajak' => $total,
        ];
    }
}
