<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Models\Personil;
use App\Services\GajLaporanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class GajPerbedaanExport
{
    protected GajLaporanService $svc;

    // PNS golongan rows
    protected array $PNS_ROWS = [
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
    protected array $PPPK_ROWS = [
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

    public function __construct()
    {
        $this->svc = app(GajLaporanService::class);
    }

    public function download(Gaj $gaj): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PERBEDAAN');

        $gajLalu = $this->svc->getGajBulanLalu($gaj);
        $dataLalu = $gajLalu ? $this->svc->perbedaanPerGolongan($gajLalu) : [];
        $dataNow  = $this->svc->perbedaanPerGolongan($gaj);

        $isPns = $gaj->jenis === 'pns';
        $rows  = $isPns ? $this->PNS_ROWS : $this->PPPK_ROWS;

        $this->buildSheet($sheet, $gaj, $dataLalu, $dataNow, $rows, $isPns);

        $writer   = new Xlsx($spreadsheet);
        $filename = 'Perbedaan_' . strtoupper($gaj->jenis) . '_' . $gaj->periode . '.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function buildSheet($sheet, Gaj $gaj, array $dataLalu, array $dataNow, array $rows, bool $isPns): void
    {
        // ── Page setup (Legal, Landscape, Fit to 1 page) ───────────────────
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LEGAL);
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(1);

        $pageMargins = $sheet->getPageMargins();
        $pageMargins->setTop(0.59);
        $pageMargins->setBottom(0.6);
        $pageMargins->setLeft(1.28);
        $pageMargins->setRight(0.52);

        // ── Column widths (A-W matching template) ──────────────────────────
        $colWidths = [
            'A' => 3.18,  'B' => 15.27, 'C' => 14.36, 'D' => 6,     'E' => 6.36,
            'F' => 6.91,  'G' => 6.09,  'H' => 13.54, 'I' => 6,     'J' => 6.36,
            'K' => 7,     'L' => 6.09,  'M' => 14.18, 'N' => 11,    'O' => 10,
            'P' => 11,    'Q' => 10.54, 'R' => 11,    'S' => 10.54,
            'T' => 11,    'U' => 10.54, 'V' => 11,    'W' => 10.54,
        ];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Default font
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $bulanLaluLabel = $gaj->bulan == 1
            ? Gaj::$bulanLabels[12] . ' ' . ($gaj->tahun - 1)
            : (Gaj::$bulanLabels[$gaj->bulan - 1] ?? '-') . ' ' . $gaj->tahun;

        // ── R1: Title ──────────────────────────────────────────────────────
        $sheet->mergeCells('A1:W1');
        $sheet->setCellValue('A1', 'DAFTAR PERBEDAAN JUMLAH PEGAWAI DAN PEMBAYARAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── R3-R5: Document info ───────────────────────────────────────────
        $sheet->setCellValue('A3', 'GAJI BULAN');
        $sheet->setCellValue('F3', ': ' . strtoupper($gaj->periode));

        $sheet->setCellValue('A4', 'BADAN / DINAS KANTOR');
        $sheet->setCellValue('F4', ': KECAMATAN WATUMALANG');

        $sheet->setCellValue('A5', 'KODE REKENING');
        $sheet->setCellValue('F5', ': 2.01.13.1.1.03.2');

        // ── R6: Group headers ──────────────────────────────────────────────
        $sheet->setCellValue('B6', 'Golongan/');
        $sheet->mergeCells('D6:H6');
        $sheet->setCellValue('D6', 'I. Bulan : ' . $bulanLaluLabel);
        $sheet->getStyle('D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('I6:M6');
        $sheet->setCellValue('I6', 'II. Bulan : ' . $gaj->periode);
        $sheet->getStyle('I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('N6:W6');
        $sheet->setCellValue('N6', 'III. PERBEDAAN');
        $sheet->getStyle('N6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── R7: Column headers ─────────────────────────────────────────────
        $sheet->setCellValue('A7', 'No');
        $sheet->setCellValue('B7', 'Ruang');
        // C7 intentionally blank (part of B column visual grouping)

        // Bulan lalu columns (D-H) - merged D7:D8, E7:E8, etc.
        $singleHeaders = [
            'D' => 'PEG.',  'E' => 'ISTRI', 'F' => 'ANAK',
            'G' => 'JIWA',  'H' => 'JML. KOTOR',
            'I' => 'PEG.',  'J' => 'ISTRI', 'K' => 'ANAK',
            'L' => 'JIWA',  'M' => 'JML. KOTOR',
        ];
        foreach ($singleHeaders as $col => $text) {
            $sheet->mergeCells("{$col}7:{$col}8");
            $sheet->setCellValue("{$col}7", $text);
            $sheet->getStyle("{$col}7")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
        }

        // Perbedaan columns - merged header pairs in R7
        $sheet->mergeCells('N7:O7');
        $sheet->setCellValue('N7', 'PEGAWAI');
        $sheet->getStyle('N7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('P7:Q7');
        $sheet->setCellValue('P7', 'ISTRI');
        $sheet->getStyle('P7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('R7:S7');
        $sheet->setCellValue('R7', 'ANAK');
        $sheet->getStyle('R7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('T7:U7');
        $sheet->setCellValue('T7', 'JIWA');
        $sheet->getStyle('T7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('V7:W7');
        $sheet->setCellValue('V7', 'JUMLAH KOTOR');
        $sheet->getStyle('V7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── R8: TAMBAH / KURANG sub-headers ────────────────────────────────
        $sheet->setCellValue('N8', 'TAMBAH');
        $sheet->setCellValue('O8', 'KURANG');
        $sheet->setCellValue('P8', 'TAMBAH');
        $sheet->setCellValue('Q8', 'KURANG');
        $sheet->setCellValue('R8', 'TAMBAH');
        $sheet->setCellValue('S8', 'KURANG');
        $sheet->setCellValue('T8', 'TAMBAH');
        $sheet->setCellValue('U8', 'KURANG');
        $sheet->setCellValue('V8', 'TAMBAH');
        $sheet->setCellValue('W8', 'KURANG');
        $sheet->getStyle('N8:W8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Merge A7:A8 and B7:B8 for vertical centering
        $sheet->mergeCells('A7:A8');
        $sheet->getStyle('A7')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->mergeCells('B7:B8');
        $sheet->getStyle('B7')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        // C7:C8 (blank column for golongan code)
        $sheet->mergeCells('C7:C8');

        // Bold the entire header area R6:W8
        $sheet->getStyle('A6:W8')->getFont()->setBold(true);

        // ── R9: Column numbers 1-22 ────────────────────────────────────────
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W'];
        foreach ($colLetters as $idx => $col) {
            $sheet->setCellValue("{$col}9", $idx + 1);
            $sheet->getStyle("{$col}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('A9:W9')->getFont()->setSize(8);

        // ── Data rows ─────────────────────────────────────────────────────
        $dataRow = 10;
        $totLalu = ['peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0, 'kotor' => 0];
        $totNow  = $totLalu;

        foreach ($rows as $groupRoman => $golRows) {
            $groupLalu = ['peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0, 'kotor' => 0];
            $groupNow  = $groupLalu;

            foreach ($golRows as $gRow) {
                $lalu = $dataLalu[$gRow['key']] ?? ['peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0, 'kotor' => 0];
                $now  = $dataNow[$gRow['key']]  ?? ['peg' => 0, 'istri' => 0, 'anak' => 0, 'jiwa' => 0, 'kotor' => 0];

                $ruang = $isPns ? $gRow['key'] : ($gRow['ruang'] ?? '');
                $this->writeDataRow($sheet, $dataRow, null, $gRow['label'], $ruang, $lalu, $now);
                $dataRow++;

                foreach (['peg', 'istri', 'anak', 'jiwa', 'kotor'] as $k) {
                    $groupLalu[$k] += $lalu[$k];
                    $groupNow[$k]  += $now[$k];
                }
            }

            // Subtotal golongan
            $this->writeDataRow($sheet, $dataRow, null, "Jumlah Gol {$groupRoman}", '', $groupLalu, $groupNow, true);
            $dataRow++;

            foreach (['peg', 'istri', 'anak', 'jiwa', 'kotor'] as $k) {
                $totLalu[$k] += $groupLalu[$k];
                $totNow[$k]  += $groupNow[$k];
            }
        }

        // Grand total
        $this->writeDataRow($sheet, $dataRow, null, 'JUMLAH', '', $totLalu, $totNow, true);
        $lastDataRow = $dataRow;

        // ── Borders: entire data area A6:W[lastDataRow] ────────────────────
        $sheet->getStyle("A6:W{$lastDataRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ── TTD Section ────────────────────────────────────────────────────
        $bendahara     = Personil::where('jabatan_lainnya', 'Bendahara Pengeluaran')->first();
        $bendaharaGaji = Personil::where('jabatan_lainnya', 'Bendahara Gaji')->first();

        $ttdStart = $lastDataRow + 3;

        $sheet->setCellValue("B" . ($ttdStart + 2), 'Bendahara Pengeluaran');
        $sheet->setCellValue("R" . ($ttdStart + 2), 'Pembantu Bendahara Pengeluaran');
        $sheet->setCellValue("R" . ($ttdStart + 3), 'Untuk Urusan Gaji');

        $sheet->setCellValue("B" . ($ttdStart + 6), $bendahara->nama ?? '—');
        $sheet->getStyle("B" . ($ttdStart + 6))->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue("B" . ($ttdStart + 7), 'NIP. ' . ($bendahara->nip ?? ''));

        $sheet->setCellValue("R" . ($ttdStart + 6), $bendaharaGaji->nama ?? '—');
        $sheet->getStyle("R" . ($ttdStart + 6))->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue("R" . ($ttdStart + 7), 'NIP. ' . ($bendaharaGaji->nip ?? ''));
    }

    private function writeDataRow($sheet, int $row, ?int $no, string $label, string $ruang, array $lalu, array $now, bool $bold = false): void
    {
        if ($no !== null) {
            $sheet->setCellValue("A{$row}", $no);
        }
        $sheet->setCellValue("B{$row}", $label);
        $sheet->setCellValue("C{$row}", $ruang);

        $fmt = fn ($v) => $v > 0 ? $v : '-';

        // Bulan lalu (D-H)
        $sheet->setCellValue("D{$row}", $fmt($lalu['peg']));
        $sheet->setCellValue("E{$row}", $fmt($lalu['istri']));
        $sheet->setCellValue("F{$row}", $fmt($lalu['anak']));
        $sheet->setCellValue("G{$row}", $fmt($lalu['jiwa']));
        $sheet->setCellValue("H{$row}", $lalu['kotor'] > 0 ? $lalu['kotor'] : '-');

        // Bulan berkenaan (I-M)
        $sheet->setCellValue("I{$row}", $fmt($now['peg']));
        $sheet->setCellValue("J{$row}", $fmt($now['istri']));
        $sheet->setCellValue("K{$row}", $fmt($now['anak']));
        $sheet->setCellValue("L{$row}", $fmt($now['jiwa']));
        $sheet->setCellValue("M{$row}", $now['kotor'] > 0 ? $now['kotor'] : '-');

        // Perbedaan: PEGAWAI, ISTRI, ANAK, JIWA, JUMLAH KOTOR (N-W)
        $diff = fn (string $k) => [
            'tambah' => $now[$k] > $lalu[$k] ? $now[$k] - $lalu[$k] : '-',
            'kurang' => $now[$k] < $lalu[$k] ? $lalu[$k] - $now[$k] : '-',
        ];
        $dPeg   = $diff('peg');
        $dIstri = $diff('istri');
        $dAnak  = $diff('anak');
        $dJiwa  = $diff('jiwa');
        $dKotor = $diff('kotor');

        $cols = ['N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];
        $vals = [
            $dPeg['tambah'], $dPeg['kurang'],
            $dIstri['tambah'], $dIstri['kurang'],
            $dAnak['tambah'], $dAnak['kurang'],
            $dJiwa['tambah'], $dJiwa['kurang'],
            $dKotor['tambah'], $dKotor['kurang'],
        ];

        foreach ($cols as $i => $col) {
            $sheet->setCellValue("{$col}{$row}", $vals[$i]);
        }

        // Alignment: center all columns
        $sheet->getStyle("A{$row}:W{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Bold for subtotal/total rows
        if ($bold) {
            $sheet->getStyle("A{$row}:W{$row}")->getFont()->setBold(true);
        }

        // Number format for money columns (H, M, V, W)
        foreach (['H', 'M', 'V', 'W'] as $col) {
            $cellValue = $sheet->getCell("{$col}{$row}")->getValue();
            if (is_int($cellValue) || is_float($cellValue)) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }
        }
    }
}
