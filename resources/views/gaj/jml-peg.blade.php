<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jumlah Pegawai {{ strtoupper($gaj->jenis) }} {{ $gaj->periode }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }

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

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10px;
            line-height: 1.5;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-top: 8px;
        }
        table.data th,
        table.data td {
            border: 0.5pt solid #000;
            padding: 2px 4px;
            text-align: center;
            vertical-align: middle;
        }
        table.data th {
            font-weight: bold;
            background: #fff;
        }
        .b { font-weight: bold; }
        td.l { text-align: left; }

        .ttd-area {
            margin-top: 20px;
            font-size: 9pt;
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
        <a href="{{ route('gaj.excel.jml-peg', $gaj) }}" style="padding:3px 12px; background:#16a34a; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:8pt; text-decoration:none;">Unduh Excel</a>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    @php
        $pnsGroups = [
            'IV' => [['IV/E','IV/E'],['IV/D','IV/D'],['IV/C','IV/C'],['IV/B','IV/B'],['IV/A','IV/A']],
            'III' => [['III/D','III/D'],['III/C','III/C'],['III/B','III/B'],['III/A','III/A']],
            'II' => [['II/D','II/D'],['II/C','II/C'],['II/B','II/B'],['II/A','II/A']],
            'I' => [['I/D','I/D'],['I/C','I/C'],['I/B','I/B'],['I/A','I/A']],
        ];
        $pppkGroups = [
            'IV' => array_map(fn($g) => ["GOL-{$g}", "Gol. {$g}"], range(17,13)),
            'III' => array_map(fn($g) => ["GOL-{$g}", "Gol. {$g}"], range(12,9)),
            'II' => array_map(fn($g) => ["GOL-{$g}", "Gol. {$g}"], range(8,5)),
            'I' => array_map(fn($g) => ["GOL-{$g}", "Gol. {$g}"], range(4,1)),
        ];
        $rowGroups = $isPns ? $pnsGroups : $pppkGroups;
        $eselonCols = $isPns ? [1,2,3,4] : ['I','II','III','IV','STAF'];
        $eselonLabels = $isPns ? ['I','II','III','IV'] : ['I','II','III','IV','STAF'];
        $colCount = count($eselonCols);
    @endphp

    <div class="title">
        DAFTAR ISIAN JUMLAH PEGAWAI<br>
        PER GOLONGAN DAN JABATAN<br>
        KANTOR : {{ $gaj->nama_satker }}<br>
        <br>
        BULAN : {{ strtoupper($gaj->periode) }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">GOLONGAN</th>
                <th colspan="{{ $colCount }}">E S E L O N</th>
                <th rowspan="2">TOTAL</th>
            </tr>
            <tr>
                @foreach ($eselonLabels as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
            <tr>
                <th style="font-size:7pt; font-weight:normal;">1</th>
                @for ($c = 2; $c <= $colCount + 1; $c++)
                    <th style="font-size:7pt; font-weight:normal;">{{ $c }}</th>
                @endfor
                <th style="font-size:7pt; font-weight:normal;">{{ $colCount + 2 }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotal = array_fill_keys($isPns ? [1,2,3,4] : ['I','II','III','IV','STAF'], 0);
                $grandRowTotal = 0;
            @endphp

            @foreach ($rowGroups as $groupRoman => $rows)
                @php
                    $groupSubtotal = array_fill_keys($eselonCols, 0);
                    $groupRowTotal = 0;
                @endphp

                @foreach ($rows as $row)
                    @php
                        $key = $row[0];
                        $label = $row[1];
                        $rowData = $data[$key] ?? array_fill_keys($eselonCols, 0);
                        $rowTotal = 0;
                        foreach ($eselonCols as $col) {
                            $val = $rowData[$col] ?? 0;
                            $rowTotal += $val;
                            $groupSubtotal[$col] += $val;
                        }
                        $groupRowTotal += $rowTotal;
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        @foreach ($eselonCols as $col)
                            <td>{{ $rowData[$col] ?? 0 }}</td>
                        @endforeach
                        <td>{{ $rowTotal }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal row --}}
                @php
                    foreach ($eselonCols as $col) {
                        $grandTotal[$col] += $groupSubtotal[$col];
                    }
                    $grandRowTotal += $groupRowTotal;
                @endphp
                <tr class="b">
                    <td class="b">Jumlah Golongan {{ $groupRoman }}</td>
                    @foreach ($eselonCols as $col)
                        <td class="b">{{ $groupSubtotal[$col] }}</td>
                    @endforeach
                    <td class="b">{{ $groupRowTotal }}</td>
                </tr>
            @endforeach

            {{-- Grand total --}}
            <tr class="b">
                <td class="b">Jumlah I s/d IV</td>
                @foreach ($eselonCols as $col)
                    <td class="b">{{ $grandTotal[$col] }}</td>
                @endforeach
                <td class="b">{{ $grandRowTotal }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TTD Section --}}
    <div class="ttd-area">
        <table>
            <tr>
                <td style="width:50%;"></td>
                <td style="width:50%;"><span id="tglCetakLabel"></span></td>
            </tr>
            <tr>
                <td>Bendahara Pengeluaran</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>Pembantu Bendahara Pengeluaran</td>
            </tr>
            <tr>
                <td></td>
                <td>Untuk Urusan Gaji</td>
            </tr>
            <tr style="height:40px;"><td></td><td></td></tr>
            <tr>
                <td><span class="name">{{ $namaBendahara }}</span></td>
                <td></td>
            </tr>
            <tr>
                <td>{{ $nipBendahara }}</td>
                <td></td>
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
