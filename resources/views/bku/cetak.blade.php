<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BKU {{ $bku->nama_label }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 landscape; margin: 8mm 10mm; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
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

        .header { text-align: center; margin-bottom: 6px; }
        .header h2 { font-size: 12pt; margin: 0; }
        .header h3 { font-size: 11pt; margin: 0; }
        .header h4 { font-size: 10pt; margin: 0; }

        .meta { margin-bottom: 6px; font-size: 8.5pt; }
        .meta table { border-collapse: collapse; }
        .meta td { padding: 1px 4px; vertical-align: top; }
        .meta .label { width: 120px; }
        .meta .sep { width: 10px; text-align: center; }
        .meta .pekerjaan { font-weight: bold; text-decoration: underline; }

        table.bku {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.bku th, table.bku td {
            border: 0.5pt solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }
        table.bku th {
            background: #f9fafb;
            font-weight: bold;
            text-align: center;
        }
        .r { text-align: right; }
        .c { text-align: center; }
        .b { font-weight: bold; }

        .footer { margin-top: 10px; font-size: 8.5pt; }
        .footer table { border-collapse: collapse; width: 100%; }
        .footer td { padding: 1px 4px; vertical-align: top; }

        .ttd {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
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

    <div class="header">
        <h2>PEMERINTAH KABUPATEN WONOSOBO</h2>
        <h3>BUKU KAS UMUM</h3>
        <h4>BENDAHARA PENGELUARAN</h4>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">SKPD</td>
                <td class="sep">:</td>
                <td>KECAMATAN WATUMALANG</td>
            </tr>
            <tr>
                <td class="label">Tahun Anggaran</td>
                <td class="sep">:</td>
                <td>{{ $bku->tahun }}</td>
            </tr>
            <tr>
                <td class="label">Sub Kegiatan</td>
                <td class="sep">:</td>
                <td>{{ $subKegiatan ? $subKegiatan->kode . ' ' . $subKegiatan->nama : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Pekerjaan</td>
                <td class="sep">:</td>
                <td class="pekerjaan">{{ $bku->pekerjaan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Bulan</td>
                <td class="sep">:</td>
                <td>{{ $bulanNama }}</td>
            </tr>
        </table>
    </div>

    <table class="bku">
        <thead>
            <tr>
                <th style="width:30px;">NO.</th>
                <th style="width:60px;">TANGGAL</th>
                <th>URAIAN</th>
                <th style="width:110px;">KODE REKENING</th>
                <th style="width:100px;">PENERIMAAN</th>
                <th style="width:100px;">PENGELUARAN</th>
                <th style="width:100px;">SALDO</th>
            </tr>
            <tr>
                <th>1.</th>
                <th>2.</th>
                <th>3.</th>
                <th>4.</th>
                <th>5.</th>
                <th>6.</th>
                <th>7.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="c">{{ $row['no'] }}</td>
                    <td class="c">{{ $row['tanggal'] }}</td>
                    <td>{{ $row['uraian'] }}</td>
                    <td class="c" style="font-size:7pt;">{{ $row['kode_rekening'] }}</td>
                    <td class="r">{{ $row['penerimaan'] ? $rp($row['penerimaan']) : '' }}</td>
                    <td class="r">{{ $row['pengeluaran'] ? $rp($row['pengeluaran']) : '-' }}</td>
                    <td class="r">{{ $rp($row['saldo']) }}</td>
                </tr>
            @endforeach
            <tr class="b">
                <td colspan="4" class="c b">JUMLAH</td>
                <td class="r b">{{ $rp($totalPenerimaan) }}</td>
                <td class="r b">{{ $rp($totalPengeluaran) }}</td>
                <td class="r b">{{ $rp($saldoAkhir) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="width:50%;">
                    <table>
                        <tr>
                            <td>Kas di Bendahara Pengeluaran Pembantu</td>
                            <td class="r" style="width:120px;">{{ $rp($totalPengeluaran) }}</td>
                        </tr>
                        <tr><td colspan="2">&nbsp;</td></tr>
                        <tr><td colspan="2">Terdiri dari :</td></tr>
                        <tr>
                            <td>a. Tunai</td>
                            <td class="r">Rp. -</td>
                        </tr>
                        <tr>
                            <td>b. Saldo Bank</td>
                            <td class="r">Rp. -</td>
                        </tr>
                        <tr>
                            <td>c. Surat Berharga</td>
                            <td class="r">Rp. -</td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; vertical-align:top; padding-left:20px;">
                    @if (count($breakdown) > 0)
                        <table>
                            @foreach ($breakdown as $item)
                                <tr>
                                    <td style="font-size:7pt;">{{ $item['label'] }}</td>
                                    <td class="r">{{ $rp($item['amount']) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="ttd">
        <div class="col">
            Mengetahui,<br>
            PENGGUNA ANGGARAN
            <div class="space"></div>
            <div class="b" style="text-decoration:underline;">{{ strtoupper($namaPa) }}</div>
            <div>{{ $nipPa }}</div>
        </div>
        <div class="col">
            Kecamatan Watumalang, &hellip;&hellip;&hellip;&hellip;&hellip; {{ $bku->tahun }}<br>
            BENDAHARA PENGELUARAN PEMBANTU
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
