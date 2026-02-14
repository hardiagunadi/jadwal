<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian {{ strtoupper($gaj->jenis) }} {{ $gaj->periode }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: 10mm; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
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

        .info { margin-bottom: 4px; font-size: 8pt; }
        .info td { padding: 0 3px; }
        .info .label { width: 140px; }
        .info .sep { width: 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.data th,
        table.data td {
            border: 0.5pt solid #000;
            padding: 1px 3px;
            text-align: center;
            vertical-align: middle;
        }
        table.data th {
            font-weight: bold;
            background: #fff;
        }
        table.data td.l { text-align: left; }
        table.data td.r { text-align: right; }
        .b { font-weight: bold; }

        .ttd-area {
            margin-top: 12px;
            font-size: 8pt;
        }
        .ttd-area table { width: 100%; }
        .ttd-area td { vertical-align: top; text-align: center; padding: 1px 4px; }
        .ttd-area .name { font-weight: bold; text-decoration: underline; }

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
        $rp     = fn (int $v): string => number_format($v, 0, ',', '.');
        $dash   = fn ($v) => $v > 0 ? $v : '-';
        $dashRp = fn (int $v): string => $v > 0 ? number_format($v, 0, ',', '.') : '-';
        $G      = $groups;
        $tot    = fn (string $k) => $G['I'][$k] + $G['II'][$k] + $G['III'][$k] + $G['IV'][$k];
    @endphp

    {{-- ══ Header Info ══ --}}
    <table class="info">
        <tr>
            <td class="label">DAFTAR</td>
            <td class="sep">:</td>
            <td>RINCIAN BELANJA DAN TUNJANGAN PEGAWAI</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td>PEMBAYARAN GAJI / GAJI SUSULAN / KEKURANGAN GAJI</td>
        </tr>
        <tr><td colspan="3">&nbsp;</td></tr>
        <tr>
            <td class="label">SATUAN KERJA</td>
            <td class="sep">:</td>
            <td>{{ $gaj->nama_satker }}</td>
        </tr>
        <tr>
            <td class="label">KODE REKENING</td>
            <td class="sep">:</td>
            <td>2.01.13.1.1.03.2</td>
        </tr>
        <tr>
            <td class="label">NPWP</td>
            <td class="sep">:</td>
            <td>-</td>
        </tr>
        <tr>
            <td class="label">KAB./KOTA</td>
            <td class="sep">:</td>
            <td>WONOSOBO</td>
        </tr>
        <tr>
            <td class="label">BULAN</td>
            <td class="sep">:</td>
            <td>{{ strtoupper($gaj->periode) }}</td>
        </tr>
        <tr><td colspan="3">&nbsp;</td></tr>
        <tr>
            <td class="label">Jumlah SPM</td>
            <td class="sep">:</td>
            <td>{{ $rp($tot('jml_kotor')) }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Meninggal</td>
            <td class="sep">:</td>
            <td>0</td>
        </tr>
    </table>

    <br>

    {{-- ══ Data Table ══ --}}
    <table class="data">
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th style="width:auto;">URAIAN</th>
                <th style="width:100px;">Golongan I</th>
                <th style="width:100px;">Golongan II</th>
                <th style="width:100px;">Golongan III</th>
                <th style="width:100px;">Golongan IV</th>
            </tr>
            <tr>
                <th>A</th>
                <th>B</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
                <th>F</th>
            </tr>
        </thead>
        <tbody>
            {{-- ══ I. JUMLAH ORANG ══ --}}
            <tr class="b">
                <td class="b">I</td>
                <td class="l b">JUMLAH ORANG</td>
                <td class="b">{{ $dash($G['I']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['II']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['III']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['IV']['jiwa']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;1. Pegawai</td>
                <td>{{ $dash($G['I']['peg']) }}</td>
                <td>{{ $dash($G['II']['peg']) }}</td>
                <td>{{ $dash($G['III']['peg']) }}</td>
                <td>{{ $dash($G['IV']['peg']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;2. Istri/Suami</td>
                <td>{{ $dash($G['I']['istri']) }}</td>
                <td>{{ $dash($G['II']['istri']) }}</td>
                <td>{{ $dash($G['III']['istri']) }}</td>
                <td>{{ $dash($G['IV']['istri']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;3. Anak</td>
                <td>{{ $dash($G['I']['anak']) }}</td>
                <td>{{ $dash($G['II']['anak']) }}</td>
                <td>{{ $dash($G['III']['anak']) }}</td>
                <td>{{ $dash($G['IV']['anak']) }}</td>
            </tr>

            {{-- ══ II. JUMLAH JABATAN ══ --}}
            <tr class="b">
                <td class="b">II</td>
                <td class="l b">JUMLAH ORANG</td>
                <td class="b">{{ $dash($G['I']['eselon_struktural'] + $G['I']['eselon_fungsional']) }}</td>
                <td class="b">{{ $dash($G['II']['eselon_struktural'] + $G['II']['eselon_fungsional']) }}</td>
                <td class="b">{{ $dash($G['III']['eselon_struktural'] + $G['III']['eselon_fungsional']) }}</td>
                <td class="b">{{ $dash($G['IV']['eselon_struktural'] + $G['IV']['eselon_fungsional']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;1. Struktural</td>
                <td>{{ $dash($G['I']['eselon_struktural']) }}</td>
                <td>{{ $dash($G['II']['eselon_struktural']) }}</td>
                <td>{{ $dash($G['III']['eselon_struktural']) }}</td>
                <td>{{ $dash($G['IV']['eselon_struktural']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;2. Fungsional</td>
                <td>{{ $dash($G['I']['eselon_fungsional']) }}</td>
                <td>{{ $dash($G['II']['eselon_fungsional']) }}</td>
                <td>{{ $dash($G['III']['eselon_fungsional']) }}</td>
                <td>{{ $dash($G['IV']['eselon_fungsional']) }}</td>
            </tr>

            {{-- ══ III. JML. GAJI & TUNJANGAN ══ --}}
            <tr class="b">
                <td class="b">III</td>
                <td class="l b">JML. GAJI &amp; TUNJ.</td>
                <td class="r b">{{ $dashRp($G['I']['jml_kotor'] - $G['I']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jml_kotor'] - $G['II']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jml_kotor'] - $G['III']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jml_kotor'] - $G['IV']['tunj_beras']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;1. Gaji Pokok</td>
                <td class="r">{{ $dashRp($G['I']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['II']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['III']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['gaji_pokok']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;2. T. Istri/Suami</td>
                <td class="r">{{ $dashRp($G['I']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_istri']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;3. Tunjangan Anak</td>
                <td class="r">{{ $dashRp($G['I']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_anak']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;4. Tunjangan Umum</td>
                <td class="r">{{ $dashRp($G['I']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_fung_umum']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;5. Tunj. Jab. Struktural</td>
                <td class="r">{{ $dashRp($G['I']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_eselon']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;6. Tunj. Fungsional</td>
                <td class="r">{{ $dashRp($G['I']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_fungsional']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;7. Tunj. Khusus / TKD</td>
                <td class="r">{{ $dashRp($G['I']['tunj_khusus'] + $G['I']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_khusus'] + $G['II']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_khusus'] + $G['III']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_khusus'] + $G['IV']['tkd']) }}</td>
            </tr>
            @if ($isPns)
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;8. Tunj. Pajak</td>
                <td class="r">{{ $dashRp($G['I']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_pajak']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;9. Pembulatan</td>
                <td class="r">{{ $dashRp($G['I']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pembulatan']) }}</td>
            </tr>
            @else
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;8. Pembulatan</td>
                <td class="r">{{ $dashRp($G['I']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pembulatan']) }}</td>
            </tr>
            @endif

            {{-- ══ IV. TUNJ. BERAS ══ --}}
            <tr class="b">
                <td class="b">IV</td>
                <td class="l b">TUNJ. BERAS</td>
                <td class="r b">{{ $dashRp($G['I']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['tunj_beras']) }}</td>
            </tr>

            {{-- ══ V. JUMLAH (III+IV) ══ --}}
            <tr class="b">
                <td class="b">V</td>
                <td class="l b">JUMLAH (III+IV)</td>
                <td class="r b">{{ $dashRp($G['I']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jml_kotor']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;1. IWP 1 %</td>
                <td class="r">{{ $dashRp($G['I']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_iwp_1']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;2. IWP 8 %</td>
                <td class="r">{{ $dashRp($G['I']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_iwp_8']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;3. TAPERUM</td>
                <td class="r">{{ $dashRp($G['I']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_taperum']) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">&nbsp;&nbsp;&nbsp;4. PPh PASAL 21</td>
                <td class="r">{{ $dashRp($G['I']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_pajak']) }}</td>
            </tr>

            {{-- ══ VI. JML. POTONGAN ══ --}}
            <tr class="b">
                <td class="b">VI</td>
                <td class="l b">JML. POTONGAN</td>
                <td class="r b">{{ $dashRp($G['I']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jml_potongan']) }}</td>
            </tr>

            {{-- ══ VII. JML. BERSIH ══ --}}
            <tr class="b">
                <td class="b">VII</td>
                <td class="l b">JML. BERSIH</td>
                <td class="r b">{{ $dashRp($G['I']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jumlah_bersih']) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ TTD Section ══ --}}
    <div class="ttd-area">
        <table>
            <tr>
                <td style="width:50%;"></td>
                <td style="width:50%;">Mengetahui,</td>
            </tr>
            <tr>
                <td></td>
                <td>Pembantu Bendahara Pengeluaran</td>
            </tr>
            <tr>
                <td>Bendahara Pengeluaran</td>
                <td>Untuk Urusan Gaji</td>
            </tr>
            <tr style="height:30px;"><td></td><td></td></tr>
            <tr>
                <td><span class="name">{{ $namaBendahara }}</span></td>
                <td><span class="name">{{ $namaPembantu }}</span></td>
            </tr>
            <tr>
                <td>{{ $nipBendahara }}</td>
                <td>{{ $nipPembantu }}</td>
            </tr>
        </table>
    </div>

    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
