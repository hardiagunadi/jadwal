<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemindahbukuan Gaji ASN {{ strtoupper($gaj->jenis) }} {{ $gaj->periode }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: 10mm 16mm; }
        @page :first { size: A4 portrait; }
        
        /* Landscape for lampiran pages */
        .page.lampiran {
            page-break-before: always;
        }
        @page lampiran { size: A4 landscape; margin: 10mm 15mm; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
        }

        .no-print {
            padding: 5px 10px;
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .no-print button {
            padding: 3px 12px;
            background: #1d4ed8;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 8pt;
        }
        .no-print span { font-size: 7.5pt; color: #6b7280; }

        .page {
            padding: 0;
        }
        .page + .page {
            page-break-before: always;
        }

        /* Kop Surat */
        .kop {
            position: relative;
            text-align: center;
            margin-bottom: 2px;
            min-height: 70px;
        }
        .kop .logo {
            position: absolute;
            left: 10px;
            top: 2px;
            width: 60px;
            height: auto;
        }
        .kop h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0;
        }
        .kop h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
        }
        .kop .detail {
            font-size: 8pt;
            margin: 0;
            line-height: 1.4;
        }
        .kop-line {
            border-bottom: 3px solid #000;
            margin-bottom: 2px;
        }
        .kop-line-inner {
            border-bottom: 1px solid #000;
            margin-bottom: 8px;
        }

        /* Surat pengantar */
        .surat-info {
            font-size: 12pt;
            line-height: 1.5;
        }
        .surat-info td {
            padding: 0 2px;
            vertical-align: top;
        }
        .surat-info .label {
            width: 120px;
        }
        .surat-info .sep {
            width: 10px;
        }

        .date-right {
            text-align: right;
            margin: 8px 0;
            font-size: 12pt;
        }

        .addressee {
            margin: 8px 0;
            font-size: 12pt;
            line-height: 1.5;
        }

        .body-text {
            font-size: 12pt;
            line-height: 1.6;
            text-align: justify;
        }
        
        /* Prevent orphaned TTD */
        .body-text + .ttd-area {
            margin-top: 16px;
        }

        /* Tables */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 6px;
            table-layout: auto;
        }
        table.data th,
        table.data td {
            border: 0.5pt solid #000;
            padding: 3px 5px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        table.data th {
            font-weight: bold;
            background: #fff;
            font-size: 10pt;
            padding: 4px 5px;
        }
        table.data td.l { 
            text-align: left; 
            padding-left: 6px;
        }
        table.data td.r { 
            text-align: right; 
            padding-right: 6px;
        }
        .b { font-weight: bold; }

        /* Lampiran title */
        .lampiran-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 10px;
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        /* Landscape page specific styling */
        .page.lampiran {
            page-break-before: always;
        }
        .page.lampiran table.data {
            width: 100%;
        }
        .page.lampiran table.data th,
        .page.lampiran table.data td {
            white-space: normal;
            line-height: 1.3;
        }

        /* TTD area */
        .ttd-area {
            margin-top: 16px;
            font-size: 12pt;
            page-break-inside: avoid;
            min-height: 120px;
        }
        .ttd-area table { width: 100%; }
        .ttd-area td {
            vertical-align: top;
            text-align: center;
            padding: 1px 4px;
        }
        .ttd-area .name {
            font-weight: bold;
            text-decoration: underline;
        }

        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0;
                width: 100%;
            }
            .page {
                page-break-after: always;
                width: 100%;
            }
            .page.lampiran {
                page: lampiran;
            }
            table.data {
                width: 100%;
                max-width: 100%;
            }
            /* Pastikan TTD tidak terpotong */
            .ttd-area {
                page-break-inside: avoid !important;
                orphans: 4;
                widows: 4;
            }
            /* Jika tabel terlalu panjang, pindahkan TTD ke halaman baru */
            .body-text:last-of-type {
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <label style="font-size:8pt;">Tanggal cetak:
            <input type="date" id="tglCetak" style="font-size:8pt; padding:2px 4px;">
        </label>
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('gaj.excel.pemindahbukuan', $gaj) }}" style="padding:3px 12px; background:#16a34a; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:8pt; text-decoration:none;">Unduh Excel</a>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    @php
        $bulanNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $bulanNama = $bulanNames[$gaj->bulan] ?? '';
        $hariTerakhir = cal_days_in_month(CAL_GREGORIAN, $gaj->bulan, $gaj->tahun);
        $tanggal = $hariTerakhir . ' ' . $bulanNama . ' ' . $gaj->tahun;
        $rp = fn(int $v): string => number_format($v, 0, ',', '.');

        $REK_GAJI   = '9023999021';
        $REK_BAZNAS = '2023062148';
        $REK_KORPRI = '10020100947';

        if ($isPns) {
            $totalPns = $sumBersih + $sumBaznas + $sumKorpri;
        } else {
            // Bank Jateng surat: gaji Jateng + transfer ke Bank Wonosobo + BAZNAS all + KORPRI all
            $totalSuratJateng = $sumBersihJateng + $sumBersihWonosobo + $sumBaznas + $sumKorpri;
            // Bank Wonosobo surat: gaji Wonosobo only
            $totalSuratWonosobo = $sumBersihWonosobo;
        }
    @endphp

    @if ($isPns)
    {{-- ============================================================ --}}
    {{-- PNS PAGE 1: SURAT PENGANTAR                                  --}}
    {{-- ============================================================ --}}
    <div class="page">
        {{-- Kop Surat --}}
        <div class="kop">
            <img src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo Wonosobo" class="logo">
            <h2>PEMERINTAH KABUPATEN WONOSOBO</h2>
            <h1>KECAMATAN WATUMALANG</h1>
            <div class="detail">
                Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352<br>
                Telpon ( 0286 ) 3304957<br>
                Laman: kecamatanwatumalang.wonosobokab.go.id<br>
                Pos-el: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="52253326273f333e333c35626a12353f333b3e7c313d3f">[email&#160;protected]</a></a>
            </div>
        </div>
        <div class="kop-line"></div>
        <div class="kop-line-inner"></div>

        {{-- Tanggal --}}
        <div class="date-right"><span class="tgl-cetak-label"></span></div>

        {{-- Nomor / Lampiran / Perihal --}}
        <table class="surat-info">
            <tr>
                <td class="label">Nomor</td>
                <td class="sep">:</td>
                <td>900/</td>
            </tr>
            <tr>
                <td class="label">Lampiran</td>
                <td class="sep">:</td>
                <td>1 (satu) Lembar</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="sep">:</td>
                <td>Pemindahbukuan Gaji ASN {{ $bulanNama }} {{ $gaj->tahun }}</td>
            </tr>
        </table>

        {{-- Addressee --}}
        <div class="addressee">
            Yth. Bpk Pemimpin Cabang BANK JATENG<br>
            Cabang Wonosobo<br>
            di &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WONOSOBO
        </div>

        {{-- Body --}}
        <div class="body-text">
            Dengan hormat,<br><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sehubungan dengan pembayaran gaji ASN bulan {{ $bulanNama }} {{ $gaj->tahun }} bersama ini kami mohon
            untuk dipindahbukukan dari rekening gaji kami :<br><br>

            <table class="surat-info" style="margin-bottom: 4px;">
                <tr>
                    <td class="label">Atas nama</td>
                    <td class="sep">:</td>
                    <td>Bendahara Gaji Kantor Kecamatan Watumalang</td>
                </tr>
                <tr>
                    <td class="label">Jumlah</td>
                    <td class="sep">:</td>
                    <td>Rp. {{ $rp($totalPns) }}</td>
                </tr>
            </table>
            Untuk dipindahkan kedalam rekening di bawah ini :
        </div>

        {{-- Tabel Pemindahbukuan --}}
        <table class="data">
            <thead>
                <tr>
                    <th style="width:30px;">No.</th>
                    <th style="width:auto">NAMA REK</th>
                    <th style="width:110px;">Nomor Rek</th>
                    <th style="width:80px;">KET</th>
                    <th style="width:100px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td class="l">Gaji bersih masuk rek <i>(daftar terlampir)</i></td>
                    <td>{{ $REK_GAJI }}</td>
                    <td>Rp/Gaji</td>
                    <td class="r">{{ $rp($sumBersih) }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td class="l">BAZNAS</td>
                    <td>{{ $REK_BAZNAS }}</td>
                    <td>BAZNAS</td>
                    <td class="r">{{ $rp($sumBaznas) }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td class="l">KORPRI KAB. WONOSOBO</td>
                    <td>{{ $REK_KORPRI }}</td>
                    <td>Bank Wonosobo</td>
                    <td class="r">{{ $rp($sumKorpri) }}</td>
                </tr>
                <tr class="b">
                    <td class="b" colspan="4"><strong>Jumlah</strong></td>
                    <td class="r b"><strong>{{ $rp($totalPns) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Closing text --}}
        <div class="body-text" style="margin-top: 10px;">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami
            akan tanggung jawab atas kesalahan penyampaian data tersebut diatas<br><br>
            Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih
        </div>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;">Mengetahui,</td>
                    <td style="width:50%;">Penyiap Dokumen Gaji</td>
                </tr>
                <tr>
                    <td>{{ $jabatanCamat }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Kabupaten Wonosobo</td>
                    <td></td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                    <td><span class="name">{{ $namaPenyiap }}</span></td>
                </tr>
                <tr>
                    <td>{{ $nipCamat }}</td>
                    <td>{{ $nipPenyiap }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PNS PAGE 2: LAMPIRAN DATA                                    --}}
    {{-- ============================================================ --}}
    <div class="page lampiran">
        <div class="lampiran-title">
            DAFTAR PENERIMAAN GAJI PNS<br>
            {{ strtoupper($gaj->nama_satker) }}<br>
            BULAN {{ strtoupper($bulanNama) }} {{ $gaj->tahun }}
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" style="width:30px;">NO</th>
                    <th rowspan="2" style="width:220px;">NAMA</th>
                    <th rowspan="2" style="width:110px;">NO REKENING</th>
                    <th rowspan="2" style="width:100px;">GAJI BRUTO</th>
                    <th colspan="2">POT LAINYA</th>
                    <th rowspan="2" style="width:100px;">GAJI BERSIH</th>
                </tr>
                <tr>
                    <th style="width:45px;">BAZNAS</th>
                    <th style="width:45px;">DKK KORPRI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="l">{{ $row['nama'] }}</td>
                    <td>{{ $row['no_rekening'] }}</td>
                    <td class="r">{{ $rp($row['bruto']) }}</td>
                    <td class="r">{{ $rp($row['baznas']) }}</td>
                    <td class="r">{{ $rp($row['korpri']) }}</td>
                    <td class="r">{{ $rp($row['bersih']) }}</td>
                </tr>
                @endforeach
                <tr class="b">
                    <td colspan="3"><strong>JUMLAH</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBruto) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBaznas) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumKorpri) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBersih) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;">Mengetahui,</td>
                </tr>
                <tr>
                    <td></td>
                    <td>&nbsp;&nbsp;{{ $jabatanCamat }}</td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td></td>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td>{{ $nipCamat }}</td>
                </tr>
            </table>
        </div>
    </div>

    @else
    {{-- ============================================================ --}}
    {{-- PPPK PAGE 1: SURAT BANK JATENG                               --}}
    {{-- ============================================================ --}}
    <div class="page">
        {{-- Kop Surat --}}
        <div class="kop">
            <img src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo Wonosobo" class="logo">
            <h2>PEMERINTAH KABUPATEN WONOSOBO</h2>
            <h1>KECAMATAN WATUMALANG</h1>
            <div class="detail">
                Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352<br>
                Telpon ( 0286 ) 3304957<br>
                Laman: kecamatanwatumalang.wonosobokab.go.id<br>
                Pos-el: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="285f495c5d45494449464f1810684f45494144064b4745">[email&#160;protected]</a></a>
            </div>
        </div>
        <div class="kop-line"></div>
        <div class="kop-line-inner"></div>

        {{-- Tanggal --}}
        <div class="date-right"><span class="tgl-cetak-label"></span></div>

        {{-- Nomor / Lampiran / Perihal --}}
        <table class="surat-info">
            <tr>
                <td class="label">Nomor</td>
                <td class="sep">:</td>
                <td>900/</td>
            </tr>
            <tr>
                <td class="label">Lampiran</td>
                <td class="sep">:</td>
                <td>2 (dua) Lembar</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="sep">:</td>
                <td>Pemindahbukuan Gaji PPPK Bulan {{ $bulanNama }} {{ $gaj->tahun }}</td>
            </tr>
        </table>

        {{-- Addressee --}}
        <div class="addressee">
            Yth. Bpk Pimpinan Cabang BANK JATENG<br>
            Cabang Wonosobo<br>
            di &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WONOSOBO
        </div>

        {{-- Body --}}
        <div class="body-text">
            Dengan hormat,<br><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sehubungan dengan pembayaran gaji PPPK bulan {{ $bulanNama }} {{ $gaj->tahun }} bersama ini kami mohon
            untuk dipindahbukukan dari rekening gaji kami :<br><br>

            <table class="surat-info" style="margin-bottom: 4px;">
                <tr>
                    <td class="label">Atas nama</td>
                    <td class="sep">:</td>
                    <td>Bendahara Gaji Kantor Kecamatan Watumalang</td>
                </tr>
                <tr>
                    <td class="label">Jumlah</td>
                    <td class="sep">:</td>
                    <td>Rp. {{ $rp($totalSuratJateng) }}</td>
                </tr>
            </table>
            Untuk dipindahkan kedalam rekening di bawah ini :
        </div>

        {{-- Tabel Pemindahbukuan --}}
        <table class="data">
            <thead>
                <tr>
                    <th style="width:30px;">No.</th>
                    <th>NAMA REK</th>
                    <th style="width:110px;">Nomor Rek</th>
                    <th style="width:80px;">KET</th>
                    <th style="width:120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td class="l">Gaji bersih masuk rek Bank Jateng <i>(daftar terlampir)</i></td>
                    <td>{{ $REK_GAJI }}</td>
                    <td>Rp/Gaji</td>
                    <td class="r">{{ $rp($sumBersihJateng) }}</td>
                </tr>
                @if (count($rowsWonosobo) > 0)
                <tr>
                    <td>2</td>
                    <td class="l">Gaji bersih transfer ke Bank Wonosobo</td>
                    <td>-</td>
                    <td>Rp/Gaji</td>
                    <td class="r">{{ $rp($sumBersihWonosobo) }}</td>
                </tr>
                @endif
                @php $no = count($rowsWonosobo) > 0 ? 3 : 2; @endphp
                <tr>
                    <td>{{ $no }}</td>
                    <td class="l">BAZNAS</td>
                    <td>{{ $REK_BAZNAS }}</td>
                    <td>BAZNAS</td>
                    <td class="r">{{ $rp($sumBaznas) }}</td>
                </tr>
                <tr>
                    <td>{{ $no + 1 }}</td>
                    <td class="l">KORPRI KAB. WONOSOBO</td>
                    <td>{{ $REK_KORPRI }}</td>
                    <td>Bank Wonosobo</td>
                    <td class="r">{{ $rp($sumKorpri) }}</td>
                </tr>
                <tr class="b">
                    <td class="b" colspan="4"><strong>Jumlah</strong></td>
                    <td class="r b"><strong>{{ $rp($totalSuratJateng) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Closing text --}}
        <div class="body-text" style="margin-top: 10px;">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami
            akan tanggung jawab atas kesalahan penyampaian data tersebut diatas<br><br>
            Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih
        </div>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;">Mengetahui,</td>
                    <td style="width:50%;">Penyiap Dokumen Gaji</td>
                </tr>
                <tr>
                    <td>{{ $jabatanCamat }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Kabupaten Wonosobo</td>
                    <td></td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                    <td><span class="name">{{ $namaPenyiap }}</span></td>
                </tr>
                <tr>
                    <td>{{ $nipCamat }}</td>
                    <td>{{ $nipPenyiap }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PPPK PAGE 2: LAMPIRAN BANK JATENG                            --}}
    {{-- ============================================================ --}}
    <div class="page lampiran">
        <div class="lampiran-title">
            DAFTAR PENERIMAAN GAJI PPPK — BANK JATENG<br>
            {{ strtoupper($gaj->nama_satker) }}<br>
            BULAN {{ strtoupper($bulanNama) }} {{ $gaj->tahun }}
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" style="width:30px;">NO</th>
                    <th rowspan="2" style="width:220px;">NAMA</th>
                    <th rowspan="2" style="width:110px;">BANK PENERIMA</th>
                    <th rowspan="2" style="width:110px;">NO REKENING</th>
                    <th rowspan="2" style="width:100px;">GAJI BRUTO</th>
                    <th colspan="2">POT LAINYA</th>
                    <th rowspan="2" style="width:100px;">GAJI BERSIH</th>
                </tr>
                <tr>
                    <th style="width:45px;">BAZNAS</th>
                    <th style="width:45px;">DKK KORPRI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rowsJateng as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="l">{{ $row['nama'] }}</td>
                    <td>Bank Jateng</td>
                    <td>{{ $row['no_rekening'] }}</td>
                    <td class="r">{{ $rp($row['bruto']) }}</td>
                    <td class="r">{{ $rp($row['baznas']) }}</td>
                    <td class="r">{{ $rp($row['korpri']) }}</td>
                    <td class="r">{{ $rp($row['bersih']) }}</td>
                </tr>
                @endforeach
                <tr class="b">
                    <td colspan="4"><strong>JUMLAH</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBrutoJateng) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBaznasJateng) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumKorpriJateng) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBersihJateng) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;">Mengetahui,</td>
                </tr>
                <tr>
                    <td></td>
                    <td>&nbsp;{{ $jabatanCamat }}</td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td></td>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td>{{ $nipCamat }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PPPK PAGE 2B: LAMPIRAN BANK WONOSOBO (untuk surat Jateng)   --}}
    {{-- ============================================================ --}}
    @if (count($rowsWonosobo) > 0)
    <div class="page lampiran">
        <div class="lampiran-title">
            DAFTAR PENERIMAAN GAJI PPPK — BANK WONOSOBO<br>
            {{ strtoupper($gaj->nama_satker) }}<br>
            BULAN {{ strtoupper($bulanNama) }} {{ $gaj->tahun }}
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" style="width:30px;">NO</th>
                    <th rowspan="2" style="width:220px;">NAMA</th>
                    <th rowspan="2" style="width:110px;">BANK PENERIMA</th>
                    <th rowspan="2" style="width:110px;">NO REKENING</th>
                    <th rowspan="2" style="width:100px;">GAJI BRUTO</th>
                    <th colspan="2">POT LAINYA</th>
                    <th rowspan="2" style="width:100px;">GAJI BERSIH</th>
                </tr>
                <tr>
                    <th style="width:45px;">BAZNAS</th>
                    <th style="width:45px;">DKK KORPRI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rowsWonosobo as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="l">{{ $row['nama'] }}</td>
                    <td>Bank Wonosobo</td>
                    <td>{{ $row['no_rekening'] }}</td>
                    <td class="r">{{ $rp($row['bruto']) }}</td>
                    <td class="r">{{ $rp($row['baznas']) }}</td>
                    <td class="r">{{ $rp($row['korpri']) }}</td>
                    <td class="r">{{ $rp($row['bersih']) }}</td>
                </tr>
                @endforeach
                <tr class="b">
                    <td colspan="4"><strong>JUMLAH</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBrutoWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBaznasWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumKorpriWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBersihWonosobo) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;">Mengetahui,</td>
                </tr>
                <tr>
                    <td></td>
                    <td>&nbsp;{{ $jabatanCamat }}</td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td></td>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td>{{ $nipCamat }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    @if (count($rowsWonosobo) > 0)
    {{-- ============================================================ --}}
    {{-- PPPK PAGE 3: SURAT BANK WONOSOBO                             --}}
    {{-- ============================================================ --}}
    <div class="page">
        {{-- Kop Surat --}}
        <div class="kop">
            <img src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo Wonosobo" class="logo">
            <h2>PEMERINTAH KABUPATEN WONOSOBO</h2>
            <h1>KECAMATAN WATUMALANG</h1>
            <div class="detail">
                Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352<br>
                Telpon ( 0286 ) 3304957<br>
                Laman: kecamatanwatumalang.wonosobokab.go.id<br>
                Pos-el: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="b5c2d4c1c0d8d4d9d4dbd2858df5d2d8d4dcd99bd6dad8">[email&#160;protected]</a>
            </div>
        </div>
        <div class="kop-line"></div>
        <div class="kop-line-inner"></div>

        {{-- Tanggal --}}
        <div class="date-right"><span class="tgl-cetak-label"></span></div>

        {{-- Nomor / Lampiran / Perihal --}}
        <table class="surat-info">
            <tr>
                <td class="label">Nomor</td>
                <td class="sep">:</td>
                <td>900/</td>
            </tr>
            <tr>
                <td class="label">Lampiran</td>
                <td class="sep">:</td>
                <td>1 (satu) Lembar</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="sep">:</td>
                <td>Pemindahbukuan Gaji PPPK Bulan {{ $bulanNama }} {{ $gaj->tahun }}</td>
            </tr>
        </table>

        {{-- Addressee --}}
        <div class="addressee">
            Yth. Direktur PT. BPR BANK WONOSOBO<br>
            (PERSERODA)<br>
            di &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;WONOSOBO
        </div>

        {{-- Body --}}
        <div class="body-text">
            Dengan hormat,<br><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sehubungan dengan pembayaran gaji PPPK bulan {{ $bulanNama }} {{ $gaj->tahun }} bersama ini kami mohon
            untuk dipindahbukukan dari rekening gaji kami :<br><br>

            <table class="surat-info" style="margin-bottom: 4px;">
                <tr>
                    <td class="label">Atas nama</td>
                    <td class="sep">:</td>
                    <td>Bendahara Gaji Kantor Kecamatan Watumalang</td>
                </tr>
                <tr>
                    <td class="label">Jumlah</td>
                    <td class="sep">:</td>
                    <td>Rp. {{ $rp($totalSuratWonosobo) }}</td>
                </tr>
            </table>
            Untuk dipindahkan kedalam rekening di bawah ini :
        </div>

        {{-- Tabel Pemindahbukuan (Bank Wonosobo) --}}
        <table class="data">
            <thead>
                <tr>
                    <th style="width:30px;">No.</th>
                    <th>NAMA REK</th>
                    <th style="width:110px;">Nomor Rek</th>
                    <th style="width:80px;">KET</th>
                    <th style="width:120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td class="l">PT BPR BANK WONOSOBO (PERSERODA) <i>(daftar terlampir)</i></td>
                    <td>-</td>
                    <td>Rp/Gaji</td>
                    <td class="r">{{ $rp($sumBersihWonosobo) }}</td>
                </tr>
                <tr class="b">
                    <td class="b" colspan="4"><strong>Jumlah</strong></td>
                    <td class="r b"><strong>{{ $rp($totalSuratWonosobo) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Closing text --}}
        <div class="body-text" style="margin-top: 10px;">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Apabila dikemudian hari terjadi kesalahan penyampaian data gaji pegawai, maka kami
            akan tanggung jawab atas kesalahan penyampaian data tersebut diatas<br><br>
            Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih
        </div>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;">Mengetahui,</td>
                    <td style="width:50%;">Penyiap Dokumen Gaji</td>
                </tr>
                <tr>
                    <td>{{ $jabatanCamat }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Kabupaten Wonosobo</td>
                    <td></td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                    <td><span class="name">{{ $namaPenyiap }}</span></td>
                </tr>
                <tr>
                    <td>{{ $nipCamat }}</td>
                    <td>{{ $nipPenyiap }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PPPK PAGE 4: LAMPIRAN BANK WONOSOBO                          --}}
    {{-- ============================================================ --}}
    <div class="page lampiran">
        <div class="lampiran-title">
            DAFTAR PENERIMAAN GAJI PPPK — BANK WONOSOBO<br>
            {{ strtoupper($gaj->nama_satker) }}<br>
            BULAN {{ strtoupper($bulanNama) }} {{ $gaj->tahun }}
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" style="width:40px;">NO</th>
                    <th rowspan="2" style="width:220px;">NAMA</th>
                    <th rowspan="2" style="width:110px;">BANK PENERIMA</th>
                    <th rowspan="2" style="width:110px;">NO REKENING</th>
                    <th rowspan="2" style="width:100px;">GAJI BRUTO</th>
                    <th colspan="2">POT LAINYA</th>
                    <th rowspan="2" style="width:100px;">GAJI BERSIH</th>
                </tr>
                <tr>
                    <th style="width:45px;">BAZNAS</th>
                    <th style="width:45px;">DKK KORPRI</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rowsWonosobo as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="l">{{ $row['nama'] }}</td>
                    <td>Bank Wonosobo</td>
                    <td>{{ $row['no_rekening'] }}</td>
                    <td class="r">{{ $rp($row['bruto']) }}</td>
                    <td class="r">{{ $rp($row['baznas']) }}</td>
                    <td class="r">{{ $rp($row['korpri']) }}</td>
                    <td class="r">{{ $rp($row['bersih']) }}</td>
                </tr>
                @endforeach
                <tr class="b">
                    <td colspan="4"><strong>JUMLAH</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBrutoWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBaznasWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumKorpriWonosobo) }}</strong></td>
                    <td class="r b"><strong>{{ $rp($sumBersihWonosobo) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- TTD --}}
        <div class="ttd-area">
            <table>
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%;">Mengetahui,</td>
                </tr>
                <tr>
                    <td></td>
                    <td>&nbsp;{{ $jabatanCamat }}</td>
                </tr>
                <tr style="height:50px;"><td></td><td></td></tr>
                <tr>
                    <td></td>
                    <td><span class="name">{{ $namaCamat }}</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td>{{ $nipCamat }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif
    @endif

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
        const bulanNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const tglInput = document.getElementById('tglCetak');
        const tglLabels = document.querySelectorAll('.tgl-cetak-label');

        function updateTglLabel() {
            const d = tglInput.valueAsDate ? new Date(tglInput.value + 'T00:00:00') : new Date();
            const labelText = 'Wonosobo, ' + d.getDate() + ' ' + bulanNames[d.getMonth()] + ' ' + d.getFullYear();
            tglLabels.forEach(label => {
                label.textContent = labelText;
            });
        }

        tglInput.value = new Date().toISOString().slice(0, 10);
        updateTglLabel();
        tglInput.addEventListener('change', updateTglLabel);
    </script>
</body>
</html>