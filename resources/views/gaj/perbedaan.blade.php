<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perbedaan {{ strtoupper($gaj->jenis) }} {{ $gaj->periode }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 landscape; margin: 8mm 10mm; }

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

        h2 {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .info { margin-bottom: 4px; font-size: 8pt; }
        .info td { padding: 0 3px; }
        .info .label { width: 140px; }
        .info .sep { width: 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
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
        <label style="font-size:8pt;">Tanggal cetak:
            <input type="date" id="tglCetak" style="font-size:8pt; padding:2px 4px;">
        </label>
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('gaj.excel.perbedaan', $gaj) }}" style="padding:3px 12px; background:#16a34a; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:8pt; text-decoration:none;">Unduh Excel</a>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    @php
        $bulanLaluLabel = $gaj->bulan == 1
            ? \App\Models\Gaj::$bulanLabels[12] . ' ' . ($gaj->tahun - 1)
            : (\App\Models\Gaj::$bulanLabels[$gaj->bulan - 1] ?? '-') . ' ' . $gaj->tahun;

        $isPns  = $gaj->jenis === 'pns';
        $rp     = fn (int $v): string => number_format($v, 0, ',', '.');
        $dash   = fn ($v) => $v > 0 ? $v : '-';
        $dashRp = fn (int $v): string => $v > 0 ? number_format($v, 0, ',', '.') : '-';
    @endphp

    <h2>DAFTAR PERBEDAAN JUMLAH PEGAWAI DAN PEMBAYARAN</h2>

    <table class="info">
        <tr><td class="label">GAJI BULAN</td><td class="sep">:</td><td>{{ strtoupper($gaj->periode) }}</td></tr>
        <tr><td class="label">BADAN / DINAS KANTOR</td><td class="sep">:</td><td>KECAMATAN WATUMALANG</td></tr>
    </table>

    <table class="data">
        {{-- ══ Header Row 1: Group headers ══ --}}
        <thead>
        <tr>
            <th rowspan="3" style="width:22px;">No</th>
            <th rowspan="3" style="width:90px;">Golongan /<br>Ruang</th>
            <th rowspan="3" style="width:30px;"></th>
            <th colspan="5">I. Bulan : {{ $bulanLaluLabel }}</th>
            <th colspan="5">II. Bulan : {{ $gaj->periode }}</th>
            <th colspan="10">III. PERBEDAAN</th>
        </tr>
        {{-- ══ Header Row 2: Column labels ══ --}}
        <tr>
            <th rowspan="2" style="width:36px;">PEG.</th>
            <th rowspan="2" style="width:36px;">ISTRI</th>
            <th rowspan="2" style="width:36px;">ANAK</th>
            <th rowspan="2" style="width:36px;">JIWA</th>
            <th rowspan="2" style="width:80px;">JML. KOTOR</th>
            <th rowspan="2" style="width:36px;">PEG.</th>
            <th rowspan="2" style="width:36px;">ISTRI</th>
            <th rowspan="2" style="width:36px;">ANAK</th>
            <th rowspan="2" style="width:36px;">JIWA</th>
            <th rowspan="2" style="width:80px;">JML. KOTOR</th>
            <th colspan="2">PEGAWAI</th>
            <th colspan="2">ISTRI</th>
            <th colspan="2">ANAK</th>
            <th colspan="2">JIWA</th>
            <th colspan="2">JUMLAH KOTOR</th>
        </tr>
        {{-- ══ Header Row 3: TAMBAH/KURANG ══ --}}
        <tr>
            <th style="width:40px;">TAMBAH</th>
            <th style="width:40px;">KURANG</th>
            <th style="width:40px;">TAMBAH</th>
            <th style="width:40px;">KURANG</th>
            <th style="width:40px;">TAMBAH</th>
            <th style="width:40px;">KURANG</th>
            <th style="width:40px;">TAMBAH</th>
            <th style="width:40px;">KURANG</th>
            <th style="width:60px;">TAMBAH</th>
            <th style="width:60px;">KURANG</th>
        </tr>
        {{-- ══ Header Row 4: Column numbers ══ --}}
        <tr>
            @for ($c = 1; $c <= 23; $c++)
                <th style="font-size:6pt; font-weight:normal;">{{ $c }}</th>
            @endfor
        </tr>
        </thead>
        <tbody>
        @php
            $totLalu = ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
            $totNow  = ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
            $rowNum  = 0;
        @endphp

        @foreach ($rows as $groupRoman => $golRows)
            @php
                $groupLalu = ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
                $groupNow  = ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
            @endphp

            @foreach ($golRows as $gRow)
                @php
                    $rowNum++;
                    $lalu = $dataLalu[$gRow['key']] ?? ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
                    $now  = $dataNow[$gRow['key']]  ?? ['peg'=>0,'istri'=>0,'anak'=>0,'jiwa'=>0,'kotor'=>0];
                    $ruang = $isPns ? $gRow['key'] : ($gRow['ruang'] ?? '');

                    $dPeg   = ['t' => max(0, $now['peg']   - $lalu['peg']),   'k' => max(0, $lalu['peg']   - $now['peg'])];
                    $dIstri = ['t' => max(0, $now['istri']  - $lalu['istri']), 'k' => max(0, $lalu['istri'] - $now['istri'])];
                    $dAnak  = ['t' => max(0, $now['anak']   - $lalu['anak']),  'k' => max(0, $lalu['anak']  - $now['anak'])];
                    $dJiwa  = ['t' => max(0, $now['jiwa']   - $lalu['jiwa']),  'k' => max(0, $lalu['jiwa']  - $now['jiwa'])];
                    $dKotor = ['t' => max(0, $now['kotor']  - $lalu['kotor']), 'k' => max(0, $lalu['kotor'] - $now['kotor'])];

                    foreach (['peg','istri','anak','jiwa','kotor'] as $k) {
                        $groupLalu[$k] += $lalu[$k];
                        $groupNow[$k]  += $now[$k];
                    }
                @endphp
                <tr>
                    <td>{{ $rowNum }}</td>
                    <td class="l">{{ $gRow['label'] }}</td>
                    <td>{{ $ruang }}</td>
                    <td>{{ $dash($lalu['peg']) }}</td>
                    <td>{{ $dash($lalu['istri']) }}</td>
                    <td>{{ $dash($lalu['anak']) }}</td>
                    <td>{{ $dash($lalu['jiwa']) }}</td>
                    <td class="r">{{ $dashRp($lalu['kotor']) }}</td>
                    <td>{{ $dash($now['peg']) }}</td>
                    <td>{{ $dash($now['istri']) }}</td>
                    <td>{{ $dash($now['anak']) }}</td>
                    <td>{{ $dash($now['jiwa']) }}</td>
                    <td class="r">{{ $dashRp($now['kotor']) }}</td>
                    <td>{{ $dash($dPeg['t']) }}</td>
                    <td>{{ $dash($dPeg['k']) }}</td>
                    <td>{{ $dash($dIstri['t']) }}</td>
                    <td>{{ $dash($dIstri['k']) }}</td>
                    <td>{{ $dash($dAnak['t']) }}</td>
                    <td>{{ $dash($dAnak['k']) }}</td>
                    <td>{{ $dash($dJiwa['t']) }}</td>
                    <td>{{ $dash($dJiwa['k']) }}</td>
                    <td class="r">{{ $dashRp($dKotor['t']) }}</td>
                    <td class="r">{{ $dashRp($dKotor['k']) }}</td>
                </tr>
            @endforeach

            {{-- Subtotal golongan --}}
            @php
                $dGPeg   = ['t' => max(0, $groupNow['peg']   - $groupLalu['peg']),   'k' => max(0, $groupLalu['peg']   - $groupNow['peg'])];
                $dGIstri = ['t' => max(0, $groupNow['istri']  - $groupLalu['istri']), 'k' => max(0, $groupLalu['istri'] - $groupNow['istri'])];
                $dGAnak  = ['t' => max(0, $groupNow['anak']   - $groupLalu['anak']),  'k' => max(0, $groupLalu['anak']  - $groupNow['anak'])];
                $dGJiwa  = ['t' => max(0, $groupNow['jiwa']   - $groupLalu['jiwa']),  'k' => max(0, $groupLalu['jiwa']  - $groupNow['jiwa'])];
                $dGKotor = ['t' => max(0, $groupNow['kotor']  - $groupLalu['kotor']), 'k' => max(0, $groupLalu['kotor'] - $groupNow['kotor'])];

                foreach (['peg','istri','anak','jiwa','kotor'] as $k) {
                    $totLalu[$k] += $groupLalu[$k];
                    $totNow[$k]  += $groupNow[$k];
                }
            @endphp
            <tr class="b">
                <td></td>
                <td class="l b">Jumlah Gol {{ $groupRoman }}</td>
                <td></td>
                <td class="b">{{ $dash($groupLalu['peg']) }}</td>
                <td class="b">{{ $dash($groupLalu['istri']) }}</td>
                <td class="b">{{ $dash($groupLalu['anak']) }}</td>
                <td class="b">{{ $dash($groupLalu['jiwa']) }}</td>
                <td class="r b">{{ $dashRp($groupLalu['kotor']) }}</td>
                <td class="b">{{ $dash($groupNow['peg']) }}</td>
                <td class="b">{{ $dash($groupNow['istri']) }}</td>
                <td class="b">{{ $dash($groupNow['anak']) }}</td>
                <td class="b">{{ $dash($groupNow['jiwa']) }}</td>
                <td class="r b">{{ $dashRp($groupNow['kotor']) }}</td>
                <td class="b">{{ $dash($dGPeg['t']) }}</td>
                <td class="b">{{ $dash($dGPeg['k']) }}</td>
                <td class="b">{{ $dash($dGIstri['t']) }}</td>
                <td class="b">{{ $dash($dGIstri['k']) }}</td>
                <td class="b">{{ $dash($dGAnak['t']) }}</td>
                <td class="b">{{ $dash($dGAnak['k']) }}</td>
                <td class="b">{{ $dash($dGJiwa['t']) }}</td>
                <td class="b">{{ $dash($dGJiwa['k']) }}</td>
                <td class="r b">{{ $dashRp($dGKotor['t']) }}</td>
                <td class="r b">{{ $dashRp($dGKotor['k']) }}</td>
            </tr>
        @endforeach

        {{-- Grand total --}}
        @php
            $dTPeg   = ['t' => max(0, $totNow['peg']   - $totLalu['peg']),   'k' => max(0, $totLalu['peg']   - $totNow['peg'])];
            $dTIstri = ['t' => max(0, $totNow['istri']  - $totLalu['istri']), 'k' => max(0, $totLalu['istri'] - $totNow['istri'])];
            $dTAnak  = ['t' => max(0, $totNow['anak']   - $totLalu['anak']),  'k' => max(0, $totLalu['anak']  - $totNow['anak'])];
            $dTJiwa  = ['t' => max(0, $totNow['jiwa']   - $totLalu['jiwa']),  'k' => max(0, $totLalu['jiwa']  - $totNow['jiwa'])];
            $dTKotor = ['t' => max(0, $totNow['kotor']  - $totLalu['kotor']), 'k' => max(0, $totLalu['kotor'] - $totNow['kotor'])];
        @endphp
        <tr class="b">
            <td></td>
            <td class="l b">JUMLAH</td>
            <td></td>
            <td class="b">{{ $dash($totLalu['peg']) }}</td>
            <td class="b">{{ $dash($totLalu['istri']) }}</td>
            <td class="b">{{ $dash($totLalu['anak']) }}</td>
            <td class="b">{{ $dash($totLalu['jiwa']) }}</td>
            <td class="r b">{{ $dashRp($totLalu['kotor']) }}</td>
            <td class="b">{{ $dash($totNow['peg']) }}</td>
            <td class="b">{{ $dash($totNow['istri']) }}</td>
            <td class="b">{{ $dash($totNow['anak']) }}</td>
            <td class="b">{{ $dash($totNow['jiwa']) }}</td>
            <td class="r b">{{ $dashRp($totNow['kotor']) }}</td>
            <td class="b">{{ $dash($dTPeg['t']) }}</td>
            <td class="b">{{ $dash($dTPeg['k']) }}</td>
            <td class="b">{{ $dash($dTIstri['t']) }}</td>
            <td class="b">{{ $dash($dTIstri['k']) }}</td>
            <td class="b">{{ $dash($dTAnak['t']) }}</td>
            <td class="b">{{ $dash($dTAnak['k']) }}</td>
            <td class="b">{{ $dash($dTJiwa['t']) }}</td>
            <td class="b">{{ $dash($dTJiwa['k']) }}</td>
            <td class="r b">{{ $dashRp($dTKotor['t']) }}</td>
            <td class="r b">{{ $dashRp($dTKotor['k']) }}</td>
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
                <td>Pembantu Bendahara Pengeluaran</td>
            </tr>
            <tr>
                <td>Bendahara Pengeluaran</td>
                <td>Untuk Urusan Gaji</td>
            </tr>
            <tr style="height:30px;"><td></td><td></td></tr>
            <tr>
                <td><span class="name">{{ $namaBendahara }}</span></td>
                <td><span class="name">{{ $namaPenyiap }}</span></td>
            </tr>
            <tr>
                <td>{{ $nipBendahara }}</td>
                <td>{{ $nipPenyiap }}</td>
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
