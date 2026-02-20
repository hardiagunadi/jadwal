<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian {{ strtoupper($gaj->jenis) }} {{ $gaj->periode }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }

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

        .info { margin-bottom: 4px; font-size: 11pt; }
        .info td { padding: 0 3px; }
        .info .label { width: 140px; }
        .info .sep { width: 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
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
        
        /* Subtotal rows - abu-abu muda untuk baris section */
        tr.section-total td {
            background: #e5e7eb;
            font-weight: bold;
        }
        
        /* Grand total row - abu-abu lebih gelap untuk baris total akhir */
        tr.grand-total td {
            background: #d1d5db;
            font-weight: bold;
        }

        .ttd-area {
            margin-top: 12px;
            font-size: 11pt;
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
        <label style="font-size:8pt;">Tanggal cetak:
            <input type="date" id="tglCetak" style="font-size:8pt; padding:2px 4px;">
        </label>
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('gaj.excel.rincian', $gaj) }}" style="padding:3px 12px; background:#16a34a; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:8pt; text-decoration:none;">Unduh Excel</a>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    @php
        $rp     = fn (int $v): string => number_format($v, 0, ',', '.');
        $dash   = fn ($v) => $v > 0 ? $v : '-';
        $dashRp = fn (int $v): string => $v > 0 ? number_format($v, 0, ',', '.') : '-';
        $G      = $groups;
        $tot    = fn (string $k) => $G['I'][$k] + $G['II'][$k] + $G['III'][$k] + $G['IV'][$k];
        
        // Hitung jumlah gaji & tunjangan untuk setiap golongan
        $hitungGajiTunj = function($gol) use ($G) {
            return ($G[$gol]['gaji_pokok'] ?? 0) 
                 + ($G[$gol]['tunj_istri'] ?? 0) 
                 + ($G[$gol]['tunj_anak'] ?? 0) 
                 + ($G[$gol]['tunj_fung_umum'] ?? 0) 
                 + ($G[$gol]['tunj_eselon'] ?? 0) 
                 + ($G[$gol]['tunj_fungsional'] ?? 0) 
                 + ($G[$gol]['tunj_khusus'] ?? 0) 
                 + ($G[$gol]['tkd'] ?? 0) 
                 + ($G[$gol]['tunj_pajak'] ?? 0) 
                 + ($G[$gol]['pembulatan'] ?? 0);
        };
        
        // Untuk jumlah jabatan, gunakan count pegawai dengan tunjangan
        $countStruktural = function($gol) use ($G) {
            return $G[$gol]['eselon_struktural'] ?? 0;
        };
        
        $countFungsional = function($gol) use ($G) {
            return $G[$gol]['eselon_fungsional'] ?? 0;
        };
        
        // Staf Pelaksana = Total Pegawai - Struktural - Fungsional
        $countPelaksana = function($gol) use ($G) {
            $totalPeg = $G[$gol]['peg'] ?? 0;
            $struktural = $G[$gol]['eselon_struktural'] ?? 0;
            $fungsional = $G[$gol]['eselon_fungsional'] ?? 0;
            return max(0, $totalPeg - $struktural - $fungsional);
        };
        
        // Fungsi untuk menghitung total dari semua golongan
        $totalGol = function($key) use ($G) {
            return ($G['I'][$key] ?? 0) + ($G['II'][$key] ?? 0) + ($G['III'][$key] ?? 0) + ($G['IV'][$key] ?? 0);
        };
    @endphp

    {{-- ══ Header Info ══ --}}
    <table class="info">
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">DAFTAR</td>
            <td class="sep">:</td>
            <td>RINCIAN BELANJA DAN TUNJANGAN PEGAWAI</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label"></td>
            <td class="sep"></td>
            <td>PEMBAYARAN GAJI / GAJI SUSULAN / KEKURANGAN GAJI</td>
        </tr>
        <tr><td colspan="5">&nbsp;</td></tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">SATUAN KERJA</td>
            <td class="sep">:</td>
            <td>{{ $gaj->nama_satker }}</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">KODE REKENING</td>
            <td class="sep">:</td>
            <td>7.01.01.2.02.0001</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">NPWP</td>
            <td class="sep">:</td>
            <td>-</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">KAB./KOTA</td>
            <td class="sep">:</td>
            <td>WONOSOBO</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">BULAN</td>
            <td class="sep">:</td>
            <td>{{ strtoupper($gaj->periode) }}</td>
        </tr>
        <tr><td colspan="5">&nbsp;</td></tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
            <td class="label">Jumlah SPM</td>
            <td class="sep">:</td>
            <td><strong>{{ $rp($tot('jml_kotor')) }}</strong></td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="sep"></td>
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
                <th rowspan="2" style="width:30px;">No.</th>
                <th rowspan="2" style="width:auto;">URAIAN</th>
                <th colspan="4">GOLONGAN</th>
                <th rowspan="2" style="width:120px;">JUMLAH</th>
            </tr>
            <tr>
                <th style="width:100px;">I</th>
                <th style="width:100px;">II</th>
                <th style="width:100px;">III</th>
                <th style="width:100px;">IV</th>
            </tr>
            <tr>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
            </tr>
        </thead>
        <tbody>
            {{-- ══ I. JUMLAH ORANG ══ --}}
            <tr class="b section-total">
                <td class="b">I</td>
                <td class="l b">JUMLAH ORANG</td>
                <td class="b">{{ $dash($G['I']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['II']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['III']['jiwa']) }}</td>
                <td class="b">{{ $dash($G['IV']['jiwa']) }}</td>
                <td class="b">{{ $dash($totalGol('jiwa')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">1. Pegawai</td>
                <td>{{ $dash($G['I']['peg']) }}</td>
                <td>{{ $dash($G['II']['peg']) }}</td>
                <td>{{ $dash($G['III']['peg']) }}</td>
                <td>{{ $dash($G['IV']['peg']) }}</td>
                <td>{{ $dash($totalGol('peg')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">2. Istri/Suami</td>
                <td>{{ $dash($G['I']['istri']) }}</td>
                <td>{{ $dash($G['II']['istri']) }}</td>
                <td>{{ $dash($G['III']['istri']) }}</td>
                <td>{{ $dash($G['IV']['istri']) }}</td>
                <td>{{ $dash($totalGol('istri')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">3. Anak</td>
                <td>{{ $dash($G['I']['anak']) }}</td>
                <td>{{ $dash($G['II']['anak']) }}</td>
                <td>{{ $dash($G['III']['anak']) }}</td>
                <td>{{ $dash($G['IV']['anak']) }}</td>
                <td>{{ $dash($totalGol('anak')) }}</td>
            </tr>

            {{-- ══ II. JUMLAH JABATAN ══ --}}
            <tr class="b section-total">
                <td class="b">II</td>
                <td class="l b">JUMLAH ORANG</td>
                <td class="b">{{ $dash($countStruktural('I') + $countFungsional('I') + $countPelaksana('I')) }}</td>
                <td class="b">{{ $dash($countStruktural('II') + $countFungsional('II') + $countPelaksana('II')) }}</td>
                <td class="b">{{ $dash($countStruktural('III') + $countFungsional('III') + $countPelaksana('III')) }}</td>
                <td class="b">{{ $dash($countStruktural('IV') + $countFungsional('IV') + $countPelaksana('IV')) }}</td>
                <td class="b">{{ $dash($countStruktural('I') + $countStruktural('II') + $countStruktural('III') + $countStruktural('IV') + $countFungsional('I') + $countFungsional('II') + $countFungsional('III') + $countFungsional('IV') + $countPelaksana('I') + $countPelaksana('II') + $countPelaksana('III') + $countPelaksana('IV')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">1. Struktural</td>
                <td>{{ $dash($countStruktural('I')) }}</td>
                <td>{{ $dash($countStruktural('II')) }}</td>
                <td>{{ $dash($countStruktural('III')) }}</td>
                <td>{{ $dash($countStruktural('IV')) }}</td>
                <td>{{ $dash($countStruktural('I') + $countStruktural('II') + $countStruktural('III') + $countStruktural('IV')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">2. Fungsional</td>
                <td>{{ $dash($countFungsional('I')) }}</td>
                <td>{{ $dash($countFungsional('II')) }}</td>
                <td>{{ $dash($countFungsional('III')) }}</td>
                <td>{{ $dash($countFungsional('IV')) }}</td>
                <td>{{ $dash($countFungsional('I') + $countFungsional('II') + $countFungsional('III') + $countFungsional('IV')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">3. Pelaksana</td>
                <td>{{ $dash($countPelaksana('I')) }}</td>
                <td>{{ $dash($countPelaksana('II')) }}</td>
                <td>{{ $dash($countPelaksana('III')) }}</td>
                <td>{{ $dash($countPelaksana('IV')) }}</td>
                <td>{{ $dash($countPelaksana('I') + $countPelaksana('II') + $countPelaksana('III') + $countPelaksana('IV')) }}</td>
            </tr>

            {{-- ══ III. JML. GAJI & TUNJ. ══ --}}
            <tr class="b section-total">
                <td class="b">III</td>
                <td class="l b">JML. GAJI & TUNJ.</td>
                <td class="r b">{{ $dashRp($hitungGajiTunj('I')) }}</td>
                <td class="r b">{{ $dashRp($hitungGajiTunj('II')) }}</td>
                <td class="r b">{{ $dashRp($hitungGajiTunj('III')) }}</td>
                <td class="r b">{{ $dashRp($hitungGajiTunj('IV')) }}</td>
                <td class="r b">{{ $dashRp($hitungGajiTunj('I') + $hitungGajiTunj('II') + $hitungGajiTunj('III') + $hitungGajiTunj('IV')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">1. Gaji Pokok</td>
                <td class="r">{{ $dashRp($G['I']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['II']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['III']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['gaji_pokok']) }}</td>
                <td class="r">{{ $dashRp($totalGol('gaji_pokok')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">2. T. Istri/Suami</td>
                <td class="r">{{ $dashRp($G['I']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_istri']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_istri')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">3. Tunjangan Anak</td>
                <td class="r">{{ $dashRp($G['I']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_anak']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_anak')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">4. Tunjangan Umum</td>
                <td class="r">{{ $dashRp($G['I']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_fung_umum']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_fung_umum')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">5. Tunj. Jab. Struktural</td>
                <td class="r">{{ $dashRp($G['I']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_eselon']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_eselon')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">6. Tunj. Fungsional</td>
                <td class="r">{{ $dashRp($G['I']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_fungsional']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_fungsional')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">7. Tunj. Khusus</td>
                <td class="r">{{ $dashRp($G['I']['tunj_khusus'] + $G['I']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_khusus'] + $G['II']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_khusus'] + $G['III']['tkd']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_khusus'] + $G['IV']['tkd']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_khusus') + $totalGol('tkd')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">8. Tunj. Pajak</td>
                <td class="r">{{ $dashRp($G['I']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['tunj_pajak']) }}</td>
                <td class="r">{{ $dashRp($totalGol('tunj_pajak')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">9. Pembulatan</td>
                <td class="r">{{ $dashRp($G['I']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pembulatan']) }}</td>
                <td class="r">{{ $dashRp($totalGol('pembulatan')) }}</td>
            </tr>

            {{-- ══ IV. TUNJ. BERAS ══ --}}
            <tr class="b section-total">
                <td class="b">IV</td>
                <td class="l b">TUNJ. BERAS</td>
                <td class="r b">{{ $dashRp($G['I']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['tunj_beras']) }}</td>
                <td class="r b">{{ $dashRp($totalGol('tunj_beras')) }}</td>
            </tr>

            {{-- ══ V. JUMLAH (III+IV) ══ --}}
            <tr class="b grand-total">
                <td class="b">V</td>
                <td class="l b">JUMLAH (III+IV)</td>
                <td class="r b">{{ $dashRp($G['I']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jml_kotor']) }}</td>
                <td class="r b">{{ $dashRp($totalGol('jml_kotor')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">1. IWP 1 %</td>
                <td class="r">{{ $dashRp($G['I']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_iwp_1']) }}</td>
                <td class="r">{{ $dashRp($totalGol('pot_iwp_1')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">2. IWP 8 %</td>
                <td class="r">{{ $dashRp($G['I']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_iwp_8']) }}</td>
                <td class="r">{{ $dashRp($totalGol('pot_iwp_8')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">3. TAPERUM</td>
                <td class="r">{{ $dashRp($G['I']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_taperum']) }}</td>
                <td class="r">{{ $dashRp($totalGol('pot_taperum')) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="l">4. PPh PASAL 21</td>
                <td class="r">{{ $dashRp($G['I']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['II']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['III']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($G['IV']['pot_pajak']) }}</td>
                <td class="r">{{ $dashRp($totalGol('pot_pajak')) }}</td>
            </tr>

            {{-- ══ VI. JML. POTONGAN ══ --}}
            <tr class="b section-total">
                <td class="b">VI</td>
                <td class="l b">JML. POTONGAN</td>
                <td class="r b">{{ $dashRp($G['I']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jml_potongan']) }}</td>
                <td class="r b">{{ $dashRp($totalGol('jml_potongan')) }}</td>
            </tr>

            {{-- ══ VII. JML. BERSIH ══ --}}
            <tr class="b grand-total">
                <td class="b">VII</td>
                <td class="l b">JML. BERSIH</td>
                <td class="r b">{{ $dashRp($G['I']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['II']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['III']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($G['IV']['jumlah_bersih']) }}</td>
                <td class="r b">{{ $dashRp($totalGol('jumlah_bersih')) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ TTD Section ══ --}}
    <div class="ttd-area">
        <table>
            <tr>
                <td style="width:50%;"></td>
                <td style="width:50%;"><span id="tglCetakLabel"></span></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Bendahara Pengeluaran</td>
                <td>Pembantu Bendahara Pengeluaran</td>
            </tr>
            <tr>
                <td></td>
                <td>Untuk Urusan Gaji</td>
            </tr>
            <tr style="height:60px;"><td></td><td></td></tr>
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
        const bulanNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const tglInput = document.getElementById('tglCetak');
        const tglLabel = document.getElementById('tglCetakLabel');

        function updateTglLabel() {
            const d = tglInput.valueAsDate ? new Date(tglInput.value + 'T00:00:00') : new Date();
            tglLabel.textContent = 'Wonosobo, ' + d.getDate() + ' ' + bulanNames[d.getMonth()] + ' ' + d.getFullYear();
        }

        tglInput.value = new Date().toISOString().slice(0, 10);
        updateTglLabel();
        tglInput.addEventListener('change', updateTglLabel);
    </script>
</body>
</html>