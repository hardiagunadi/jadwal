<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemindahbukuan SPJ {{ $bku->nama_label }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
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
        .kop h2 { font-size: 11pt; margin: 0; }
        .kop h3 { font-size: 13pt; margin: 0; }
        .kop p { font-size: 8pt; margin: 0; }
        .kop-line {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 4px;
            margin: 4px 0 8px 0;
        }

        .letter-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        .letter-info .left { width: 45%; }
        .letter-info .right { width: 45%; text-align: left; }
        .letter-info table { border-collapse: collapse; }
        .letter-info td { padding: 1px 3px; vertical-align: top; }

        .body-text { margin-bottom: 6px; font-size: 9pt; line-height: 1.4; }
        .body-text table { border-collapse: collapse; margin: 4px 0; }
        .body-text td { padding: 1px 4px; vertical-align: top; }
        .body-text .label { width: 120px; }
        .body-text .sep { width: 10px; text-align: center; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 8px;
        }
        table.data th, table.data td {
            border: 0.5pt solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }
        table.data th {
            background: #f9fafb;
            font-weight: bold;
            text-align: center;
        }
        .r { text-align: right; }
        .c { text-align: center; }
        .b { font-weight: bold; }

        .disclaimer { font-size: 8.5pt; line-height: 1.4; margin-bottom: 10px; }

        .ttd {
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        .ttd .col { width: 45%; text-align: center; }
        .ttd .space { height: 50px; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    @php
        $rp = fn (int $v): string => $v ? number_format($v, 0, ',', '.') : '-';
    @endphp

    <div class="kop">
        <img class="logo" src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo">
        <h2>PEMERINTAH KABUPATEN WONOSOBO</h2>
        <h3>KECAMATAN WATUMALANG</h3>
        <p>Jalan Kyai Jebeng Lintang Nomor 29 Watumalang Wonosobo</p>
    </div>
    <div class="kop-line"></div>

    <div style="text-align:right; margin-bottom:6px;">
        Watumalang, &hellip;&hellip; {{ $bulanNama }} {{ $bku->tahun }}
    </div>

    <div class="letter-info">
        <div class="left">
            <table>
                <tr><td>No</td><td>:</td><td></td></tr>
                <tr><td>Lamp</td><td>:</td><td>1 bendel</td></tr>
                <tr><td>Hal</td><td>:</td><td><strong>Pemindahbukuan Rekening</strong></td></tr>
            </table>
        </div>
        <div class="right">
            <table>
                <tr><td colspan="2">Kepada :</td></tr>
                <tr><td colspan="2">Yth. Bapak Pimpinan Cabang</td></tr>
                <tr><td colspan="2"><strong>BANK JATENG</strong></td></tr>
                <tr><td colspan="2">Cabang Wonosobo</td></tr>
                <tr><td colspan="2">di_</td></tr>
                <tr><td>&nbsp;&nbsp;&nbsp;</td><td><strong>WONOSOBO</strong></td></tr>
            </table>
        </div>
    </div>

    <div class="body-text">
        <p>Dengan hormat,</p>
        <p>Bersama ini kami mohon untuk dipindahbukukan dari rekening kami :</p>
        <table>
            <tr>
                <td class="label">Nomor Rekening</td>
                <td class="sep">:</td>
                <td><strong>1-023-00085.3</strong></td>
            </tr>
            <tr>
                <td class="label">Atas Nama</td>
                <td class="sep">:</td>
                <td><strong>Bendh Pengeluaran Kec. Watumalang</strong></td>
            </tr>
            <tr>
                <td class="label">Jumlah</td>
                <td class="sep"></td>
                <td><strong>{{ $rp($totalAll) }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:30px;">NO</th>
                <th>NAMA</th>
                <th style="width:110px;">JUMLAH</th>
                <th style="width:130px;">NO REKENING BANK</th>
                <th style="width:60px;">BANK</th>
            </tr>
            <tr>
                <th>1.</th>
                <th>2.</th>
                <th>3.</th>
                <th>4.</th>
                <th>5.</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($items as $item)
                <tr>
                    <td class="c">{{ $no++ }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="r">Rp {{ $rp($item['jumlah']) }}</td>
                    <td class="c">{{ $item['no_rekening'] }}</td>
                    <td class="c">{{ $item['bank'] }}</td>
                </tr>
            @endforeach

            {{-- Tax rows --}}
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td>PPh. 21</td>
                <td class="r">Rp {{ $rp($totalPph21) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td>PPh. 22</td>
                <td class="r">Rp {{ $rp($totalPph22) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td>PPh. 23</td>
                <td class="r">Rp {{ $rp($totalPph23) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td>PPN</td>
                <td class="r">Rp {{ $rp($totalPpn) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td>BPJS Kes</td>
                <td class="r">Rp {{ $rp($totalBpjs) }}</td>
                <td></td>
                <td></td>
            </tr>

            {{-- Total row --}}
            <tr class="b">
                <td colspan="2" class="c b">Total</td>
                <td class="r b">Rp {{ $rp($totalAll) }}</td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="disclaimer">
        <p>Apabila dikemudian hari terjadi kesalahan penyampaian data tersebut diatas, maka
        kami akan bertanggung jawab atas kesalahan penyampaian data tersebut diatas.</p>
        <br>
        <p>Demikian yang dapat kami sampaikan, atas perhatiannya kami ucapkan terimakasih.</p>
    </div>

    <div class="ttd">
        <div class="col">
            Mengetahui,<br>
            {{ strtoupper($jabatanCamat) }}
            <div class="space"></div>
            <div class="b" style="text-decoration:underline;">{{ strtoupper($namaCamat) }}</div>
            <div>{{ $nipCamat }}</div>
        </div>
        <div class="col">
            <br>BENDAHARA PENGELUARAN
            <div class="space"></div>
            <div class="b" style="text-decoration:underline;">{{ strtoupper($namaBendahara) }}</div>
            <div>{{ $nipBendahara }}</div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
