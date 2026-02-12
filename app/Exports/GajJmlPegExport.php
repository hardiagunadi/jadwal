<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Services\GajLaporanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
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
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DAFTAR ISIAN JUMLAH PEGAWAI');
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'PER GOLONGAN DAN JABATAN');
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'KANTOR : ' . $gaj->nama_satker);
        $sheet->mergeCells('A5:F5');
        $sheet->setCellValue('A5', 'BULAN : ' . strtoupper($gaj->periode));

        foreach (['A1', 'A2', 'A3', 'A5'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true);
            $sheet->getStyle($c)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Header kolom
        $sheet->setCellValue('A6', 'GOLONGAN');
        $sheet->mergeCells('B6:E6');
        $sheet->setCellValue('B6', 'E S E L O N');
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

        $r = 9;
        $groups = [
            'IV' => [['IV/E', 'IV/E'], ['IV/D', 'IV/D'], ['IV/C', 'IV/C'], ['IV/B', 'IV/B'], ['IV/A', 'IV/A']],
            'III' => [['III/D', 'III/D'], ['III/C', 'III/C'], ['III/B', 'III/B'], ['III/A', 'III/A']],
            'II' => [['II/D', 'II/D'], ['II/C', 'II/C'], ['II/B', 'II/B'], ['II/A', 'II/A']],
            'I' => [['I/D', 'I/D'], ['I/C', 'I/C'], ['I/B', 'I/B'], ['I/A', 'I/A']],
        ];
        $totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($groups as $groupName => $gols) {
            $groupTotal = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            foreach ($gols as [$key, $label]) {
                $d = $data[$key] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0];
                $rowTotal = array_sum($d);
                $sheet->setCellValue("A{$r}", $label);
                $sheet->setCellValue("B{$r}", $d[1] > 0 ? $d[1] : '-');
                $sheet->setCellValue("C{$r}", $d[2] > 0 ? $d[2] : '-');
                $sheet->setCellValue("D{$r}", $d[3] > 0 ? $d[3] : '-');
                $sheet->setCellValue("E{$r}", $d[4] > 0 ? $d[4] : '-');
                $sheet->setCellValue("F{$r}", $rowTotal > 0 ? $rowTotal : '-');
                $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                foreach ([1, 2, 3, 4] as $e) { $groupTotal[$e] += $d[$e]; }
                $r++;
            }
            // Subtotal
            $gt = array_sum($groupTotal);
            $sheet->setCellValue("A{$r}", "Jumlah Golongan {$groupName}");
            $sheet->setCellValue("B{$r}", $groupTotal[1] > 0 ? $groupTotal[1] : '-');
            $sheet->setCellValue("C{$r}", $groupTotal[2] > 0 ? $groupTotal[2] : '-');
            $sheet->setCellValue("D{$r}", $groupTotal[3] > 0 ? $groupTotal[3] : '-');
            $sheet->setCellValue("E{$r}", $groupTotal[4] > 0 ? $groupTotal[4] : '-');
            $sheet->setCellValue("F{$r}", $gt > 0 ? $gt : '-');
            $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
            $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach ([1, 2, 3, 4] as $e) { $totals[$e] += $groupTotal[$e]; }
            $r++;
        }

        // Grand total
        $gt = array_sum($totals);
        $sheet->setCellValue("A{$r}", 'Jumlah I s/d IV');
        $sheet->setCellValue("B{$r}", $totals[1] > 0 ? $totals[1] : '-');
        $sheet->setCellValue("C{$r}", $totals[2] > 0 ? $totals[2] : '-');
        $sheet->setCellValue("D{$r}", $totals[3] > 0 ? $totals[3] : '-');
        $sheet->setCellValue("E{$r}", $totals[4] > 0 ? $totals[4] : '-');
        $sheet->setCellValue("F{$r}", $gt > 0 ? $gt : 0);
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyBorder($sheet, "A6:F{$r}");

        foreach (['A' => 14, 'B' => 8, 'C' => 8, 'D' => 8, 'E' => 8, 'F' => 8] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    // ── PPPK ─────────────────────────────────────────────────────────────────

    private function buildPppk($sheet, Gaj $gaj, array $data): void
    {
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'DAFTAR ISIAN JUMLAH PEGAWAI');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'PER GOLONGAN DAN JABATAN');
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'KANTOR : ' . $gaj->nama_satker);
        $sheet->mergeCells('A5:G5');
        $sheet->setCellValue('A5', 'BULAN : ' . strtoupper($gaj->periode));

        foreach (['A1', 'A2', 'A3', 'A5'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true);
            $sheet->getStyle($c)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Header
        $sheet->setCellValue('A6', 'GOLONGAN');
        $sheet->mergeCells('B6:F6');
        $sheet->setCellValue('B6', 'E S E L O N');
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
                    $sheet->setCellValue("{$col}{$r}", $v > 0 ? $v : '-');
                }
                $sheet->setCellValue("G{$r}", $rowTotal > 0 ? $rowTotal : '-');
                $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                foreach ($cols as $ck) { $groupTotal[$ck] += ($d[$ck] ?? 0); }
                $r++;
            }
            $gt = array_sum($groupTotal);
            $sheet->setCellValue("A{$r}", "Jumlah Golongan {$groupName}");
            foreach (['B' => 'I', 'C' => 'II', 'D' => 'III', 'E' => 'IV', 'F' => 'STAF'] as $col => $ck) {
                $v = $groupTotal[$ck];
                $sheet->setCellValue("{$col}{$r}", $v > 0 ? $v : '-');
            }
            $sheet->setCellValue("G{$r}", $gt > 0 ? $gt : '-');
            $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
            $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach ($cols as $ck) { $totals[$ck] += $groupTotal[$ck]; }
            $r++;
        }

        $gt = array_sum($totals);
        $sheet->setCellValue("A{$r}", 'Jumlah I s/d IV');
        foreach (['B' => 'I', 'C' => 'II', 'D' => 'III', 'E' => 'IV', 'F' => 'STAF'] as $col => $ck) {
            $v = $totals[$ck];
            $sheet->setCellValue("{$col}{$r}", $v > 0 ? $v : '-');
        }
        $sheet->setCellValue("G{$r}", $gt > 0 ? $gt : 0);
        $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyBorder($sheet, "A6:G{$r}");

        foreach (['A' => 14, 'B' => 8, 'C' => 8, 'D' => 8, 'E' => 8, 'F' => 8, 'G' => 8] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    private function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
