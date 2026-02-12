<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Services\GajLaporanService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $bulanLaluLabel = $gaj->bulan == 1
            ? Gaj::$bulanLabels[12] . ' ' . ($gaj->tahun - 1)
            : (Gaj::$bulanLabels[$gaj->bulan - 1] ?? '-') . ' ' . $gaj->tahun;

        // ── Header ───────────────────────────────────────────────────────────
        $sheet->mergeCells('A1:W1');
        $sheet->setCellValue('A1', 'DAFTAR PERBEDAAN JUMLAH PEGAWAI DAN PEMBAYARAN');
        $this->styleTitle($sheet, 'A1');

        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'GAJI BULAN');
        $sheet->mergeCells('F3:W3');
        $sheet->setCellValue('F3', ': ' . strtoupper($gaj->periode));

        $sheet->mergeCells('A4:E4');
        $sheet->setCellValue('A4', 'BADAN / DINAS KANTOR');
        $sheet->mergeCells('F4:W4');
        $sheet->setCellValue('F4', ': ' . $gaj->nama_satker);

        // ── Sub-header ────────────────────────────────────────────────────────
        $r = 6;
        // Row 6: group headers
        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->mergeCells("D{$r}:H{$r}");
        $sheet->setCellValue("D{$r}", 'I. Bulan : ' . $bulanLaluLabel);
        $sheet->mergeCells("I{$r}:M{$r}");
        $sheet->setCellValue("I{$r}", 'II. Bulan : ' . $gaj->periode);
        $sheet->mergeCells("N{$r}:W{$r}");
        $sheet->setCellValue("N{$r}", 'III. PERBEDAAN');

        // Row 7: column headers
        $r = 7;
        $headers = ['No', 'Golongan/', '', 'PEG.', 'ISTRI', 'ANAK', 'JIWA', 'JML. KOTOR',
                    'PEG.', 'ISTRI', 'ANAK', 'JIWA', 'JML. KOTOR',
                    'PEG\nTAMBAH', 'PEG\nKURANG', 'ISTRI\nTAMBAH', 'ISTRI\nKURANG',
                    'ANAK\nTAMBAH', 'ANAK\nKURANG', 'JIWA\nTAMBAH', 'JIWA\nKURANG',
                    'JML.KOTOR\nTAMBAH', 'JML.KOTOR\nKURANG'];
        foreach ($headers as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $sheet->setCellValue("{$col}{$r}", $h);
            $sheet->getStyle("{$col}{$r}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getRowDimension($r)->setRowHeight(30);

        // ── Data rows ─────────────────────────────────────────────────────────
        $dataRow = 10;
        $no = 1;
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
            $no++;

            foreach (['peg', 'istri', 'anak', 'jiwa', 'kotor'] as $k) {
                $totLalu[$k] += $groupLalu[$k];
                $totNow[$k]  += $groupNow[$k];
            }
        }

        // Grand total
        $this->writeDataRow($sheet, $dataRow, null, 'JUMLAH', '', $totLalu, $totNow, true);

        // ── Kolom lebar ───────────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(8);
        foreach (range('D', 'W') as $col) {
            $sheet->getColumnDimension($col)->setWidth(11);
        }
    }

    private function writeDataRow($sheet, int $row, ?int $no, string $label, string $ruang, array $lalu, array $now, bool $bold = false): void
    {
        if ($no !== null) $sheet->setCellValue("A{$row}", $no);
        $sheet->setCellValue("B{$row}", $label);
        $sheet->setCellValue("C{$row}", $ruang);

        $fmt = fn ($v) => $v > 0 ? $v : '-';

        // Bulan lalu
        $sheet->setCellValue("D{$row}", $fmt($lalu['peg']));
        $sheet->setCellValue("E{$row}", $fmt($lalu['istri']));
        $sheet->setCellValue("F{$row}", $fmt($lalu['anak']));
        $sheet->setCellValue("G{$row}", $fmt($lalu['jiwa']));
        $sheet->setCellValue("H{$row}", $lalu['kotor'] > 0 ? $lalu['kotor'] : '-');

        // Bulan berkenaan
        $sheet->setCellValue("I{$row}", $fmt($now['peg']));
        $sheet->setCellValue("J{$row}", $fmt($now['istri']));
        $sheet->setCellValue("K{$row}", $fmt($now['anak']));
        $sheet->setCellValue("L{$row}", $fmt($now['jiwa']));
        $sheet->setCellValue("M{$row}", $now['kotor'] > 0 ? $now['kotor'] : '-');

        // Perbedaan (TAMBAH/KURANG)
        $diff = fn (string $k) => [
            'tambah' => $now[$k] > $lalu[$k] ? $now[$k] - $lalu[$k] : '-',
            'kurang' => $now[$k] < $lalu[$k] ? $lalu[$k] - $now[$k] : '-',
        ];
        $dPeg   = $diff('peg');
        $dIstri = $diff('istri');
        $dAnak  = $diff('anak');
        $dJiwa  = $diff('jiwa');
        $dKotor = $diff('kotor');

        $cols  = ['N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];
        $vals  = [$dPeg['tambah'], $dPeg['kurang'], $dIstri['tambah'], $dIstri['kurang'],
                  $dAnak['tambah'], $dAnak['kurang'], $dJiwa['tambah'], $dJiwa['kurang'],
                  $dKotor['tambah'], $dKotor['kurang']];

        foreach ($cols as $i => $col) {
            $sheet->setCellValue("{$col}{$row}", $vals[$i]);
        }

        // Alignment & bold
        $range = "A{$row}:W{$row}";
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if ($bold) {
            $sheet->getStyle($range)->getFont()->setBold(true);
        }
        // Format angka
        foreach (['H', 'M', 'V', 'W'] as $col) {
            if (is_int($sheet->getCell("{$col}{$row}")->getValue())) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }
        }
    }

    private function styleTitle($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
