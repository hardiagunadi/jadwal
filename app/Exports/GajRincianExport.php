<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Services\GajLaporanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GajRincianExport
{
    protected GajLaporanService $svc;

    public function __construct()
    {
        $this->svc = app(GajLaporanService::class);
    }

    public function download(Gaj $gaj): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RINCIAN');

        $groups = $this->svc->rincianPerGolongan($gaj);
        $isPns  = $gaj->jenis === 'pns';

        $this->buildSheet($sheet, $gaj, $groups, $isPns);

        $writer   = new Xlsx($spreadsheet);
        $filename = 'Rincian_' . strtoupper($gaj->jenis) . '_' . $gaj->periode . '.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function buildSheet($sheet, Gaj $gaj, array $groups, bool $isPns): void
    {
        $G = $groups;
        $tot = fn (string $k) => array_sum(array_column($G, $k));

        // ── Kolom lebar ───────────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(16);

        $money = '#,##0';
        $fmt   = function ($sheet, string $cell, $val) use ($money) {
            $sheet->setCellValue($cell, $val > 0 ? $val : '-');
            if ($val > 0) $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        };

        // ── Header dokumen ───────────────────────────────────────────────────
        $sheet->mergeCells('B1:G1');
        $sheet->setCellValue('A1', '');
        $sheet->setCellValue('B1', 'DAFTAR      :     RINCIAN BELANJA DAN TUNJANGAN PEGAWAI');
        $sheet->mergeCells('B2:G2');
        $sheet->setCellValue('B2', 'PEMBAYARAN GAJI / GAJI SUSULAN / KEKURANGAN GAJI');
        $sheet->getStyle('B1:B2')->getFont()->setBold(true);

        $sheet->setCellValue('C4', 'SATUAN KERJA');
        $sheet->setCellValue('E4', ':');
        $sheet->setCellValue('F4', $gaj->nama_satker);

        $sheet->setCellValue('C5', 'KAB./KOTA');
        $sheet->setCellValue('E5', ':');
        $sheet->setCellValue('F5', 'WONOSOBO');

        $sheet->setCellValue('C6', 'BULAN');
        $sheet->setCellValue('E6', ':');
        $sheet->setCellValue('F6', strtoupper($gaj->periode));

        $totalSpm = $tot('jml_kotor');
        $sheet->setCellValue('C8', 'Jumlah SPM');
        $sheet->setCellValue('E8', ':');
        $sheet->setCellValue('F8', $totalSpm);
        $sheet->getStyle('F8')->getNumberFormat()->setFormatCode($money);

        // ── Sub-header golongan ───────────────────────────────────────────────
        $r = 11;
        $sheet->setCellValue("A{$r}", 'No.');
        $sheet->setCellValue("B{$r}", 'URAIAN');
        $sheet->mergeCells("C{$r}:F{$r}");
        $sheet->setCellValue("C{$r}", 'GOLONGAN');
        $sheet->setCellValue("G{$r}", 'JUMLAH');

        $r = 12;
        $sheet->setCellValue("C{$r}", 'I');
        $sheet->setCellValue("D{$r}", 'II');
        $sheet->setCellValue("E{$r}", 'III');
        $sheet->setCellValue("F{$r}", 'IV');
        $sheet->setCellValue("G{$r}", '(I,II,III,IV)');

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getStyle("{$col}11:{$col}13")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('A11:G13')->getFont()->setBold(true);

        $r = 13;
        $sheet->setCellValue("A{$r}", '1');
        $sheet->setCellValue("B{$r}", '2');
        $sheet->setCellValue("C{$r}", '3');
        $sheet->setCellValue("D{$r}", '4');
        $sheet->setCellValue("E{$r}", '5');
        $sheet->setCellValue("F{$r}", '6');
        $sheet->setCellValue("G{$r}", '7(3+4+5+6)');

        // ── Data rows ─────────────────────────────────────────────────────────
        $r = 14;

        // I. JUMLAH ORANG
        $this->row($sheet, $r++, 'I', 'JUMLAH ORANG',
            $G['I']['jiwa'], $G['II']['jiwa'], $G['III']['jiwa'], $G['IV']['jiwa'], $tot('jiwa'), true);
        $this->row($sheet, $r++, '', '1. Pegawai',
            $G['I']['peg'], $G['II']['peg'], $G['III']['peg'], $G['IV']['peg'], $tot('peg'));
        $this->row($sheet, $r++, '', '2. Istri/Suami',
            $G['I']['istri'], $G['II']['istri'], $G['III']['istri'], $G['IV']['istri'], $tot('istri'));
        $this->row($sheet, $r++, '', '3. Anak',
            $G['I']['anak'], $G['II']['anak'], $G['III']['anak'], $G['IV']['anak'], $tot('anak'));

        // II. ESELON
        $this->row($sheet, $r++, 'II', 'JUMLAH ORANG (ESELON)',
            $G['I']['eselon_struktural'] + $G['I']['eselon_fungsional'],
            $G['II']['eselon_struktural'] + $G['II']['eselon_fungsional'],
            $G['III']['eselon_struktural'] + $G['III']['eselon_fungsional'],
            $G['IV']['eselon_struktural'] + $G['IV']['eselon_fungsional'],
            $tot('eselon_struktural') + $tot('eselon_fungsional'), true);
        $this->row($sheet, $r++, '', '1. Struktural',
            $G['I']['eselon_struktural'], $G['II']['eselon_struktural'],
            $G['III']['eselon_struktural'], $G['IV']['eselon_struktural'], $tot('eselon_struktural'));
        $this->row($sheet, $r++, '', '2. Fungsional',
            $G['I']['eselon_fungsional'], $G['II']['eselon_fungsional'],
            $G['III']['eselon_fungsional'], $G['IV']['eselon_fungsional'], $tot('eselon_fungsional'));

        // III. JML GAJI & TUNJ
        $gajTunj = fn ($g) => $g['jml_kotor'] - $g['tunj_beras'];
        $this->rowMoney($sheet, $r++, 'III', 'JML. GAJI & TUNJ.',
            $gajTunj($G['I']), $gajTunj($G['II']), $gajTunj($G['III']), $gajTunj($G['IV']),
            $gajTunj($G['I']) + $gajTunj($G['II']) + $gajTunj($G['III']) + $gajTunj($G['IV']), true);
        $this->rowMoney($sheet, $r++, '', '1. Gaji Pokok',
            $G['I']['gaji_pokok'], $G['II']['gaji_pokok'], $G['III']['gaji_pokok'], $G['IV']['gaji_pokok'], $tot('gaji_pokok'));
        $this->rowMoney($sheet, $r++, '', '2. T. Istri/Suami',
            $G['I']['tunj_istri'], $G['II']['tunj_istri'], $G['III']['tunj_istri'], $G['IV']['tunj_istri'], $tot('tunj_istri'));
        $this->rowMoney($sheet, $r++, '', '3. Tunjangan Anak',
            $G['I']['tunj_anak'], $G['II']['tunj_anak'], $G['III']['tunj_anak'], $G['IV']['tunj_anak'], $tot('tunj_anak'));
        $this->rowMoney($sheet, $r++, '', '4. Tunjangan Umum',
            $G['I']['tunj_fung_umum'], $G['II']['tunj_fung_umum'], $G['III']['tunj_fung_umum'], $G['IV']['tunj_fung_umum'], $tot('tunj_fung_umum'));
        $this->rowMoney($sheet, $r++, '', '5. Tunj. Jab. Struktural',
            $G['I']['tunj_eselon'], $G['II']['tunj_eselon'], $G['III']['tunj_eselon'], $G['IV']['tunj_eselon'], $tot('tunj_eselon'));
        $this->rowMoney($sheet, $r++, '', '6. Tunj. Fungsional',
            $G['I']['tunj_fungsional'], $G['II']['tunj_fungsional'], $G['III']['tunj_fungsional'], $G['IV']['tunj_fungsional'], $tot('tunj_fungsional'));
        $this->rowMoney($sheet, $r++, '', '7. Tunj. Khusus / TKD',
            $G['I']['tunj_khusus'] + $G['I']['tkd'],
            $G['II']['tunj_khusus'] + $G['II']['tkd'],
            $G['III']['tunj_khusus'] + $G['III']['tkd'],
            $G['IV']['tunj_khusus'] + $G['IV']['tkd'],
            $tot('tunj_khusus') + $tot('tkd'));

        if ($isPns) {
            $this->rowMoney($sheet, $r++, '', '8. Tunj. Pajak',
                $G['I']['tunj_pajak'], $G['II']['tunj_pajak'], $G['III']['tunj_pajak'], $G['IV']['tunj_pajak'], $tot('tunj_pajak'));
            $this->rowMoney($sheet, $r++, '', '9. Pembulatan',
                $G['I']['pembulatan'], $G['II']['pembulatan'], $G['III']['pembulatan'], $G['IV']['pembulatan'], $tot('pembulatan'));
        } else {
            $this->rowMoney($sheet, $r++, '', '8. Pembulatan',
                $G['I']['pembulatan'], $G['II']['pembulatan'], $G['III']['pembulatan'], $G['IV']['pembulatan'], $tot('pembulatan'));
        }

        // IV. TUNJ. BERAS
        $this->rowMoney($sheet, $r++, 'IV', 'TUNJ. BERAS',
            $G['I']['tunj_beras'], $G['II']['tunj_beras'], $G['III']['tunj_beras'], $G['IV']['tunj_beras'], $tot('tunj_beras'), true);

        // V. JUMLAH (III+IV) = jml_kotor
        $this->rowMoney($sheet, $r++, 'V', 'JUMLAH (III+IV)',
            $G['I']['jml_kotor'], $G['II']['jml_kotor'], $G['III']['jml_kotor'], $G['IV']['jml_kotor'], $tot('jml_kotor'), true);
        $this->rowMoney($sheet, $r++, '', '1. IWP 1 %',
            $G['I']['pot_iwp_1'], $G['II']['pot_iwp_1'], $G['III']['pot_iwp_1'], $G['IV']['pot_iwp_1'], $tot('pot_iwp_1'));
        $this->rowMoney($sheet, $r++, '', '2. IWP 8 %',
            $G['I']['pot_iwp_8'], $G['II']['pot_iwp_8'], $G['III']['pot_iwp_8'], $G['IV']['pot_iwp_8'], $tot('pot_iwp_8'));
        $this->rowMoney($sheet, $r++, '', '3. TAPERUM',
            $G['I']['pot_taperum'], $G['II']['pot_taperum'], $G['III']['pot_taperum'], $G['IV']['pot_taperum'], $tot('pot_taperum'));
        $this->rowMoney($sheet, $r++, '', '4. PPh PASAL 21',
            $G['I']['pot_pajak'], $G['II']['pot_pajak'], $G['III']['pot_pajak'], $G['IV']['pot_pajak'], $tot('pot_pajak'));

        // VI. JML. POTONGAN
        $this->rowMoney($sheet, $r++, 'VI', 'JML. POTONGAN',
            $G['I']['jml_potongan'], $G['II']['jml_potongan'], $G['III']['jml_potongan'], $G['IV']['jml_potongan'], $tot('jml_potongan'), true);

        // VII. JML. BERSIH
        $this->rowMoney($sheet, $r++, 'VII', 'JML. BERSIH',
            $G['I']['jumlah_bersih'], $G['II']['jumlah_bersih'], $G['III']['jumlah_bersih'], $G['IV']['jumlah_bersih'], $tot('jumlah_bersih'), true);

        // ── Border keseluruhan ────────────────────────────────────────────────
        $lastRow = $r - 1;
        $this->applyBorder($sheet, "A11:G{$lastRow}");
    }

    private function row($sheet, int $r, string $no, string $label, ...$vals): void
    {
        $isBold = count($vals) > 5 && $vals[5] === true;
        $vals   = array_slice($vals, 0, 5);

        $sheet->setCellValue("A{$r}", $no);
        $sheet->setCellValue("B{$r}", $label);

        $cols = ['C', 'D', 'E', 'F', 'G'];
        foreach ($cols as $i => $col) {
            $v = $vals[$i] ?? 0;
            $sheet->setCellValue("{$col}{$r}", $v > 0 ? $v : '-');
            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        if ($isBold) $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
    }

    private function rowMoney($sheet, int $r, string $no, string $label, ...$vals): void
    {
        $isBold = count($vals) > 5 && $vals[5] === true;
        $vals   = array_slice($vals, 0, 5);

        $sheet->setCellValue("A{$r}", $no);
        $sheet->setCellValue("B{$r}", $label);

        $cols = ['C', 'D', 'E', 'F', 'G'];
        foreach ($cols as $i => $col) {
            $v = $vals[$i] ?? 0;
            if ($v > 0) {
                $sheet->setCellValue("{$col}{$r}", $v);
                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue("{$col}{$r}", '-');
            }
            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        if ($isBold) $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
    }

    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
