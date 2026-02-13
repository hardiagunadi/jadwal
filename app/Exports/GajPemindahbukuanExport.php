<?php

namespace App\Exports;

use App\Models\Gaj;
use App\Models\GajKorpri;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GajPemindahbukuanExport
{
    // ── Rekening tetap ────────────────────────────────────────────────────────
    private const REK_GAJI   = '9023999021';
    private const REK_BAZNAS = '2023062148';
    private const REK_KORPRI = '10020100947';

    // ── Pejabat TTD ──────────────────────────────────────────────────────────
    private const NAMA_CAMAT   = 'SUBUH ONI WIYONO, SE., MM.';
    private const NIP_CAMAT    = 'NIP. 19680331 199603 1 007';
    private const JABATAN_CAMAT = 'Plt. Camat Watumalang';
    private const NAMA_PENYIAP = 'SOLEH';
    private const NIP_PENYIAP  = 'NIP. 19710815 201212 1 001';

    private const BULAN_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // ── Reusable style arrays ─────────────────────────────────────────────────
    private const BORDER_THIN = [
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
        ],
    ];

    private const BORDER_THIN_OUTLINE = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
        ],
    ];

    public function download(Gaj $gaj): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // hapus sheet default

        if ($gaj->jenis === 'pns') {
            $this->buildPns($spreadsheet, $gaj);
        } else {
            $this->buildPppk($spreadsheet, $gaj);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'Pemindahbukuan_' . strtoupper($gaj->jenis) . '_' . $gaj->periode . '.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PNS: 2 sheets
    // ═══════════════════════════════════════════════════════════════════════════

    private function buildPns(Spreadsheet $spreadsheet, Gaj $gaj): void
    {
        $rows = $this->buildRows($gaj);

        $sumBruto  = array_sum(array_column($rows, 'bruto'));
        $sumBaznas = array_sum(array_column($rows, 'baznas'));
        $sumKorpri = array_sum(array_column($rows, 'korpri'));
        $sumBersih = array_sum(array_column($rows, 'bersih'));

        // Sheet 1: PENGANTAR
        $pengantar = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'PENGANTAR');
        $spreadsheet->addSheet($pengantar);
        $this->buildPengantarPns($pengantar, $gaj, $sumBruto + $sumBaznas + $sumKorpri, $sumBersih, $sumBaznas, $sumKorpri);

        // Sheet 2: DATA PRINT
        $dataPrint = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'DATA PRINT');
        $spreadsheet->addSheet($dataPrint);
        $this->buildDataPrintPns($dataPrint, $gaj, $rows);
    }

    private function buildPengantarPns($sheet, Gaj $gaj, int $total, int $sumBersih, int $sumBaznas, int $sumKorpri): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';
        $tahun     = $gaj->tahun;
        $tanggal   = $this->tanggalSurat($gaj->bulan, $gaj->tahun);

        // ── Page Setup ──────────────────────────────────────────────────────
        $this->setPageSetupSurat($sheet, 0.748, 0, 0.83, 0.61);

        // ── Column Widths (A:5, B:9.18, C:2.63, D:16.82, E:7, F:7.73, G:7.54, H:6.82, I:9.45, J:9.18, K:6) ──
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(9.18);
        $sheet->getColumnDimension('C')->setWidth(2.63);
        $sheet->getColumnDimension('D')->setWidth(16.82);
        $sheet->getColumnDimension('E')->setWidth(7);
        $sheet->getColumnDimension('F')->setWidth(7.73);
        $sheet->getColumnDimension('G')->setWidth(7.54);
        $sheet->getColumnDimension('H')->setWidth(6.82);
        $sheet->getColumnDimension('I')->setWidth(9.45);
        $sheet->getColumnDimension('J')->setWidth(9.18);
        $sheet->getColumnDimension('K')->setWidth(6);

        // ── Default font for sheet ──────────────────────────────────────────
        $sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);

        // ── Kop Surat (rows 1-6, each merged A:K) ──────────────────────────
        $this->setKopSurat($sheet, 'K');

        // ── Separator lines rows 7-8 ───────────────────────────────────────
        $sheet->mergeCells('A7:K7');
        $sheet->mergeCells('A8:K8');

        // ── Date ────────────────────────────────────────────────────────────
        $sheet->mergeCells('H9:K9');
        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        // ── Nomor/Lampiran/Perihal (merged A:F) ─────────────────────────────
        $sheet->mergeCells('A11:F11');
        $sheet->mergeCells('A12:F12');
        $sheet->mergeCells('A13:F13');
        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji ASN ' . $bulanNama . ' ' . $tahun);

        // ── Addressee (merged A:F) ──────────────────────────────────────────
        $sheet->mergeCells('A15:F15');
        $sheet->mergeCells('A16:F16');
        $sheet->mergeCells('A17:F17');
        $sheet->setCellValue('A15', 'Yth. Bpk Pemimpin Cabang BANK JATENG');
        $sheet->setCellValue('A16', 'Cabang Wonosobo');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        // ── Body ────────────────────────────────────────────────────────────
        $sheet->mergeCells('A21:E21');
        $sheet->setCellValue('A21', 'Dengan hormat,');

        $sheet->mergeCells('A23:K23');
        $sheet->setCellValue('A23', '     Sehubungan dengan pembayaran gaji ASN bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->mergeCells('A25:K25');

        $sheet->setCellValue('A26', 'Atas nama              :');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        // ── Table header (rows 29-30, 2-row merged headers) ─────────────────
        $sheet->mergeCells('A29:A30');
        $sheet->setCellValue('A29', 'No.');
        $sheet->mergeCells('B29:E30');
        $sheet->setCellValue('B29', 'NAMA REK');
        $sheet->mergeCells('F29:G30');
        $sheet->setCellValue('F29', 'Nomor Rek');
        $sheet->mergeCells('H29:I30');
        $sheet->setCellValue('H29', 'KET');
        $sheet->mergeCells('J29:K30');
        $sheet->setCellValue('J29', 'Jumlah');

        // Style header: bold, center, border
        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A29:K30')->applyFromArray($headerStyle);

        // ── Data row 1: Gaji bersih ─────────────────────────────────────────
        $sheet->setCellValue('A31', '1');
        $sheet->mergeCells('B31:E31');
        $sheet->setCellValue('B31', 'Gaji bersih masuk rek (daft terlamp)');
        $sheet->mergeCells('F31:G31');
        $sheet->setCellValue('F31', self::REK_GAJI);
        $sheet->mergeCells('H31:I31');
        $sheet->setCellValue('H31', 'RP/Gaji');
        $sheet->mergeCells('J31:K31');
        $sheet->setCellValue('J31', $sumBersih);

        // ── Data row 2: BAZNAS ──────────────────────────────────────────────
        $sheet->setCellValue('A32', '2');
        $sheet->mergeCells('B32:E32');
        $sheet->setCellValue('B32', 'BAZNAS');
        $sheet->mergeCells('F32:G32');
        $sheet->setCellValue('F32', self::REK_BAZNAS);
        $sheet->mergeCells('H32:I32');
        $sheet->setCellValue('H32', 'BAZNAS');
        $sheet->mergeCells('J32:K32');
        $sheet->setCellValue('J32', $sumBaznas);

        // ── Data row 3: KORPRI ──────────────────────────────────────────────
        $sheet->setCellValue('A33', '3');
        $sheet->mergeCells('B33:E33');
        $sheet->setCellValue('B33', 'KORPRI KAB. WONOSOBO');
        $sheet->mergeCells('F33:G33');
        $sheet->setCellValue('F33', self::REK_KORPRI);
        $sheet->mergeCells('H33:I33');
        $sheet->setCellValue('H33', 'Bank Wonosobo');
        $sheet->mergeCells('J33:K33');
        $sheet->setCellValue('J33', $sumKorpri);

        // ── Jumlah row ──────────────────────────────────────────────────────
        $sheet->mergeCells('A34:G34');
        $sheet->setCellValue('A34', 'Jumlah');
        $sheet->mergeCells('H34:I34');
        $sheet->mergeCells('J34:K34');
        $sheet->setCellValue('J34', $total);
        $sheet->getStyle('A34:K34')->getFont()->setBold(true);

        // ── Empty row 35 with borders ───────────────────────────────────────
        $sheet->mergeCells('B35:E35');
        $sheet->mergeCells('J35:K35');

        // ── Borders for data rows 31-35 ─────────────────────────────────────
        $sheet->getStyle('A31:K35')->applyFromArray(self::BORDER_THIN);

        // ── Number format & alignment for Jumlah column ─────────────────────
        $sheet->getStyle('J31:K34')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J31:K34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Center No. column ───────────────────────────────────────────────
        $sheet->getStyle('A31:A35')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A34:G34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Closing text ────────────────────────────────────────────────────
        $sheet->setCellValue('A38', '            Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A39', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');

        $sheet->mergeCells('A41:K41');
        $sheet->setCellValue('A41', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        // ── TTD ─────────────────────────────────────────────────────────────
        $this->setTtdSurat($sheet, 43, 'K');

        // ── Vertical alignment default ──────────────────────────────────────
        $sheet->getStyle('A29:A30')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function buildDataPrintPns($sheet, Gaj $gaj, array $rows): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';

        // ── Page Setup ──────────────────────────────────────────────────────
        $this->setPageSetupData($sheet, 0.75, 0.75, 0.41, 0.12);

        // ── Column Widths (A:4.73, B:31.18, C:18, D:14.54, E:11.27, F:11.27, G:13.73) ──
        $sheet->getColumnDimension('A')->setWidth(4.73);
        $sheet->getColumnDimension('B')->setWidth(31.18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(14.54);
        $sheet->getColumnDimension('E')->setWidth(11.27);
        $sheet->getColumnDimension('F')->setWidth(11.27);
        $sheet->getColumnDimension('G')->setWidth(13.73);

        // ── Title rows (merged A:G) ─────────────────────────────────────────
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A1', 'DAFTAR PENERIMAAN GAJI PNS');
        $sheet->setCellValue('A2', strtoupper($gaj->nama_satker));
        $sheet->setCellValue('A3', 'BULAN ' . strtoupper($bulanNama) . ' ' . $gaj->tahun);

        // Title style: bold, center
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Header row 5-6 (2-row merged headers) ──────────────────────────
        // Row 5-6 merged vertically for single-span headers
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'NO');
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'NAMA');
        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('C5', 'NO REKENING');
        $sheet->mergeCells('D5:D6');
        $sheet->setCellValue('D5', 'GAJI BRUTO');
        // E5:F5 merged for "POT LAINYA"
        $sheet->mergeCells('E5:F5');
        $sheet->setCellValue('E5', 'POT LAINYA');
        // G5:G6 merged for GAJI BERSIH
        $sheet->mergeCells('G5:G6');
        $sheet->setCellValue('G5', 'GAJI BERSIH');
        // Sub-headers row 6
        $sheet->setCellValue('E6', 'BAZNAS');
        $sheet->setCellValue('F6', 'DKK KORPRI');

        // Header style: bold, center, vcenter, border
        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A5:G6')->applyFromArray($headerStyle);

        // ── Data rows ───────────────────────────────────────────────────────
        $r = 7;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, $row['nama']);
            $sheet->setCellValue('C' . $r, $row['no_rekening']);
            $sheet->setCellValue('D' . $r, $row['bruto']);
            $sheet->setCellValue('E' . $r, $row['baznas']);
            $sheet->setCellValue('F' . $r, $row['korpri'] ?: null);
            $sheet->setCellValue('G' . $r, $row['bersih']);
            $r++;
        }

        $lastDataRow = $r - 1;

        // ── Data area formatting ────────────────────────────────────────────
        if ($lastDataRow >= 7) {
            // Borders on all data rows
            $sheet->getStyle("A7:G{$lastDataRow}")->applyFromArray(self::BORDER_THIN);
            // Number format for money columns
            $sheet->getStyle("D7:G{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
            // Right-align money columns
            $sheet->getStyle("D7:G{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            // Center NO column
            $sheet->getStyle("A7:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ── Jumlah row ──────────────────────────────────────────────────────
        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->setCellValue('A' . $r, 'JUMLAH');
        $sheet->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        $sheet->setCellValue('D' . $r, array_sum(array_column($rows, 'bruto')));
        $sheet->setCellValue('E' . $r, array_sum(array_column($rows, 'baznas')));
        $sheet->setCellValue('F' . $r, array_sum(array_column($rows, 'korpri')) ?: null);
        $sheet->setCellValue('G' . $r, array_sum(array_column($rows, 'bersih')));
        $sheet->getStyle("D{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("D{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$r}:G{$r}")->applyFromArray(self::BORDER_THIN);
        $sheet->getStyle("A{$r}:C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── TTD ─────────────────────────────────────────────────────────────
        $ttdRow = $r + 3;
        $sheet->setCellValue('F' . $ttdRow, 'Mengetahui,');
        $sheet->setCellValue('F' . ($ttdRow + 1), '  ' . self::JABATAN_CAMAT);
        $sheet->setCellValue('F' . ($ttdRow + 5), self::NAMA_CAMAT);
        $sheet->setCellValue('F' . ($ttdRow + 6), self::NIP_CAMAT);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PPPK: 3 sheets
    // ═══════════════════════════════════════════════════════════════════════════

    private function buildPppk(Spreadsheet $spreadsheet, Gaj $gaj): void
    {
        $rows = $this->buildRows($gaj);

        $sumBruto  = array_sum(array_column($rows, 'bruto'));
        $sumBaznas = array_sum(array_column($rows, 'baznas'));
        $sumKorpri = array_sum(array_column($rows, 'korpri'));
        $sumBersih = array_sum(array_column($rows, 'bersih'));

        // Sheet 1: BANK JATENG
        $bankJateng = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'BANK JATENG');
        $spreadsheet->addSheet($bankJateng);
        $this->buildBankJatengPppk($bankJateng, $gaj, $sumBersih + $sumBaznas, $sumBersih, $sumBaznas);

        // Sheet 2: BAWON
        $bawon = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'BAWON');
        $spreadsheet->addSheet($bawon);
        $this->buildBawonPppk($bawon, $gaj, $sumBruto + $sumBaznas + $sumKorpri, $sumBersih, $sumBaznas, $sumKorpri);

        // Sheet 3: LAMPIRAN
        $lampiran = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'LAMPIRAN');
        $spreadsheet->addSheet($lampiran);
        $this->buildLampiranPppk($lampiran, $gaj, $rows);
    }

    private function buildBankJatengPppk($sheet, Gaj $gaj, int $total, int $sumBersih, int $sumBaznas): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';
        $tahun     = $gaj->tahun;
        $tanggal   = $this->tanggalSurat($gaj->bulan, $gaj->tahun);

        // ── Page Setup ──────────────────────────────────────────────────────
        $this->setPageSetupSurat($sheet, 0.748, 0, 0.83, 0.61);

        // ── Column Widths (same as PNS PENGANTAR) ───────────────────────────
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(9.18);
        $sheet->getColumnDimension('C')->setWidth(2.63);
        $sheet->getColumnDimension('D')->setWidth(16.82);
        $sheet->getColumnDimension('E')->setWidth(7);
        $sheet->getColumnDimension('F')->setWidth(7.73);
        $sheet->getColumnDimension('G')->setWidth(7.54);
        $sheet->getColumnDimension('H')->setWidth(6.82);
        $sheet->getColumnDimension('I')->setWidth(9.45);
        $sheet->getColumnDimension('J')->setWidth(9.18);
        $sheet->getColumnDimension('K')->setWidth(6);

        // ── Kop Surat ──────────────────────────────────────────────────────
        $this->setKopSurat($sheet, 'K');

        // ── Separator lines ─────────────────────────────────────────────────
        $sheet->mergeCells('A7:K7');
        $sheet->mergeCells('A8:K8');

        // ── Date ────────────────────────────────────────────────────────────
        $sheet->mergeCells('H9:K9');
        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        // ── Nomor/Lampiran/Perihal ──────────────────────────────────────────
        $sheet->mergeCells('A11:F11');
        $sheet->mergeCells('A12:F12');
        $sheet->mergeCells('A13:F13');
        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji PPPK Bulan ' . $bulanNama . ' ' . $tahun);

        // ── Addressee ───────────────────────────────────────────────────────
        $sheet->mergeCells('A15:F15');
        $sheet->mergeCells('A16:F16');
        $sheet->mergeCells('A17:F17');
        $sheet->setCellValue('A15', 'Yth. Direktur PT. BPR BANK WONOSOBO');
        $sheet->setCellValue('A16', 'PERSERODA');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        // ── Body ────────────────────────────────────────────────────────────
        $sheet->mergeCells('A21:E21');
        $sheet->setCellValue('A21', 'Dengan hormat,');

        $sheet->mergeCells('A23:K23');
        $sheet->setCellValue('A23', '         Sehubungan  dengan pembayaran  gaji  PPPK bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->mergeCells('A25:K25');

        $sheet->setCellValue('A26', 'Atas nama             : ');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        // ── Table header (rows 29-30, 2-row merged) ─────────────────────────
        $sheet->mergeCells('A29:A30');
        $sheet->setCellValue('A29', 'No.');
        $sheet->mergeCells('B29:E30');
        $sheet->setCellValue('B29', 'NAMA REK');
        $sheet->mergeCells('F29:G30');
        $sheet->setCellValue('F29', 'Nomor Rek');
        $sheet->mergeCells('H29:I30');
        $sheet->setCellValue('H29', 'KET');
        $sheet->mergeCells('J29:K30');
        $sheet->setCellValue('J29', 'Jumlah');

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A29:K30')->applyFromArray($headerStyle);

        // ── Data row 1: Gaji bersih (Bank Wonosobo) ─────────────────────────
        $sheet->setCellValue('A31', '1');
        $sheet->mergeCells('B31:E31');
        $sheet->setCellValue('B31', 'PT BPR BANK WONOSOBO PERSERODA');
        $sheet->mergeCells('F31:G31');
        $sheet->setCellValue('F31', self::REK_GAJI);
        $sheet->mergeCells('H31:I31');
        $sheet->setCellValue('H31', 'RP/Gaji');
        $sheet->mergeCells('J31:K31');
        $sheet->setCellValue('J31', $sumBersih);

        // ── Data row 2: BAZNAS ──────────────────────────────────────────────
        $sheet->setCellValue('A32', '2');
        $sheet->mergeCells('B32:E32');
        $sheet->setCellValue('B32', 'BAZNAS');
        $sheet->mergeCells('F32:G32');
        $sheet->setCellValue('F32', self::REK_BAZNAS);
        $sheet->mergeCells('H32:I32');
        $sheet->setCellValue('H32', 'BAZNAS');
        $sheet->mergeCells('J32:K32');
        $sheet->setCellValue('J32', $sumBaznas);

        // ── Jumlah row ──────────────────────────────────────────────────────
        $sheet->mergeCells('A33:G33');
        $sheet->setCellValue('A33', 'Jumlah');
        $sheet->mergeCells('H33:I33');
        $sheet->mergeCells('J33:K33');
        $sheet->setCellValue('J33', $total);
        $sheet->getStyle('A33:K33')->getFont()->setBold(true);

        // ── Empty row 34 with borders ───────────────────────────────────────
        $sheet->mergeCells('B34:E34');
        $sheet->mergeCells('J34:K34');

        // ── Borders for data rows 31-34 ─────────────────────────────────────
        $sheet->getStyle('A31:K34')->applyFromArray(self::BORDER_THIN);

        // ── Number format & alignment ───────────────────────────────────────
        $sheet->getStyle('J31:K33')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J31:K33')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A31:A34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A33:G33')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Closing text ────────────────────────────────────────────────────
        $sheet->setCellValue('A37', 'Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A38', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');

        $sheet->mergeCells('A40:K40');
        $sheet->setCellValue('A40', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        // ── TTD ─────────────────────────────────────────────────────────────
        $this->setTtdSurat($sheet, 42, 'K');
    }

    private function buildBawonPppk($sheet, Gaj $gaj, int $total, int $sumBersih, int $sumBaznas, int $sumKorpri): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';
        $tahun     = $gaj->tahun;
        $tanggal   = $this->tanggalSurat($gaj->bulan, $gaj->tahun);

        // ── Page Setup ──────────────────────────────────────────────────────
        $this->setPageSetupSurat($sheet, 0.748, 0, 0.83, 0.61);

        // ── Column Widths (same as PENGANTAR) ───────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(9.18);
        $sheet->getColumnDimension('C')->setWidth(2.63);
        $sheet->getColumnDimension('D')->setWidth(16.82);
        $sheet->getColumnDimension('E')->setWidth(7);
        $sheet->getColumnDimension('F')->setWidth(7.73);
        $sheet->getColumnDimension('G')->setWidth(7.54);
        $sheet->getColumnDimension('H')->setWidth(6.82);
        $sheet->getColumnDimension('I')->setWidth(9.45);
        $sheet->getColumnDimension('J')->setWidth(9.18);
        $sheet->getColumnDimension('K')->setWidth(6);

        // ── Kop Surat ──────────────────────────────────────────────────────
        $this->setKopSurat($sheet, 'K');

        // ── Separator lines ─────────────────────────────────────────────────
        $sheet->mergeCells('A7:K7');
        $sheet->mergeCells('A8:K8');

        // ── Date ────────────────────────────────────────────────────────────
        $sheet->mergeCells('H9:K9');
        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        // ── Nomor/Lampiran/Perihal ──────────────────────────────────────────
        $sheet->mergeCells('A11:F11');
        $sheet->mergeCells('A12:F12');
        $sheet->mergeCells('A13:F13');
        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji PPPK Bulan ' . $bulanNama . ' ' . $tahun);

        // ── Addressee ───────────────────────────────────────────────────────
        $sheet->mergeCells('A15:F15');
        $sheet->mergeCells('A16:F16');
        $sheet->mergeCells('A17:F17');
        $sheet->setCellValue('A15', 'Yth. Bpk Pimpinan Cabang BANK JATENG');
        $sheet->setCellValue('A16', 'Cabang Wonosobo');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        // ── Body ────────────────────────────────────────────────────────────
        $sheet->mergeCells('A21:E21');
        $sheet->setCellValue('A21', 'Dengan hormat,');

        $sheet->mergeCells('A23:K23');
        $sheet->setCellValue('A23', '         Sehubungan  dengan pembayaran  gaji  PPPK bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->mergeCells('A25:K25');

        $sheet->setCellValue('A26', 'Atas nama             : ');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        // ── Table header (rows 29-30, 2-row merged) ─────────────────────────
        $sheet->mergeCells('A29:A30');
        $sheet->setCellValue('A29', 'No.');
        $sheet->mergeCells('B29:E30');
        $sheet->setCellValue('B29', 'NAMA REK');
        $sheet->mergeCells('F29:G30');
        $sheet->setCellValue('F29', 'Nomor Rek');
        $sheet->mergeCells('H29:I30');
        $sheet->setCellValue('H29', 'KET');
        $sheet->mergeCells('J29:K30');
        $sheet->setCellValue('J29', 'Jumlah');

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A29:K30')->applyFromArray($headerStyle);

        // ── Data row 1: Gaji bersih ─────────────────────────────────────────
        $sheet->setCellValue('A31', '1');
        $sheet->mergeCells('B31:E31');
        $sheet->setCellValue('B31', 'Gaji bersih masuk rek (daft terlamp)');
        $sheet->mergeCells('F31:G31');
        $sheet->setCellValue('F31', self::REK_GAJI);
        $sheet->mergeCells('H31:I31');
        $sheet->setCellValue('H31', 'RP/Gaji');
        $sheet->mergeCells('J31:K31');
        $sheet->setCellValue('J31', $sumBersih);

        // ── Data row 2: BAZNAS ──────────────────────────────────────────────
        $sheet->setCellValue('A32', '2');
        $sheet->mergeCells('B32:E32');
        $sheet->setCellValue('B32', 'BAZNAS');
        $sheet->mergeCells('F32:G32');
        $sheet->setCellValue('F32', self::REK_BAZNAS);
        $sheet->mergeCells('H32:I32');
        $sheet->setCellValue('H32', 'BAZNAS');
        $sheet->mergeCells('J32:K32');
        $sheet->setCellValue('J32', $sumBaznas);

        // ── Data row 3: KORPRI ──────────────────────────────────────────────
        $sheet->setCellValue('A33', '3');
        $sheet->mergeCells('B33:E33');
        $sheet->setCellValue('B33', 'KORPRI KAB. WONOSOBO');
        $sheet->mergeCells('F33:G33');
        $sheet->setCellValue('F33', self::REK_KORPRI);
        $sheet->mergeCells('H33:I33');
        $sheet->setCellValue('H33', 'Bank Wonosobo');
        $sheet->mergeCells('J33:K33');
        $sheet->setCellValue('J33', $sumKorpri);

        // ── Jumlah row ──────────────────────────────────────────────────────
        $sheet->mergeCells('A34:G34');
        $sheet->setCellValue('A34', 'Jumlah');
        $sheet->mergeCells('H34:I34');
        $sheet->mergeCells('J34:K34');
        $sheet->setCellValue('J34', $total);
        $sheet->getStyle('A34:K34')->getFont()->setBold(true);

        // ── Empty row 35 with borders ───────────────────────────────────────
        $sheet->mergeCells('B35:E35');
        $sheet->mergeCells('J35:K35');

        // ── Borders for data rows 31-35 ─────────────────────────────────────
        $sheet->getStyle('A31:K35')->applyFromArray(self::BORDER_THIN);

        // ── Number format & alignment ───────────────────────────────────────
        $sheet->getStyle('J31:K34')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J31:K34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A31:A35')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A34:G34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Closing text ────────────────────────────────────────────────────
        $sheet->setCellValue('A39', 'Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A40', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');

        $sheet->mergeCells('A42:K42');
        $sheet->setCellValue('A42', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        // ── TTD ─────────────────────────────────────────────────────────────
        $this->setTtdSurat($sheet, 44, 'K');
    }

    private function buildLampiranPppk($sheet, Gaj $gaj, array $rows): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';

        // ── Page Setup ──────────────────────────────────────────────────────
        $this->setPageSetupData($sheet, 0.75, 0.75, 0.41, 0.12);

        // ── Column Widths ───────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(4.73);
        $sheet->getColumnDimension('B')->setWidth(31.18);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14.54);
        $sheet->getColumnDimension('F')->setWidth(11.27);
        $sheet->getColumnDimension('G')->setWidth(11.27);
        $sheet->getColumnDimension('H')->setWidth(13.73);

        // ── Title rows (merged A:H) ─────────────────────────────────────────
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A1', 'DAFTAR PENERIMAAN GAJI PPPK');
        $sheet->setCellValue('A2', strtoupper($gaj->nama_satker));
        $sheet->setCellValue('A3', 'BULAN ' . strtoupper($bulanNama) . ' ' . $gaj->tahun);

        // Title style: bold, center
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Header row 5-6 (2-row merged headers) ──────────────────────────
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'NO');
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'NAMA');
        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('C5', 'BANK PENERIMA');
        $sheet->mergeCells('D5:D6');
        $sheet->setCellValue('D5', 'NO REKENING');
        $sheet->mergeCells('E5:E6');
        $sheet->setCellValue('E5', 'GAJI BRUTO');
        // F5:G5 merged for "POT LAINYA"
        $sheet->mergeCells('F5:G5');
        $sheet->setCellValue('F5', 'POT LAINYA');
        // H5:H6 merged for GAJI BERSIH
        $sheet->mergeCells('H5:H6');
        $sheet->setCellValue('H5', 'GAJI BERSIH');
        // Sub-headers row 6
        $sheet->setCellValue('F6', 'BAZNAS');
        $sheet->setCellValue('G6', 'DKK KORPRI');

        // Header style: bold, center, vcenter, border
        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A5:H6')->applyFromArray($headerStyle);

        // ── Data rows ───────────────────────────────────────────────────────
        $r = 7;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, $row['nama']);
            $sheet->setCellValue('C' . $r, 'Bank Wonosobo');
            $sheet->setCellValue('D' . $r, $row['no_rekening']);
            $sheet->setCellValue('E' . $r, $row['bruto']);
            $sheet->setCellValue('F' . $r, $row['baznas']);
            $sheet->setCellValue('G' . $r, $row['korpri'] ?: null);
            $sheet->setCellValue('H' . $r, $row['bersih']);
            $r++;
        }

        $lastDataRow = $r - 1;

        // ── Data area formatting ────────────────────────────────────────────
        if ($lastDataRow >= 7) {
            $sheet->getStyle("A7:H{$lastDataRow}")->applyFromArray(self::BORDER_THIN);
            $sheet->getStyle("E7:H{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E7:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A7:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ── Jumlah row ──────────────────────────────────────────────────────
        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->setCellValue('A' . $r, 'JUMLAH');
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->setCellValue('E' . $r, array_sum(array_column($rows, 'bruto')));
        $sheet->setCellValue('F' . $r, array_sum(array_column($rows, 'baznas')));
        $sheet->setCellValue('G' . $r, array_sum(array_column($rows, 'korpri')) ?: null);
        $sheet->setCellValue('H' . $r, array_sum(array_column($rows, 'bersih')));
        $sheet->getStyle("E{$r}:H{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$r}:H{$r}")->applyFromArray(self::BORDER_THIN);
        $sheet->getStyle("A{$r}:C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── TTD ─────────────────────────────────────────────────────────────
        $ttdRow = $r + 3;
        $sheet->setCellValue('G' . $ttdRow, 'Mengetahui,');
        $sheet->setCellValue('G' . ($ttdRow + 1), ' ' . self::JABATAN_CAMAT);
        $sheet->setCellValue('G' . ($ttdRow + 5), self::NAMA_CAMAT);
        $sheet->setCellValue('G' . ($ttdRow + 6), self::NIP_CAMAT);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Hitung per-pegawai: bruto = jumlah_bersih (dari SIPD), baznas = 2.5%,
     * korpri = lookup dari tabel gaj_korpris berdasarkan NIP (persistent, tidak perlu entri ulang tiap bulan)
     */
    private function buildRows(Gaj $gaj): array
    {
        // Ambil semua KORPRI sekaligus untuk efisiensi
        $korpriMap = GajKorpri::pluck('jumlah', 'nip');

        return $gaj->pegawais()
            ->orderBy('no_urut')
            ->get()
            ->map(function ($p) use ($korpriMap) {
                $bruto  = (int) $p->jumlah_bersih;
                $baznas = (int) round($bruto * 0.025);
                $korpri = (int) ($korpriMap[$p->nip] ?? 0);
                $bersih = $bruto - $baznas - $korpri;

                return [
                    'nama'        => $p->nama,
                    'no_rekening' => $p->no_rekening,
                    'bruto'       => $bruto,
                    'baznas'      => $baznas,
                    'korpri'      => $korpri,
                    'bersih'      => $bersih,
                ];
            })
            ->toArray();
    }

    /**
     * Page setup for surat/pengantar sheets (Legal, Portrait).
     */
    private function setPageSetupSurat($sheet, float $top, float $bottom, float $left, float $right): void
    {
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageMargins()
            ->setTop($top)
            ->setBottom($bottom)
            ->setLeft($left)
            ->setRight($right);
    }

    /**
     * Page setup for data/lampiran sheets (Legal, Portrait).
     */
    private function setPageSetupData($sheet, float $top, float $bottom, float $left, float $right): void
    {
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageMargins()
            ->setTop($top)
            ->setBottom($bottom)
            ->setLeft($left)
            ->setRight($right);
    }

    /**
     * Kop surat: 6 rows, each merged from A to $lastCol.
     */
    private function setKopSurat($sheet, string $lastCol): void
    {
        // Merge each kop row across all columns
        for ($i = 1; $i <= 6; $i++) {
            $sheet->mergeCells("A{$i}:{$lastCol}{$i}");
        }

        $sheet->setCellValue('A1', 'PEMERINTAH KABUPATEN WONOSOBO');
        $sheet->setCellValue('A2', 'KECAMATAN WATUMALANG');
        $sheet->setCellValue('A3', 'Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352');
        $sheet->setCellValue('A4', 'Telpon ( 0286 ) 3304957');
        $sheet->setCellValue('A5', 'Laman: kecamatanwatumalang.wonosobokab.go.id');
        $sheet->setCellValue('A6', 'Pos-el watumalang08@gmail.com');

        // Style: center, bold for header lines
        $sheet->getStyle("A1:{$lastCol}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A3:{$lastCol}6")->getFont()->setSize(9);
    }

    /**
     * TTD block for surat sheets: Mengetahui + Penyiap, using merged columns.
     * $startRow = row for "Mengetahui," line
     * $lastCol  = last column letter (K for surat sheets)
     */
    private function setTtdSurat($sheet, int $startRow, string $lastCol): void
    {
        $rMengetahui = $startRow;
        $rJabatan    = $startRow + 1;
        $rKab        = $startRow + 2;
        $rNama       = $startRow + 6;
        $rNip        = $startRow + 7;

        // Left side: Mengetahui (merged A:E)
        $sheet->mergeCells("A{$rMengetahui}:E{$rMengetahui}");
        $sheet->setCellValue("A{$rMengetahui}", 'Mengetahui,');

        $sheet->mergeCells("A{$rJabatan}:E{$rJabatan}");
        $sheet->setCellValue("A{$rJabatan}", self::JABATAN_CAMAT);

        $sheet->mergeCells("A{$rKab}:E{$rKab}");
        $sheet->setCellValue("A{$rKab}", 'Kabupaten Wonosobo');

        $sheet->mergeCells("A{$rNama}:E{$rNama}");
        $sheet->setCellValue("A{$rNama}", self::NAMA_CAMAT);
        $sheet->getStyle("A{$rNama}")->getFont()->setBold(true)->setUnderline(true);

        $sheet->mergeCells("A{$rNip}:E{$rNip}");
        $sheet->setCellValue("A{$rNip}", self::NIP_CAMAT);

        // Right side: Penyiap Dokumen (merged H:K)
        $sheet->mergeCells("H{$rMengetahui}:{$lastCol}{$rMengetahui}");
        $sheet->setCellValue("H{$rMengetahui}", 'Penyiap Dokumen Gaji');

        $sheet->mergeCells("H{$rJabatan}:{$lastCol}{$rJabatan}");
        $sheet->mergeCells("H{$rKab}:{$lastCol}{$rKab}");

        $sheet->mergeCells("H{$rNama}:{$lastCol}{$rNama}");
        $sheet->setCellValue("H{$rNama}", self::NAMA_PENYIAP);
        $sheet->getStyle("H{$rNama}")->getFont()->setBold(true)->setUnderline(true);

        $sheet->mergeCells("H{$rNip}:{$lastCol}{$rNip}");
        $sheet->setCellValue("H{$rNip}", self::NIP_PENYIAP);

        // Center both sides
        $sheet->getStyle("A{$rMengetahui}:E{$rNip}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$rMengetahui}:{$lastCol}{$rNip}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function tanggalSurat(int $bulan, int $tahun): string
    {
        $hari      = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $bulanNama = self::BULAN_NAMES[$bulan] ?? '';

        return $hari . ' ' . $bulanNama . ' ' . $tahun;
    }
}
