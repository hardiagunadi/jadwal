<?php

namespace App\Http\Controllers;

use App\Exports\GajJmlPegExport;
use App\Exports\GajPemindahbukuanExport;
use App\Exports\GajPerbedaanExport;
use App\Exports\GajRincianExport;
use App\Models\Gaj;
use App\Models\GajKorpri;
use App\Models\Personil;
use App\Services\GajLaporanService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GajExportController extends Controller
{
    // PNS golongan rows
    private const PNS_ROWS = [
        'I'   => [
            ['key' => 'I/A', 'label' => 'Gol. I/a'],
            ['key' => 'I/B', 'label' => 'Gol. I/b'],
            ['key' => 'I/C', 'label' => 'Gol. I/c'],
            ['key' => 'I/D', 'label' => 'Gol. I/d'],
        ],
        'II'  => [
            ['key' => 'II/A', 'label' => 'Gol. II/a'],
            ['key' => 'II/B', 'label' => 'Gol. II/b'],
            ['key' => 'II/C', 'label' => 'Gol. II/c'],
            ['key' => 'II/D', 'label' => 'Gol. II/d'],
        ],
        'III' => [
            ['key' => 'III/A', 'label' => 'Gol. III/a'],
            ['key' => 'III/B', 'label' => 'Gol. III/b'],
            ['key' => 'III/C', 'label' => 'Gol. III/c'],
            ['key' => 'III/D', 'label' => 'Gol. III/d'],
        ],
        'IV'  => [
            ['key' => 'IV/A', 'label' => 'Gol. IV/a'],
            ['key' => 'IV/B', 'label' => 'Gol. IV/b'],
            ['key' => 'IV/C', 'label' => 'Gol. IV/c'],
            ['key' => 'IV/D', 'label' => 'Gol. IV/d'],
        ],
    ];

    // PPPK golongan rows (gol 1-16)
    private const PPPK_ROWS = [
        'I'  => [
            ['key' => 'GOL-1', 'label' => 'Gol. I/a',  'ruang' => 'I'],
            ['key' => 'GOL-2', 'label' => 'Gol. I/b',  'ruang' => 'II'],
            ['key' => 'GOL-3', 'label' => 'Gol. I/c',  'ruang' => 'III'],
            ['key' => 'GOL-4', 'label' => 'Gol. I/d',  'ruang' => 'IV'],
        ],
        'II' => [
            ['key' => 'GOL-5', 'label' => 'Gol. II/a', 'ruang' => 'V'],
            ['key' => 'GOL-6', 'label' => 'Gol. II/b', 'ruang' => 'VI'],
            ['key' => 'GOL-7', 'label' => 'Gol. II/c', 'ruang' => 'II/C'],
            ['key' => 'GOL-8', 'label' => 'Gol. II/d', 'ruang' => 'VIII'],
        ],
        'III' => [
            ['key' => 'GOL-9',  'label' => 'Gol. III/a', 'ruang' => 'IX'],
            ['key' => 'GOL-10', 'label' => 'Gol. III/b', 'ruang' => 'X'],
            ['key' => 'GOL-11', 'label' => 'Gol. III/c', 'ruang' => 'XI'],
            ['key' => 'GOL-12', 'label' => 'Gol. III/d', 'ruang' => 'XII'],
        ],
        'IV' => [
            ['key' => 'GOL-13', 'label' => 'Gol. IV/a', 'ruang' => 'IV/A'],
            ['key' => 'GOL-14', 'label' => 'Gol. IV/b', 'ruang' => 'IV/B'],
            ['key' => 'GOL-15', 'label' => 'Gol. IV/c', 'ruang' => 'IV/C'],
            ['key' => 'GOL-16', 'label' => 'Gol. IV/d', 'ruang' => 'IV/D'],
        ],
    ];

    public function perbedaan(Gaj $gaj): View
    {
        $svc     = app(GajLaporanService::class);
        $gajLalu = $svc->getGajBulanLalu($gaj);

        $dataLalu = $gajLalu ? $svc->perbedaanPerGolongan($gajLalu) : [];
        $dataNow  = $svc->perbedaanPerGolongan($gaj);

        $isPns = $gaj->jenis === 'pns';
        $rows  = $isPns ? self::PNS_ROWS : self::PPPK_ROWS;

        $bendahara     = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();
        $bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

        return view('gaj.perbedaan', [
            'gaj'            => $gaj,
            'dataLalu'       => $dataLalu,
            'dataNow'        => $dataNow,
            'rows'           => $rows,
            'namaBendahara'  => $bendahara->nama ?? '—',
            'nipBendahara'   => 'NIP. ' . ($bendahara->nip ?? ''),
            'namaPenyiap'    => $bendaharaGaji->nama ?? '—',
            'nipPenyiap'     => 'NIP. ' . ($bendaharaGaji->nip ?? ''),
        ]);
    }

    public function rincian(Gaj $gaj): View
    {
        $svc    = app(GajLaporanService::class);
        $groups = $svc->rincianPerGolongan($gaj);
        $isPns  = $gaj->jenis === 'pns';

        $bendahara     = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();
        $bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

        return view('gaj.rincian', [
            'gaj'            => $gaj,
            'groups'         => $groups,
            'isPns'          => $isPns,
            'namaBendahara'  => $bendahara->nama ?? '—',
            'nipBendahara'   => 'NIP. ' . ($bendahara->nip ?? ''),
            'namaPembantu'   => $bendaharaGaji->nama ?? '—',
            'nipPembantu'    => 'NIP. ' . ($bendaharaGaji->nip ?? ''),
        ]);
    }

	public function jmlPeg(Gaj $gaj): View
	{
		$svc  = app(GajLaporanService::class);
		$data = $svc->jmlPegPerGolEselon($gaj);
		$isPns = $gaj->jenis === 'pns';

		$bendahara     = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();
		$bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

		return view('gaj.jml-peg', [
			'gaj'            => $gaj,
			'data'           => $data,
			'isPns'          => $isPns,
			'namaBendahara'  => $bendahara->nama ?? '—',
			'nipBendahara'   => 'NIP. ' . ($bendahara->nip ?? ''),
			'namaPembantu'   => $bendaharaGaji->nama ?? '—',        // ✅ DITAMBAHKAN
			'nipPembantu'    => 'NIP. ' . ($bendaharaGaji->nip ?? ''), // ✅ DITAMBAHKAN
		]);
	}

    public function pemindahbukuan(Gaj $gaj): View
    {
        $isPns = $gaj->jenis === 'pns';

        // Build per-pegawai rows: bruto, baznas (2.5%), korpri, bersih
        $korpriMap = GajKorpri::pluck('jumlah', 'nip');
        $allRows = $gaj->pegawais()->with('personil')->orderBy('no_urut')->get()
            ->map(function ($p) use ($korpriMap) {
                $bruto  = (int) $p->jumlah_bersih;
                $baznas = (int) round($bruto * 0.025);
                $korpri = (int) ($korpriMap[$p->nip] ?? 0);
                $bersih = $bruto - $baznas - $korpri;
                return [
                    'nama'        => $p->nama,
                    'no_rekening' => $p->personil->no_rekening ?? '',
                    'kode_bank'   => $p->personil->kode_bank ?? '',
                    'bruto'       => $bruto,
                    'baznas'      => $baznas,
                    'korpri'      => $korpri,
                    'bersih'      => $bersih,
                ];
            })->toArray();

        // For PNS: all rows in one set
        // For PPPK: split by bank
        if ($isPns) {
            $rows = $allRows;
            $sumBruto  = array_sum(array_column($rows, 'bruto'));
            $sumBaznas = array_sum(array_column($rows, 'baznas'));
            $sumKorpri = array_sum(array_column($rows, 'korpri'));
            $sumBersih = array_sum(array_column($rows, 'bersih'));

            $viewData = [
                'rows'     => $rows,
                'sumBruto' => $sumBruto,
                'sumBaznas' => $sumBaznas,
                'sumKorpri' => $sumKorpri,
                'sumBersih' => $sumBersih,
            ];
        } else {
            // Split rows by bank
            $rowsJateng   = array_values(array_filter($allRows, fn ($r) => ($r['kode_bank'] ?? '') !== 'wonosobo'));
            $rowsWonosobo = array_values(array_filter($allRows, fn ($r) => ($r['kode_bank'] ?? '') === 'wonosobo'));

            $sumBaznasJateng  = array_sum(array_column($rowsJateng, 'baznas'));
            $sumKorpriJateng  = array_sum(array_column($rowsJateng, 'korpri'));
            $sumBersihJateng  = array_sum(array_column($rowsJateng, 'bersih'));
            $sumBaznasWonosobo = array_sum(array_column($rowsWonosobo, 'baznas'));
            $sumKorpriWonosobo = array_sum(array_column($rowsWonosobo, 'korpri'));
            $sumBersihWonosobo = array_sum(array_column($rowsWonosobo, 'bersih'));

            // Total surat Jateng: gaji Jateng + BAZNAS Jateng + KORPRI Jateng
            $totalSuratJateng  = $sumBersihJateng + $sumBaznasJateng + $sumKorpriJateng;
            // Total surat Wonosobo: gaji Wonosobo + BAZNAS Wonosobo + KORPRI Wonosobo
            $totalSuratWonosobo = $sumBersihWonosobo + $sumBaznasWonosobo + $sumKorpriWonosobo;

            $viewData = [
                'rows'          => $allRows,
                'rowsJateng'    => $rowsJateng,
                'rowsWonosobo'  => $rowsWonosobo,
                // Totals for all
                'sumBruto'      => array_sum(array_column($allRows, 'bruto')),
                'sumBaznas'     => array_sum(array_column($allRows, 'baznas')),
                'sumKorpri'     => array_sum(array_column($allRows, 'korpri')),
                'sumBersih'     => array_sum(array_column($allRows, 'bersih')),
                // Bank Jateng subtotals
                'sumBrutoJateng'    => array_sum(array_column($rowsJateng, 'bruto')),
                'sumBaznasJateng'   => $sumBaznasJateng,
                'sumKorpriJateng'   => $sumKorpriJateng,
                'sumBersihJateng'   => $sumBersihJateng,
                // Bank Wonosobo subtotals
                'sumBrutoWonosobo'  => array_sum(array_column($rowsWonosobo, 'bruto')),
                'sumBaznasWonosobo' => $sumBaznasWonosobo,
                'sumKorpriWonosobo' => $sumKorpriWonosobo,
                'sumBersihWonosobo' => $sumBersihWonosobo,
                // Total per surat
                'totalSuratJateng'  => $totalSuratJateng,
                'totalSuratWonosobo' => $totalSuratWonosobo,
            ];
        }

        $pa            = Personil::whereIn('jabatan_lainnya', ['PA', 'KPA'])->first();
        $bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

        return view('gaj.pemindahbukuan', array_merge($viewData, [
            'gaj'           => $gaj,
            'isPns'         => $isPns,
            'namaCamat'     => $pa->nama ?? '—',
            'nipCamat'      => 'NIP. ' . ($pa->nip ?? ''),
            'jabatanCamat'  => $pa->jabatan ?? 'Camat Watumalang',
            'namaPenyiap'   => $bendaharaGaji->nama ?? '—',
            'nipPenyiap'    => 'NIP. ' . ($bendaharaGaji->nip ?? ''),
        ]));
    }

    public function excelPerbedaan(Gaj $gaj): StreamedResponse
    {
        return (new GajPerbedaanExport())->download($gaj);
    }

    public function excelRincian(Gaj $gaj): StreamedResponse
    {
        return (new GajRincianExport())->download($gaj);
    }

    public function excelJmlPeg(Gaj $gaj): StreamedResponse
    {
        return (new GajJmlPegExport())->download($gaj);
    }

    public function excelPemindahbukuan(Gaj $gaj): StreamedResponse
    {
        return (new GajPemindahbukuanExport())->download($gaj);
    }
}
