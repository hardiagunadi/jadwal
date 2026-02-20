<?php

namespace App\Http\Controllers;

use App\Models\Bku;
use App\Models\Personil;
use Illuminate\View\View;

class BkuController extends Controller
{
    public function cetak(Bku $bku): View
    {
        $bku->load(['dpaSubKegiatan.dpa', 'spjs' => fn ($q) => $q->orderBy('tanggal')->with('dpaRincianBelanja')]);

        $pa = Personil::whereIn('jabatan_lainnya', ['PA', 'KPA'])->first();
        $bendahara = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();

        $bulanNama = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
        ];

        // Build rows with running saldo
        $saldo = $bku->penerimaan;
        $totalPenerimaan = $bku->penerimaan;
        $totalPengeluaran = 0;

        // Aggregate taxes from all SPJs
        $totalPph21 = 0;
        $totalPph22 = 0;
        $totalPph23 = 0;
        $totalPpn = 0;

        $rows = [];
        $no = 1;

        // Row 1: Penerimaan
        $subKeg = $bku->dpaSubKegiatan;
        $rows[] = [
            'no' => $no++,
            'tanggal' => '',
            'uraian' => 'Terima dari Bendahara Pengeluaran',
            'kode_rekening' => $subKeg ? $subKeg->kode : '',
            'penerimaan' => $bku->penerimaan,
            'pengeluaran' => 0,
            'saldo' => $saldo,
        ];

        // SPJ rows
        // Group pengeluaran by kode_rekening for the footer breakdown
        $breakdownByKode = [];

        foreach ($bku->spjs as $spj) {
            $pengeluaran = (int) $spj->jumlah;
            $saldo -= $pengeluaran;
            $totalPengeluaran += $pengeluaran;

            $kodeRek = $spj->dpaRincianBelanja->kode_rekening ?? '';

            $rows[] = [
                'no' => $no++,
                'tanggal' => $spj->tanggal?->format('d/m/Y') ?? '',
                'uraian' => $spj->uraian,
                'kode_rekening' => $kodeRek,
                'penerimaan' => 0,
                'pengeluaran' => $pengeluaran,
                'saldo' => $saldo,
            ];

            // Accumulate breakdown
            if ($kodeRek) {
                $breakdownByKode[$kodeRek] = ($breakdownByKode[$kodeRek] ?? 0) + $pengeluaran;
            }

            // Accumulate taxes
            $totalPph21 += (int) $spj->pph21;
            $totalPph22 += (int) $spj->pph22;
            $totalPph23 += (int) $spj->pph23;
            $totalPpn += (int) $spj->ppn;
        }

        // Tax rows
        $taxItems = [
            ['label' => 'Bayar PPh 21', 'amount' => $totalPph21],
            ['label' => 'Bayar PPh 22', 'amount' => $totalPph22],
            ['label' => 'Bayar PPh 23', 'amount' => $totalPph23],
            ['label' => 'Bayar PPN', 'amount' => $totalPpn],
        ];

        foreach ($taxItems as $tax) {
            $saldo -= $tax['amount'];
            $totalPengeluaran += $tax['amount'];

            $rows[] = [
                'no' => $no++,
                'tanggal' => '',
                'uraian' => $tax['label'],
                'kode_rekening' => 'Pajak',
                'penerimaan' => 0,
                'pengeluaran' => $tax['amount'],
                'saldo' => $saldo,
            ];
        }

        // Build uraian breakdown from kode_rekening for the footer
        // Map kode_rekening to short labels
        $breakdownLabels = [];
        foreach ($breakdownByKode as $kode => $amount) {
            $breakdownLabels[] = [
                'label' => $kode,
                'amount' => $amount,
            ];
        }

        return view('bku.cetak', [
            'bku' => $bku,
            'rows' => $rows,
            'totalPenerimaan' => $totalPenerimaan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoAkhir' => $saldo,
            'bulanNama' => $bulanNama[$bku->bulan] ?? '',
            'subKegiatan' => $subKeg,
            'breakdown' => $breakdownLabels,
            'namaPa' => $pa->nama ?? '-',
            'nipPa' => $pa?->nip ? 'NIP. ' . $pa->nip : '',
            'namaBendahara' => $bendahara->nama ?? '-',
            'nipBendahara' => $bendahara?->nip ? 'NIP. ' . $bendahara->nip : '',
        ]);
    }

    public function pemindahbukuan(Bku $bku): View
    {
        $bku->load(['dpaSubKegiatan', 'spjs' => fn ($q) => $q->orderBy('tanggal')->with('penerimas')]);

        $pa = Personil::whereIn('jabatan_lainnya', ['PA', 'KPA'])->first();
        $bendahara = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();

        $bulanNama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // Build rows: each penerima from each SPJ
        $items = [];
        $totalPph21 = 0;
        $totalPph22 = 0;
        $totalPph23 = 0;
        $totalPpn = 0;
        $totalBpjs = 0;

        foreach ($bku->spjs as $spj) {
            $totalPph21 += (int) $spj->pph21;
            $totalPph22 += (int) $spj->pph22;
            $totalPph23 += (int) $spj->pph23;
            $totalPpn += (int) $spj->ppn;

            if ($spj->penerimas->isEmpty()) {
                // If no penerimas, use the SPJ itself as a row
                $items[] = [
                    'nama' => $spj->uraian,
                    'jumlah' => (int) $spj->jumlah_bersih,
                    'no_rekening' => '',
                    'bank' => '',
                ];
            } else {
                foreach ($spj->penerimas as $penerima) {
                    $items[] = [
                        'nama' => $penerima->nama,
                        'jumlah' => (int) $penerima->jumlah,
                        'no_rekening' => $penerima->no_rekening ?? '',
                        'bank' => $penerima->nama_bank ?? '',
                    ];
                }
            }
        }

        $totalItems = array_sum(array_column($items, 'jumlah'));
        $totalAll = $totalItems + $totalPph21 + $totalPph22 + $totalPph23 + $totalPpn + $totalBpjs;

        return view('bku.pemindahbukuan', [
            'bku' => $bku,
            'items' => $items,
            'totalItems' => $totalItems,
            'totalAll' => $totalAll,
            'totalPph21' => $totalPph21,
            'totalPph22' => $totalPph22,
            'totalPph23' => $totalPph23,
            'totalPpn' => $totalPpn,
            'totalBpjs' => $totalBpjs,
            'bulanNama' => $bulanNama[$bku->bulan] ?? '',
            'namaCamat' => $pa->nama ?? '-',
            'nipCamat' => $pa?->nip ? 'NIP. ' . $pa->nip : '',
            'jabatanCamat' => $pa->jabatan ?? 'Camat Watumalang',
            'namaBendahara' => $bendahara->nama ?? '-',
            'nipBendahara' => $bendahara?->nip ? 'NIP. ' . $bendahara->nip : '',
        ]);
    }
}
