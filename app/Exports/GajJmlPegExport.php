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

class GajJmlPegExport
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
        $sheet->setTitle('JML. PEG');

        $data  = $this->svc->jmlPegPerGolEselon($gaj);
        $isPns = $gaj->jenis === 'pns';

        $isPns ? $this->buildPns($sheet, $gaj, $data) : $this->buildPppk($sheet, $gaj, $data);

        $writer   = new Xlsx($spreadsheet);
        $filename = 'JmlPeg_' . strtoupper($gaj->jenis) . '_' . $gaj->periode . '.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    // ── PNS ──────────────────────────────────────────────────────────────────

    private function buildPns($sheet, Gaj $gaj, array $data): void
    {
        // ── Page Setup ──────────────────────────────────────────────────────
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageMargins()->setTop(0.75);
        $sheet->getPageMargins()->setBottom(0.75);
        $sheet->getPageMargins()->setLeft(0.37);
        $sheet->getPageMargins()->setRight(0.25);

        // ── Column Widths (template: A=23.54, B-E=9, F=18.63) ──────────
        $sheet->getColumnDimension('A')->setWidth(23.54);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(9);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(9);
        $sheet->getColumnDimension('F')->setWidth(18.63);

        // ── Title Rows (R1-R3) ──────────────────────────────────────────────
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DAFTAR ISIAN JUMLAH PEGAWAI');
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'PER GOLONGAN DAN JABATAN');
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'KANTOR : ' . $gaj->nama_satker);

        foreach (['A1', 'A2', 'A3'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true);
            $sheet->getStyle($c)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ── R5: BULAN ───────────────────────────────────────────────────────
        $sheet->setCellValue('A5', 'BULAN : ' . strtoupper($gaj->periode));

        // ── Header (R6-R8) ──────────────────────────────────────────────────
        $sheet->mergeCells('A6:A7');
        $sheet->setCellValue('A6', 'GOLONGAN');
        $sheet->mergeCells('B6:E6');
        $sheet->setCellValue('B6', 'E S E L O N');
        $sheet->mergeCells('F6:F7');
        $sheet->setCellValue('F6', 'TOTAL');
        $sheet->setCellValue('B7', 'I');
        $sheet->setCellValue('C7', 'II');
        $sheet->setCellValue('D7', 'III');
        $sheet->setCellValue('E7', 'IV');
        $sheet->setCellValue('A8', '1');
        $sheet->setCellValue('B8', '2');
        $sheet->setCellValue('C8', '3');
        $sheet->setCellValue('D8', '4');
        $sheet->setCellValue('E8', '5');
        $sheet->setCellValue('F8', '6');

        $sheet->getStyle('A6:F8')->getFont()->setBold(true);
        $sheet->getStyle('A6:F8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:F8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // ── Data Rows (R9-R30) ──────────────────────────────────────────────
        $r = 9;
        $groups = [
            'IV'  => [['IV/E', 'IV/E'], ['IV/D', 'IV/D'], ['IV/C', 'IV/C'], ['IV/B', 'IV/B'], ['IV/A', 'IV/A']],
            'III' => [['III/D', 'III/D'], ['III/C', 'III/C'], ['III/B', 'III/B'], ['III/A', 'III/A']],
            'II'  => [['II/D', 'II/D'], ['II/C', 'II/C'], ['II/B', 'II/B'], ['II/A', 'II/A']],
            'I'   => [['I/D', 'I/D'], ['I/C', 'I/C'], ['I/B', 'I/B'], ['I/A', 'I/A']],
        ];
        $totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($groups as $groupName => $gols) {
            $groupTotal = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            foreach ($gols as [$key, $label]) {
                $d = $data[$key] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0];
                $rowTotal = array_sum($d);
                $sheet->setCellValue("A{$r}", $label);
                $sheet->setCellValue("B{$r}", $d[1]);
                $sheet->setCellValue("C{$r}", $d[2]);
                $sheet->setCellValue("D{$r}", $d[3]);
                $sheet->setCellValue("E{$r}", $d[4]);
                $sheet->setCellValue("F{$r}", $rowTotal);
                $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                foreach ([1, 2, 3, 4] as $e) {
                    $groupTotal[$e] += $d[$e];
                }
                $r++;
            }
            // Subtotal
            $gt = array_sum($groupTotal);
            $sheet->setCellValue("A{$r}", "Jumlah Golongan {$groupName}");
            $sheet->setCellValue("B{$r}", $groupTotal[1]);
            $sheet->setCellValue("C{$r}", $groupTotal[2]);
            $sheet->setCellValue("D{$r}", $groupTotal[3]);
            $sheet->setCellValue("E{$r}", $groupTotal[4]);
            $sheet->setCellValue("F{$r}", $gt);
            $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
            $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach ([1, 2, 3, 4] as $e) {
                $totals[$e] += $groupTotal[$e];
            }
            $r++;
        }

        // Grand total
        $gt = array_sum($totals);
        $sheet->setCellValue("A{$r}", 'Jumlah I s/d IV');
        $sheet->setCellValue("B{$r}", $totals[1]);
        $sheet->setCellValue("C{$r}", $totals[2]);
        $sheet->setCellValue("D{$r}", $totals[3]);
        $sheet->setCellValue("E{$r}", $totals[4]);
        $sheet->setCellValue("F{$r}", $gt);
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $lastDataRow = $r;

        // ── Borders A6:F{lastDataRow} ───────────────────────────────────────
        $this->applyBorder($sheet, "A6:F{$lastDataRow}");

        // ── TTD Section ─────────────────────────────────────────────────────
        $ttdStart = $lastDataRow + 3; // R33 equivalent (30+3)

        // Right side: name/title
        $sheet->mergeCells("D{$ttdStart}:F{$ttdStart}");

        $ttdRow2 = $ttdStart + 1;
        $sheet->mergeCells("D{$ttdRow2}:F{$ttdRow2}");
        $sheet->setCellValue("D{$ttdRow2}", 'Pembantu Bendahara Pengeluaran');
        $sheet->getStyle("D{$ttdRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ttdRow3 = $ttdStart + 2;
        $sheet->mergeCells("A{$ttdRow3}:B{$ttdRow3}");
        $sheet->setCellValue("A{$ttdRow3}", 'Bendahara Pengeluaran');
        $sheet->getStyle("A{$ttdRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("D{$ttdRow3}:F{$ttdRow3}");
        $sheet->setCellValue("D{$ttdRow3}", 'Untuk Urusan Gaji');
        $sheet->getStyle("D{$ttdRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $bendahara = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();

        // Names row (ttdStart + 5, i.e. R38 equivalent)
        $nameRow = $ttdStart + 5;
        $sheet->mergeCells("A{$nameRow}:B{$nameRow}");
        $sheet->setCellValue("A{$nameRow}", $bendahara->nama ?? '—');
        $sheet->getStyle("A{$nameRow}")->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("A{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // NIP row (ttdStart + 6, i.e. R39 equivalent)
        $nipRow = $ttdStart + 6;
        $sheet->mergeCells("A{$nipRow}:B{$nipRow}");
        $sheet->setCellValue("A{$nipRow}", 'NIP. ' . ($bendahara->nip ?? ''));
        $sheet->getStyle("A{$nipRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // ── PPPK ─────────────────────────────────────────────────────────────────

    private function buildPppk($sheet, Gaj $gaj, array $data): void
    {
        // ── Page Setup ──────────────────────────────────────────────────────
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageMargins()->setTop(0.75);
        $sheet->getPageMargins()->setBottom(0.75);
        $sheet->getPageMargins()->setLeft(0.37);
        $sheet->getPageMargins()->setRight(0.25);

        // ── Column Widths (adjusted for 7 cols: A=23.54, B-E=9, F=9, G=18.63) ─
        $sheet->getColumnDimension('A')->setWidth(23.54);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(9);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(9);
        $sheet->getColumnDimension('F')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(18.63);

        // ── Title Rows (R1-R3) ──────────────────────────────────────────────
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'DAFTAR ISIAN JUMLAH PEGAWAI');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'PER GOLONGAN DAN JABATAN');
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'KANTOR : ' . $gaj->nama_satker);

        foreach (['A1', 'A2', 'A3'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true);
            $sheet->getStyle($c)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ── R5: BULAN ───────────────────────────────────────────────────────
        $sheet->setCellValue('A5', 'BULAN : ' . strtoupper($gaj->periode));

        // ── Header (R6-R8) ──────────────────────────────────────────────────
        $sheet->mergeCells('A6:A7');
        $sheet->setCellValue('A6', 'GOLONGAN');
        $sheet->mergeCells('B6:F6');
        $sheet->setCellValue('B6', 'E S E L O N');
        $sheet->mergeCells('G6:G7');
        $sheet->setCellValue('G6', 'TOTAL');
        $sheet->setCellValue('B7', 'I');
        $sheet->setCellValue('C7', 'II');
        $sheet->setCellValue('D7', 'III');
        $sheet->setCellValue('E7', 'IV');
        $sheet->setCellValue('F7', 'STAF');
        $sheet->setCellValue('A8', '1');
        $sheet->setCellValue('B8', '2');
        $sheet->setCellValue('C8', '3');
        $sheet->setCellValue('D8', '4');
        $sheet->setCellValue('E8', '5');
        $sheet->setCellValue('F8', '6');
        $sheet->setCellValue('G8', '7');

        $sheet->getStyle('A6:G8')->getFont()->setBold(true);
        $sheet->getStyle('A6:G8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:G8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // ── Data Rows ───────────────────────────────────────────────────────
        $r = 9;
        $groups = [
            'IV'  => array_map(fn ($g) => ["GOL-{$g}", "Gol. {$g}"], range(17, 13)),
            'III' => array_map(fn ($g) => ["GOL-{$g}", "Gol. {$g}"], range(12, 9)),
            'II'  => array_map(fn ($g) => ["GOL-{$g}", "Gol. {$g}"], range(8, 5)),
            'I'   => array_map(fn ($g) => ["GOL-{$g}", "Gol. {$g}"], range(4, 1)),
        ];
        $cols   = ['I', 'II', 'III', 'IV', 'STAF'];
        $totals = array_fill_keys($cols, 0);

        foreach ($groups as $groupName => $gols) {
            $groupTotal = array_fill_keys($cols, 0);
            foreach ($gols as [$key, $label]) {
                $d        = $data[$key] ?? array_fill_keys($cols, 0);
                $rowTotal = array_sum($d);
                $sheet->setCellValue("A{$r}", $label);
                foreach (['B' => 'I', 'C' => 'II', 'D' => 'III', 'E' => 'IV', 'F' => 'STAF'] as $col => $ck) {
                    $v = $d[$ck] ?? 0;
                    $sheet->setCellValue("{$col}{$r}", $v);
                }
                $sheet->setCellValue("G{$r}", $rowTotal);
                $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                foreach ($cols as $ck) {
                    $groupTotal[$ck] += ($d[$ck] ?? 0);
                }
                $r++;
            }
            $gt = array_sum($groupTotal);
            $sheet->setCellValue("A{$r}", "Jumlah Golongan {$groupName}");
            foreach (['B' => 'I', 'C' => 'II', 'D' => 'III', 'E' => 'IV', 'F' => 'STAF'] as $col => $ck) {
                $v = $groupTotal[$ck];
                $sheet->setCellValue("{$col}{$r}", $v);
            }
            $sheet->setCellValue("G{$r}", $gt);
            $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
            $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach ($cols as $ck) {
                $totals[$ck] += $groupTotal[$ck];
            }
            $r++;
        }

        $gt = array_sum($totals);
        $sheet->setCellValue("A{$r}", 'Jumlah I s/d IV');
        foreach (['B' => 'I', 'C' => 'II', 'D' => 'III', 'E' => 'IV', 'F' => 'STAF'] as $col => $ck) {
            $v = $totals[$ck];
            $sheet->setCellValue("{$col}{$r}", $v);
        }
        $sheet->setCellValue("G{$r}", $gt);
        $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $lastDataRow = $r;

        // ── Borders A6:G{lastDataRow} ───────────────────────────────────────
        $this->applyBorder($sheet, "A6:G{$lastDataRow}");

        // ── TTD Section ─────────────────────────────────────────────────────
        $ttdStart = $lastDataRow + 3;

        // Right side: name/title
        $sheet->mergeCells("E{$ttdStart}:G{$ttdStart}");

        $ttdRow2 = $ttdStart + 1;
        $sheet->mergeCells("E{$ttdRow2}:G{$ttdRow2}");
        $sheet->setCellValue("E{$ttdRow2}", 'Pembantu Bendahara Pengeluaran');
        $sheet->getStyle("E{$ttdRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ttdRow3 = $ttdStart + 2;
        $sheet->mergeCells("A{$ttdRow3}:B{$ttdRow3}");
        $sheet->setCellValue("A{$ttdRow3}", 'Bendahara Pengeluaran');
        $sheet->getStyle("A{$ttdRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("E{$ttdRow3}:G{$ttdRow3}");
        $sheet->setCellValue("E{$ttdRow3}", 'Untuk Urusan Gaji');
        $sheet->getStyle("E{$ttdRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $bendahara = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();

        // Names row
        $nameRow = $ttdStart + 5;
        $sheet->mergeCells("A{$nameRow}:B{$nameRow}");
        $sheet->setCellValue("A{$nameRow}", $bendahara->nama ?? '—');
        $sheet->getStyle("A{$nameRow}")->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("A{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // NIP row
        $nipRow = $ttdStart + 6;
        $sheet->mergeCells("A{$nipRow}:B{$nipRow}");
        $sheet->setCellValue("A{$nipRow}", 'NIP. ' . ($bendahara->nip ?? ''));
        $sheet->getStyle("A{$nipRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
