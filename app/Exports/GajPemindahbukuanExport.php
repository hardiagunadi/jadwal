<?php

namespace App\Exports;

use App\Models\Gaj;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

    // ── PNS: 2 sheets ────────────────────────────────────────────────────────

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

        $this->setKopSurat($sheet);

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(4);
        $sheet->getColumnDimension('C')->setWidth(4);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(4);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(4);
        $sheet->getColumnDimension('H')->setWidth(4);
        $sheet->getColumnDimension('I')->setWidth(4);
        $sheet->getColumnDimension('J')->setWidth(16);

        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji ASN ' . $bulanNama . ' ' . $tahun);

        $sheet->setCellValue('A15', 'Yth. Bpk Pemimpin Cabang BANK JATENG');
        $sheet->setCellValue('A16', 'Cabang Wonosobo');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        $sheet->setCellValue('A21', 'Dengan hormat,');
        $sheet->setCellValue('A23', '     Sehubungan dengan pembayaran gaji ASN bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->setCellValue('A26', 'Atas nama              :');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        // Header tabel
        $sheet->setCellValue('A29', 'No.');
        $sheet->setCellValue('B29', 'NAMA  REK');
        $sheet->setCellValue('F29', 'Nomor Rek');
        $sheet->setCellValue('H29', 'KET');
        $sheet->setCellValue('J29', 'Jumlah');

        // Data tabel
        $sheet->setCellValue('A31', '1');
        $sheet->setCellValue('B31', 'Gaji bersih masuk rek (daft terlamp)');
        $sheet->setCellValue('F31', self::REK_GAJI);
        $sheet->setCellValue('H31', 'RP/Gaji');
        $sheet->setCellValue('J31', $sumBersih);
        $sheet->getStyle('J31')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A32', '2');
        $sheet->setCellValue('B32', 'BAZNAS');
        $sheet->setCellValue('F32', self::REK_BAZNAS);
        $sheet->setCellValue('H32', 'BAZNAS');
        $sheet->setCellValue('J32', $sumBaznas);
        $sheet->getStyle('J32')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A33', '3');
        $sheet->setCellValue('B33', 'KORPRI KAB. WONOSOBO');
        $sheet->setCellValue('F33', self::REK_KORPRI);
        $sheet->setCellValue('H33', 'Bank Wonosobo');
        $sheet->setCellValue('J33', $sumKorpri);
        $sheet->getStyle('J33')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A34', 'Jumlah');
        $sheet->setCellValue('J34', $total);
        $sheet->getStyle('J34')->getNumberFormat()->setFormatCode('#,##0');

        // Rata kanan kolom J
        $sheet->getStyle('J29:J34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A38', '            Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A39', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');
        $sheet->setCellValue('A41', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        $this->setTtd($sheet, 43, 44, 49, 50, self::JABATAN_CAMAT, 'Kabupaten Wonosobo');
    }

    private function buildDataPrintPns($sheet, Gaj $gaj, array $rows): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(14);

        $sheet->setCellValue('A1', 'DAFTAR PENERIMAAN GAJI PNS');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->setCellValue('A2', strtoupper($gaj->nama_satker));
        $sheet->setCellValue('A3', 'BULAN ' . strtoupper($bulanNama) . ' ' . $gaj->tahun);

        // Header
        $sheet->setCellValue('A5', 'NO');
        $sheet->setCellValue('B5', 'NAMA');
        $sheet->setCellValue('C5', 'NO REKENING');
        $sheet->setCellValue('D5', 'GAJI BRUTO');
        $sheet->setCellValue('E5', 'POT LAINYA');
        $sheet->setCellValue('G5', 'GAJI BERSIH');
        $sheet->setCellValue('E6', 'BAZNAS');
        $sheet->setCellValue('F6', 'DKK  KORPRI');

        $this->styleHeaderRow($sheet, 'A5:G5');
        $this->styleHeaderRow($sheet, 'A6:G6');

        // Data
        $r = 7;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, $row['nama']);
            $sheet->setCellValue('C' . $r, $row['no_rekening']);
            $sheet->setCellValue('D' . $r, $row['bruto']);
            $sheet->setCellValue('E' . $r, $row['baznas']);
            $sheet->setCellValue('F' . $r, $row['korpri'] ?: null);
            $sheet->setCellValue('G' . $r, $row['bersih']);

            $sheet->getStyle('D' . $r . ':G' . $r)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $r . ':G' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $r++;
        }

        // Jumlah
        $sheet->setCellValue('A' . $r, 'JUMLAH');
        $sheet->getStyle('A' . $r)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $r, array_sum(array_column($rows, 'bruto')));
        $sheet->setCellValue('E' . $r, array_sum(array_column($rows, 'baznas')));
        $sheet->setCellValue('F' . $r, array_sum(array_column($rows, 'korpri')) ?: null);
        $sheet->setCellValue('G' . $r, array_sum(array_column($rows, 'bersih')));
        $sheet->getStyle('D' . $r . ':G' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $r . ':G' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $r . ':G' . $r)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        // TTD
        $ttdRow = $r + 4;
        $sheet->setCellValue('F' . $ttdRow, 'Mengetahui,');
        $sheet->setCellValue('F' . ($ttdRow + 1), '  ' . self::JABATAN_CAMAT);
        $sheet->setCellValue('F' . ($ttdRow + 5), self::NAMA_CAMAT);
        $sheet->setCellValue('F' . ($ttdRow + 6), self::NIP_CAMAT);
    }

    // ── PPPK: 3 sheets ───────────────────────────────────────────────────────

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

        $this->setKopSurat($sheet);
        $this->setColumnWidthsSurat($sheet);

        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji PPPK Bulan ' . $bulanNama . ' ' . $tahun);

        $sheet->setCellValue('A15', 'Yth. Direktur PT. BPR BANK WONOSOBO');
        $sheet->setCellValue('A16', 'PERSERODA');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        $sheet->setCellValue('A21', 'Dengan hormat,');
        $sheet->setCellValue('A23', '         Sehubungan  dengan pembayaran  gaji  PPPK bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->setCellValue('A26', 'Atas nama             : ');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        $sheet->setCellValue('A30', 'No.');
        $sheet->setCellValue('B30', 'NAMA  REK');
        $sheet->setCellValue('F30', 'Nomor Rek');
        $sheet->setCellValue('H30', 'KET');
        $sheet->setCellValue('J30', 'Jumlah');

        $sheet->setCellValue('A32', '1');
        $sheet->setCellValue('B32', 'PT BPR BANK WONOSOBO PERSERODA');
        $sheet->setCellValue('F32', self::REK_GAJI);
        $sheet->setCellValue('H32', 'RP/Gaji');
        $sheet->setCellValue('J32', $sumBersih);
        $sheet->getStyle('J32')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A33', '2');
        $sheet->setCellValue('B33', 'BAZNAS');
        $sheet->setCellValue('F33', self::REK_BAZNAS);
        $sheet->setCellValue('H33', 'BAZNAS');
        $sheet->setCellValue('J33', $sumBaznas);
        $sheet->getStyle('J33')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A34', 'Jumlah');
        $sheet->setCellValue('J34', $total);
        $sheet->getStyle('J34')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->getStyle('J30:J34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A38', 'Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A39', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');
        $sheet->setCellValue('A41', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        $this->setTtd($sheet, 43, 44, 49, 50, self::JABATAN_CAMAT, 'Kabupaten Wonosobo');
    }

    private function buildBawonPppk($sheet, Gaj $gaj, int $total, int $sumBersih, int $sumBaznas, int $sumKorpri): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';
        $tahun     = $gaj->tahun;
        $tanggal   = $this->tanggalSurat($gaj->bulan, $gaj->tahun);

        $this->setKopSurat($sheet);
        $this->setColumnWidthsSurat($sheet);

        $sheet->setCellValue('H9', 'Wonosobo, ' . $tanggal);

        $sheet->setCellValue('A11', 'Nomor     :  900/');
        $sheet->setCellValue('A12', 'Lampiran :  1 (satu) Lembar');
        $sheet->setCellValue('A13', 'Perihal     : Pemindahbukuan Gaji PPPK Bulan ' . $bulanNama . ' ' . $tahun);

        $sheet->setCellValue('A15', 'Yth. Bpk Pimpinan Cabang BANK JATENG');
        $sheet->setCellValue('A16', 'Cabang Wonosobo');
        $sheet->setCellValue('A17', 'di      -');
        $sheet->setCellValue('B18', 'WONOSOBO');

        $sheet->setCellValue('A21', 'Dengan hormat,');
        $sheet->setCellValue('A23', '         Sehubungan  dengan pembayaran  gaji  PPPK bulan ' . $bulanNama . ' ' . $tahun . ' bersama ini kami mohon');
        $sheet->setCellValue('A24', 'untuk dipindahbukukan dari rekening gaji kami :');

        $sheet->setCellValue('A26', 'Atas nama             : ');
        $sheet->setCellValue('D26', 'Bendahara Gaji Kantor Kecamatan Watumalang');
        $sheet->setCellValue('A27', 'Jumlah                  : Rp. ' . number_format($total, 0, ',', '.'));
        $sheet->setCellValue('A28', 'Untuk dipindahkan kedalam rekening di bawah ini :');

        $sheet->setCellValue('A30', 'No.');
        $sheet->setCellValue('B30', 'NAMA  REK');
        $sheet->setCellValue('F30', 'Nomor Rek');
        $sheet->setCellValue('H30', 'KET');
        $sheet->setCellValue('J30', 'Jumlah');

        $sheet->setCellValue('A32', '1');
        $sheet->setCellValue('B32', 'Gaji bersih masuk rek (daft terlamp)');
        $sheet->setCellValue('F32', self::REK_GAJI);
        $sheet->setCellValue('H32', 'RP/Gaji');
        $sheet->setCellValue('J32', $sumBersih);
        $sheet->getStyle('J32')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A33', '2');
        $sheet->setCellValue('B33', 'BAZNAS');
        $sheet->setCellValue('F33', self::REK_BAZNAS);
        $sheet->setCellValue('H33', 'BAZNAS');
        $sheet->setCellValue('J33', $sumBaznas);
        $sheet->getStyle('J33')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A34', '3');
        $sheet->setCellValue('B34', 'KORPRI KAB. WONOSOBO');
        $sheet->setCellValue('F34', self::REK_KORPRI);
        $sheet->setCellValue('H34', 'Bank Wonosobo');
        $sheet->setCellValue('J34', $sumKorpri);
        $sheet->getStyle('J34')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValue('A35', 'Jumlah');
        $sheet->setCellValue('J35', $total);
        $sheet->getStyle('J35')->getNumberFormat()->setFormatCode('#,##0');

        $sheet->getStyle('J30:J35')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A39', 'Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami');
        $sheet->setCellValue('A40', 'akan tanggung jawab atas kesalahan penyampaian data tersebut diatas');
        $sheet->setCellValue('A42', 'Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih');

        $this->setTtd($sheet, 44, 45, 50, 51, self::JABATAN_CAMAT, 'Kabupaten Wonosobo');
    }

    private function buildLampiranPppk($sheet, Gaj $gaj, array $rows): void
    {
        $bulanNama = self::BULAN_NAMES[$gaj->bulan] ?? '';

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);

        $sheet->setCellValue('A1', 'DAFTAR PENERIMAAN GAJI PPPK');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->setCellValue('A2', strtoupper($gaj->nama_satker));
        $sheet->setCellValue('A3', 'BULAN ' . strtoupper($bulanNama) . ' ' . $gaj->tahun);

        // Header
        $sheet->setCellValue('A5', 'NO');
        $sheet->setCellValue('B5', 'NAMA');
        $sheet->setCellValue('C5', 'BANK PENERIMA');
        $sheet->setCellValue('D5', 'NO REKENING');
        $sheet->setCellValue('E5', 'GAJI BRUTO');
        $sheet->setCellValue('F5', 'POT LAINYA');
        $sheet->setCellValue('H5', 'GAJI BERSIH');
        $sheet->setCellValue('F6', 'BAZNAS');
        $sheet->setCellValue('G6', 'DKK  KORPRI');

        $this->styleHeaderRow($sheet, 'A5:H5');
        $this->styleHeaderRow($sheet, 'A6:H6');

        // Data
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

            $sheet->getStyle('E' . $r . ':H' . $r)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $r . ':H' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $r++;
        }

        // Jumlah
        $sheet->setCellValue('A' . $r, 'JUMLAH');
        $sheet->getStyle('A' . $r)->getFont()->setBold(true);
        $sheet->setCellValue('E' . $r, array_sum(array_column($rows, 'bruto')));
        $sheet->setCellValue('F' . $r, array_sum(array_column($rows, 'baznas')));
        $sheet->setCellValue('G' . $r, array_sum(array_column($rows, 'korpri')) ?: null);
        $sheet->setCellValue('H' . $r, array_sum(array_column($rows, 'bersih')));
        $sheet->getStyle('E' . $r . ':H' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $r . ':H' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $r . ':H' . $r)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        // TTD
        $ttdRow = $r + 4;
        $sheet->setCellValue('G' . $ttdRow, 'Mengetahui,');
        $sheet->setCellValue('G' . ($ttdRow + 1), ' ' . self::JABATAN_CAMAT);
        $sheet->setCellValue('G' . ($ttdRow + 5), self::NAMA_CAMAT);
        $sheet->setCellValue('G' . ($ttdRow + 6), self::NIP_CAMAT);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Hitung per-pegawai: bruto = jumlah_bersih (dari SIPD), baznas = 2.5%, korpri = 0
     */
    private function buildRows(Gaj $gaj): array
    {
        return $gaj->pegawais()
            ->orderBy('no_urut')
            ->get()
            ->map(function ($p) {
                $bruto  = (int) $p->jumlah_bersih;
                $baznas = (int) round($bruto * 0.025);
                $korpri = 0;
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

    private function setKopSurat($sheet): void
    {
        $sheet->setCellValue('A1', '     PEMERINTAH KABUPATEN WONOSOBO');
        $sheet->setCellValue('A2', '     KECAMATAN WATUMALANG');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->setCellValue('A3', '          Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352');
        $sheet->setCellValue('A4', 'Telpon ( 0286 ) 3304957');
        $sheet->setCellValue('A5', 'Laman: kecamatanwatumalang.wonosobokab.go.id');
        $sheet->setCellValue('A6', 'Pos-el watumalang08@gmail.com');
    }

    private function setColumnWidthsSurat($sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(4);
        $sheet->getColumnDimension('C')->setWidth(4);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(4);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(4);
        $sheet->getColumnDimension('H')->setWidth(4);
        $sheet->getColumnDimension('I')->setWidth(4);
        $sheet->getColumnDimension('J')->setWidth(16);
    }

    private function setTtd($sheet, int $rMengetahui, int $rJabatan, int $rNama, int $rNip, string $jabatan, string $kab): void
    {
        $sheet->setCellValue('A' . $rMengetahui, 'Mengetahui,');
        $sheet->setCellValue('A' . $rJabatan, $jabatan);
        $sheet->setCellValue('A' . ($rJabatan + 1), $kab);
        $sheet->setCellValue('H' . $rMengetahui, 'Penyiap Dokumen Gaji');
        $sheet->setCellValue('A' . $rNama, self::NAMA_CAMAT);
        $sheet->setCellValue('A' . $rNip, self::NIP_CAMAT);
        $sheet->setCellValue('H' . $rNama, self::NAMA_PENYIAP);
        $sheet->setCellValue('H' . $rNip, self::NIP_PENYIAP);
    }

    private function styleHeaderRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function tanggalSurat(int $bulan, int $tahun): string
    {
        $hari      = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $bulanNama = self::BULAN_NAMES[$bulan] ?? '';

        return $hari . ' ' . $bulanNama . ' ' . $tahun;
    }
}
