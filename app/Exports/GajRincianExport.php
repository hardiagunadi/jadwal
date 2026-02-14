<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Models\Personil;
use App\Services\GajLaporanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GajRincianExport
{
    protected GajLaporanService $svc;

    private const BULAN_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

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
        $G   = $groups;
        $tot = fn (string $k) => array_sum(array_column($G, $k));

        // ── Page Setup ──────────────────────────────────────────────────────
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

        $pageMargins = $sheet->getPageMargins();
        $pageMargins->setTop(0.55);
        $pageMargins->setBottom(0.75);
        $pageMargins->setLeft(0.73);
        $pageMargins->setRight(0.25);

        // ── Column widths (match template) ──────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(3.54);
        $sheet->getColumnDimension('B')->setWidth(22.73);
        $sheet->getColumnDimension('C')->setWidth(11.45);
        $sheet->getColumnDimension('D')->setWidth(11.45);
        $sheet->getColumnDimension('E')->setWidth(1.36);
        $sheet->getColumnDimension('F')->setWidth(12.45);
        $sheet->getColumnDimension('G')->setWidth(11.18);

        // ── Default font ────────────────────────────────────────────────────
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // ── Header dokumen ──────────────────────────────────────────────────
        // R1: DAFTAR : RINCIAN BELANJA DAN TUNJANGAN PEGAWAI
        $sheet->setCellValue('B1', 'DAFTAR      :     ');
        $sheet->setCellValue('C1', 'RINCIAN BELANJA DAN TUNJANGAN PEGAWAI');
        $sheet->getStyle('B1:C1')->getFont()->setBold(true);

        // R2: PEMBAYARAN GAJI / GAJI SUSULAN / KEKURANGAN GAJI
        $sheet->setCellValue('C2', 'PEMBAYARAN GAJI / GAJI SUSULAN / KEKURANGAN GAJI');
        $sheet->getStyle('C2')->getFont()->setBold(true);

        // R4: SATUAN KERJA
        $sheet->setCellValue('C4', 'SATUAN KERJA');
        $sheet->setCellValue('E4', ':');
        $sheet->setCellValue('F4', $gaj->nama_satker);

        // R5: KODE REKENING
        $sheet->setCellValue('C5', 'KODE REKENING');
        $sheet->setCellValue('E5', ':');
        $sheet->setCellValue('F5', '2.01.13.1.1.03.2');

        // R6: NPWP
        $sheet->setCellValue('C6', 'NPWP');
        $sheet->setCellValue('E6', ':');
        $sheet->setCellValue('F6', '-');

        // R7: KAB./KOTA
        $sheet->setCellValue('C7', 'KAB./KOTA');
        $sheet->setCellValue('E7', ':');
        $sheet->setCellValue('F7', 'WONOSOBO');

        // R8: BULAN
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';
        $sheet->mergeCells('C8:D8');
        $sheet->setCellValue('C8', 'BULAN');
        $sheet->setCellValue('E8', ':');
        $sheet->setCellValue('F8', strtoupper($bulanNama) . ' ' . $gaj->tahun);

        // R10: Jumlah SPM
        $totalSpm = $tot('jml_kotor');
        $sheet->setCellValue('C10', 'Jumlah SPM');
        $sheet->setCellValue('E10', ':');
        $sheet->setCellValue('F10', $totalSpm);
        $sheet->getStyle('F10')->getNumberFormat()->setFormatCode('#,##0');

        // R11: Jumlah Meninggal
        $sheet->setCellValue('C11', 'Jumlah Meninggal');
        $sheet->setCellValue('E11', ':');
        $sheet->setCellValue('F11', '0');

        // ── Table header rows (R13-R15) ─────────────────────────────────────
        // R13: merged headers
        $sheet->mergeCells('A13:A14');
        $sheet->setCellValue('A13', 'No.');
        $sheet->mergeCells('B13:B14');
        $sheet->setCellValue('B13', 'URAIAN');
        $sheet->mergeCells('C13:G13');
        $sheet->setCellValue('C13', 'GOLONGAN');

        // R14: golongan sub-headers
        $sheet->setCellValue('C14', 'I');
        $sheet->mergeCells('D14:E14');
        $sheet->setCellValue('D14', 'II');
        $sheet->setCellValue('F14', 'III');
        $sheet->setCellValue('G14', 'IV');

        // R15: column numbers
        $sheet->setCellValue('A15', '1');
        $sheet->setCellValue('B15', '2');
        $sheet->setCellValue('C15', '3');
        $sheet->mergeCells('D15:E15');
        $sheet->setCellValue('D15', '4');
        $sheet->setCellValue('F15', '5');
        $sheet->setCellValue('G15', '6');

        // Style header rows: bold, center
        $sheet->getStyle('A13:G15')->getFont()->setBold(true);
        $sheet->getStyle('A13:G15')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // ── Data rows (R16 onwards) ─────────────────────────────────────────
        $r = 16;

        // I. JUMLAH ORANG
        $this->row($sheet, $r++, 'I', 'JUMLAH ORANG',
            $G['I']['jiwa'], $G['II']['jiwa'], $G['III']['jiwa'], $G['IV']['jiwa'], true);
        $this->row($sheet, $r++, '', '1. Pegawai',
            $G['I']['peg'], $G['II']['peg'], $G['III']['peg'], $G['IV']['peg']);
        $this->row($sheet, $r++, '', '2. Istri/Suami',
            $G['I']['istri'], $G['II']['istri'], $G['III']['istri'], $G['IV']['istri']);
        $this->row($sheet, $r++, '', '3. Anak',
            $G['I']['anak'], $G['II']['anak'], $G['III']['anak'], $G['IV']['anak']);

        // II. ESELON
        $this->row($sheet, $r++, 'II', 'JUMLAH ORANG',
            $G['I']['eselon_struktural'] + $G['I']['eselon_fungsional'],
            $G['II']['eselon_struktural'] + $G['II']['eselon_fungsional'],
            $G['III']['eselon_struktural'] + $G['III']['eselon_fungsional'],
            $G['IV']['eselon_struktural'] + $G['IV']['eselon_fungsional'], true);
        $this->row($sheet, $r++, '', '1. Struktural',
            $G['I']['eselon_struktural'], $G['II']['eselon_struktural'],
            $G['III']['eselon_struktural'], $G['IV']['eselon_struktural']);
        $this->row($sheet, $r++, '', '2. Fungsional',
            $G['I']['eselon_fungsional'], $G['II']['eselon_fungsional'],
            $G['III']['eselon_fungsional'], $G['IV']['eselon_fungsional']);

        // III. JML GAJI & TUNJ
        $gajTunj = fn ($g) => $g['jml_kotor'] - $g['tunj_beras'];
        $this->rowMoney($sheet, $r++, 'III', 'JML. GAJI & TUNJ.',
            $gajTunj($G['I']), $gajTunj($G['II']), $gajTunj($G['III']), $gajTunj($G['IV']), true);
        $this->rowMoney($sheet, $r++, '', '1. Gaji Pokok',
            $G['I']['gaji_pokok'], $G['II']['gaji_pokok'], $G['III']['gaji_pokok'], $G['IV']['gaji_pokok']);
        $this->rowMoney($sheet, $r++, '', '2. T. Istri/Suami',
            $G['I']['tunj_istri'], $G['II']['tunj_istri'], $G['III']['tunj_istri'], $G['IV']['tunj_istri']);
        $this->rowMoney($sheet, $r++, '', '3. Tunjangan Anak',
            $G['I']['tunj_anak'], $G['II']['tunj_anak'], $G['III']['tunj_anak'], $G['IV']['tunj_anak']);
        $this->rowMoney($sheet, $r++, '', '4. Tunjangan Umum',
            $G['I']['tunj_fung_umum'], $G['II']['tunj_fung_umum'], $G['III']['tunj_fung_umum'], $G['IV']['tunj_fung_umum']);
        $this->rowMoney($sheet, $r++, '', '5. Tunj. Jab. Struktural',
            $G['I']['tunj_eselon'], $G['II']['tunj_eselon'], $G['III']['tunj_eselon'], $G['IV']['tunj_eselon']);
        $this->rowMoney($sheet, $r++, '', '6. Tunj. Fungsional',
            $G['I']['tunj_fungsional'], $G['II']['tunj_fungsional'], $G['III']['tunj_fungsional'], $G['IV']['tunj_fungsional']);
        $this->rowMoney($sheet, $r++, '', '7. Tunj. Khusus / TKD',
            $G['I']['tunj_khusus'] + $G['I']['tkd'],
            $G['II']['tunj_khusus'] + $G['II']['tkd'],
            $G['III']['tunj_khusus'] + $G['III']['tkd'],
            $G['IV']['tunj_khusus'] + $G['IV']['tkd']);

        if ($isPns) {
            $this->rowMoney($sheet, $r++, '', '8. Tunj. Pajak',
                $G['I']['tunj_pajak'], $G['II']['tunj_pajak'], $G['III']['tunj_pajak'], $G['IV']['tunj_pajak']);
            $this->rowMoney($sheet, $r++, '', '9. Pembulatan',
                $G['I']['pembulatan'], $G['II']['pembulatan'], $G['III']['pembulatan'], $G['IV']['pembulatan']);
        } else {
            $this->rowMoney($sheet, $r++, '', '8. Pembulatan',
                $G['I']['pembulatan'], $G['II']['pembulatan'], $G['III']['pembulatan'], $G['IV']['pembulatan']);
        }

        // IV. TUNJ. BERAS
        $this->rowMoney($sheet, $r++, 'IV', 'TUNJ. BERAS',
            $G['I']['tunj_beras'], $G['II']['tunj_beras'], $G['III']['tunj_beras'], $G['IV']['tunj_beras'], true);

        // V. JUMLAH (III+IV) = jml_kotor
        $this->rowMoney($sheet, $r++, 'V', 'JUMLAH (III+IV)',
            $G['I']['jml_kotor'], $G['II']['jml_kotor'], $G['III']['jml_kotor'], $G['IV']['jml_kotor'], true);
        $this->rowMoney($sheet, $r++, '', '1. IWP 1 %',
            $G['I']['pot_iwp_1'], $G['II']['pot_iwp_1'], $G['III']['pot_iwp_1'], $G['IV']['pot_iwp_1']);
        $this->rowMoney($sheet, $r++, '', '2. IWP 8 %',
            $G['I']['pot_iwp_8'], $G['II']['pot_iwp_8'], $G['III']['pot_iwp_8'], $G['IV']['pot_iwp_8']);
        $this->rowMoney($sheet, $r++, '', '3. TAPERUM',
            $G['I']['pot_taperum'], $G['II']['pot_taperum'], $G['III']['pot_taperum'], $G['IV']['pot_taperum']);
        $this->rowMoney($sheet, $r++, '', '4. PPh PASAL 21',
            $G['I']['pot_pajak'], $G['II']['pot_pajak'], $G['III']['pot_pajak'], $G['IV']['pot_pajak']);

        // VI. JML. POTONGAN
        $this->rowMoney($sheet, $r++, 'VI', 'JML. POTONGAN',
            $G['I']['jml_potongan'], $G['II']['jml_potongan'], $G['III']['jml_potongan'], $G['IV']['jml_potongan'], true);

        // VII. JML. BERSIH
        $this->rowMoney($sheet, $r++, 'VII', 'JML. BERSIH',
            $G['I']['jumlah_bersih'], $G['II']['jumlah_bersih'], $G['III']['jumlah_bersih'], $G['IV']['jumlah_bersih'], true);

        $lastDataRow = $r - 1;

        // ── Borders on entire data area (A13:G[lastDataRow]) ────────────────
        $this->applyBorder($sheet, "A13:G{$lastDataRow}");

        // ── TTD Section ─────────────────────────────────────────────────────
        $ttdRow = $lastDataRow + 2;

        // Mengetahui line (right side)
        $sheet->setCellValue("F{$ttdRow}", 'Mengetahui,');

        $ttdRow += 2;
        $sheet->setCellValue("B{$ttdRow}", 'Bendahara Pengeluaran');
        $sheet->setCellValue("G{$ttdRow}", 'Pembantu Bendahara Pengeluaran');

        $ttdRow++;
        $sheet->setCellValue("G{$ttdRow}", 'Untuk Urusan Gaji');

        $bendahara     = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();
        $bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

        $ttdRow += 3; // skip space for signature
        $sheet->setCellValue("B{$ttdRow}", $bendahara->nama ?? '—');
        $sheet->getStyle("B{$ttdRow}")->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue("G{$ttdRow}", $bendaharaGaji->nama ?? '—');
        $sheet->getStyle("G{$ttdRow}")->getFont()->setBold(true)->setUnderline(true);

        $ttdRow++;
        $sheet->setCellValue("B{$ttdRow}", 'NIP. ' . ($bendahara->nip ?? ''));
        $sheet->setCellValue("G{$ttdRow}", 'NIP. ' . ($bendaharaGaji->nip ?? ''));
    }

    /**
     * Write a data row with count values (centered, dash for zero).
     * Columns: A=no, B=label, C=gol I, D:E(merged)=gol II, F=gol III, G=gol IV.
     * No "JUMLAH" column -- template only has 4 golongan columns.
     */
    private function row($sheet, int $r, string $no, string $label, int $v1, int $v2, int $v3, int $v4, bool $isBold = false): void
    {
        $sheet->setCellValue("A{$r}", $no);
        $sheet->setCellValue("B{$r}", $label);

        // Merge D:E for golongan II data
        $sheet->mergeCells("D{$r}:E{$r}");

        $vals = ['C' => $v1, 'D' => $v2, 'F' => $v3, 'G' => $v4];
        foreach ($vals as $col => $v) {
            $sheet->setCellValue("{$col}{$r}", $v > 0 ? $v : '-');
            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        if ($isBold) {
            $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        }
    }

    /**
     * Write a data row with money values (#,##0 format, right-aligned, dash for zero).
     * Columns: A=no, B=label, C=gol I, D:E(merged)=gol II, F=gol III, G=gol IV.
     */
    private function rowMoney($sheet, int $r, string $no, string $label, int $v1, int $v2, int $v3, int $v4, bool $isBold = false): void
    {
        $sheet->setCellValue("A{$r}", $no);
        $sheet->setCellValue("B{$r}", $label);

        // Merge D:E for golongan II data
        $sheet->mergeCells("D{$r}:E{$r}");

        $vals = ['C' => $v1, 'D' => $v2, 'F' => $v3, 'G' => $v4];
        foreach ($vals as $col => $v) {
            if ($v > 0) {
                $sheet->setCellValue("{$col}{$r}", $v);
                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue("{$col}{$r}", '-');
            }
            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        if ($isBold) {
            $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        }
    }

    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
