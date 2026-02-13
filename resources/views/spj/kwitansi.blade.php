<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $spj->nomor_kwitansi ?? $spj->id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: 216mm 165mm; margin: 5mm 6mm; }

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

        table.kw {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.kw td {
            font-size: 8.5pt;
            padding: 1px 3px;
            vertical-align: top;
        }

        .b  { font-weight: bold; }
        .c  { text-align: center; }
        .r  { text-align: right; }
        .vm { vertical-align: middle; }
        .vb { vertical-align: bottom; }
        .it { font-style: italic; }
        .u  { text-decoration: underline; }
        .sm { font-size: 7.5pt; }
        .xs { font-size: 7pt; }

        /* Border helpers — 0.5pt solid */
        .ba  { border: 0.5pt solid #000; }
        .bt  { border-top: 0.5pt solid #000; }
        .bb  { border-bottom: 0.5pt solid #000; }
        .bl  { border-left: 0.5pt solid #000; }
        .br  { border-right: 0.5pt solid #000; }

        .h-ttd { height: 14mm; }

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
        $jumlah     = (int) ($spj->jumlah ?? 0);
        $ppn        = (int) ($spj->ppn    ?? 0);
        $pph21      = (int) ($spj->pph21  ?? 0);
        $pph22      = (int) ($spj->pph22  ?? 0);
        $pph23      = (int) ($spj->pph23  ?? 0);
        $totalPajak = $ppn + $pph21 + $pph22 + $pph23;
        $bersih     = $jumlah - $totalPajak;

        $rp = fn (int $v): string => number_format($v, 0, ',', '.') . ',00';

        $terbilang = '-';
        if ($jumlah > 0 && class_exists(NumberFormatter::class)) {
            $fmt = new NumberFormatter('id', NumberFormatter::SPELLOUT);
            $res = $fmt->format($jumlah);
            $terbilang = (is_string($res) && $res !== '')
                ? ucfirst($res) . ' Rupiah'
                : $jumlah . ' Rupiah';
        } elseif ($jumlah > 0) {
            $terbilang = $jumlah . ' Rupiah';
        }

        $tanggalRaw  = $spj->tanggal
            ? \Carbon\Carbon::parse($spj->tanggal)->translatedFormat('d F Y')
            : null;

        $personil    = $spj->personil;
        $rincian     = $spj->dpaRincianBelanja;
        $subKegiatan = $rincian?->subKegiatan;

        $nomor   = $spj->nomor_kwitansi ?: '';
        $tahun   = $spj->tahun ?: now()->year;

        $subKegText = $subKegiatan
            ? $subKegiatan->kode . ' ' . $subKegiatan->nama
            : '';

        $kodeRek = $rincian ? $rincian->kode_rekening : '';

        $dpa       = $subKegiatan?->dpa;
        $kodeSkpd  = '';
        if ($dpa && $dpa->organisasi) {
            $kodeSkpd = preg_match('/^([\d.]+)/', $dpa->organisasi, $m) ? $m[1] : '';
        }
    @endphp

    {{--
    GRID: 20 kolom × 5% masing-masing

    ATURAN GARIS (mengikuti docx):
      • Garis luar  : bl pada kolom pertama, br pada kolom terakhir setiap baris
      • Garis atas  : bt hanya pada baris pertama (logo/PEMERINTAH)
      • Divider Header/Body : bb pada baris 7 (Tahun Anggaran)
      • Divider vertikal body : br pada sel terakhir bagian kiri (kol 12)
      • Kotak judul SURAT BUKTI: bl br pada sel kiri, br pada sel kanan
      • Kotak tax (Jumlah kotor/Pajak/Bersih headers): bb, br per sub-kolom
      • Tax values: bb (menutup kotak tax)
      • Divider Body/TTD: bb pada baris terakhir body (baris NIP penerima)
      • Area TTD: bl br per kolom, bb pada baris terakhir
      • TIDAK ADA bb individual pada baris-baris isi (Sudah terima, Uang sejumlah, dll.)
    --}}
    <table class="kw">
        <colgroup>
            @for ($i = 0; $i < 20; $i++)<col style="width:5%">@endfor
        </colgroup>

        {{-- ══ ROW 1 : Logo + PEMERINTAH ══ --}}
        <tr>
            <td colspan="3" class="bl bt" rowspan="3"
                style="text-align:center; vertical-align:middle; padding:2px;">
                <img src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo" style="width:48px; height:auto;">
            </td>
            <td colspan="17" class="bt br b c" style="font-size:11pt; padding:2px 4px; vertical-align:middle;">
                PEMERINTAH KABUPATEN WONOSOBO
            </td>
        </tr>
        {{-- ROW 2 : Nomor + Lembar ke --}}
        <tr>
            <td colspan="17" class="br sm" style="padding:1px 3px; vertical-align:bottom;">
                <div style="display:flex; align-items:baseline;">
                    <div style="flex:1;"></div>
                    <div>Nomor :&nbsp; {{ $nomor ?: '900 / ____________ / ' . $tahun }}</div>
                    <div style="flex:1; text-align:right; font-size:6.5pt;">Lembar ke &nbsp;: I / II / III / IV</div>
                </div>
            </td>
        </tr>
        {{-- ROW 3 : kosong penutup logo --}}
        <tr>
            <td colspan="17" class="br" style="height:3mm;"></td>
        </tr>

        {{-- ══ ROWS 4–7 : Data header ══ --}}
        {{-- Garis: bl (kiri luar) + br (kanan luar) saja, tanpa horizontal internal --}}
        <tr>
            <td colspan="6" class="bl sm" style="padding:1px 3px;">SKPD / Kode Rekening</td>
            <td colspan="1" class="sm c">:</td>
            <td colspan="13" class="br sm" style="padding:1px 3px;">KECAMATAN WATUMALANG &nbsp;/ {{ $kodeSkpd }}</td>
        </tr>
        <tr>
            <td colspan="6" class="bl sm" style="padding:1px 3px;">Pengguna Angg. / Kuasa Peng. Angg.</td>
            <td colspan="1" class="sm c">:</td>
            <td colspan="13" class="br sm" style="padding:1px 3px;">{{ $pa ? strtoupper($pa->nama) : '—' }}</td>
        </tr>
        <tr>
            <td colspan="6" class="bl sm" style="padding:1px 3px;">Bendahara Pengeluaran</td>
            <td colspan="1" class="sm c">:</td>
            <td colspan="13" class="br sm" style="padding:1px 3px;">{{ $bendahara ? strtoupper($bendahara->nama) : '—' }}</td>
        </tr>
        {{-- ROW 7 : Tahun – bb penutup header --}}
        <tr>
            <td colspan="6" class="bl bb sm" style="padding:1px 3px;">Tahun Anggaran</td>
            <td colspan="1" class="bb sm c">:</td>
            <td colspan="13" class="br bb sm" style="padding:1px 3px;">{{ $tahun }}</td>
        </tr>

        {{-- ══ ROW 8 : SURAT BUKTI PEMBAYARAN | KETERANGAN ══ --}}
        {{-- Garis: bl br pada kiri (divider tengah via br), br saja pada kanan --}}
        <tr>
            <td colspan="12" class="bl br b c vm" style="font-size:9.5pt; padding:3px 4px;">
                SURAT BUKTI PEMBAYARAN
            </td>
            <td colspan="8" class="br b c vm" style="font-size:8.5pt; padding:3px 4px;">
                KETERANGAN
            </td>
        </tr>

        {{-- ══ ROW 9 : Sudah terima dari ══ --}}
        {{-- Kiri: bl col1, br col12 (divider vertikal). Kanan: br col20 saja --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;">Sudah terima dari</td>
            <td colspan="1" class="c">:</td>
            <td colspan="6" class="br" style="padding:1px 3px;">Bendahara Pengeluaran</td>
            <td colspan="8" class="br" style="padding:1px 3px;">Barang &#8211; barang telah masuk buku</td>
        </tr>

        {{-- ══ ROW 10 : Uang sejumlah ══ --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;">Uang sejumlah</td>
            <td colspan="1" class="c">:</td>
            <td colspan="6" class="br b" style="padding:1px 3px;">Rp. &nbsp;&nbsp; {{ $rp($jumlah) }}</td>
            <td colspan="8" class="br" style="padding:1px 3px;">persediaan / inventaris pada tgl &#8230;&#8230;&#8230;</td>
        </tr>

        {{-- ══ ROW 11–13 : Terbilang (kiri rowspan=3) ══ --}}
        {{-- Row 11 : kanan kosong --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;" rowspan="3">Terbilang</td>
            <td colspan="1" rowspan="3">:</td>
            <td colspan="6" class="br it" style="padding:2px 3px;" rowspan="3">{{ $terbilang }}</td>
            <td colspan="8" class="br" style="padding:1px 3px;"></td>
        </tr>
        {{-- Row 12 : kanan = Jumlah kotor | Pajak | Jumlah bersih headers --}}
        {{-- Sub-kolom: 3+2+3 = 8 cols. Garis: bt bb br per sub-kolom, bl pada pertama --}}
        <tr>
            <td colspan="3" class="bl bt bb br c xs b" style="padding:1px 2px;">Jumlah kotor</td>
            <td colspan="2" class="bt bb br c xs b"    style="padding:1px 2px;">Pajak</td>
            <td colspan="3" class="bt bb br c xs b"    style="padding:1px 2px;">Jumlah bersih</td>
        </tr>
        {{-- Row 13 : kanan = nilai (naik dari ROW 14) --}}
        <tr>
            <td colspan="3" class="bl bb br c xs" style="padding:1px 2px;">{{ $rp($jumlah) }}</td>
            <td colspan="2" class="bb br c xs"   style="padding:1px 2px;">{{ $rp($totalPajak) }}</td>
            <td colspan="3" class="bb br c xs"   style="padding:1px 2px;">{{ $rp($bersih) }}</td>
        </tr>

        {{-- ══ ROW 14 : Yaitu untuk pembayaran + Uraian Pajak (naik dari ROW 15) ══ --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;">Yaitu untuk pembayaran</td>
            <td colspan="1" class="c">:</td>
            <td colspan="6" class="br" style="padding:1px 3px;">{{ $spj->uraian ?: '' }}</td>
            <td colspan="8" class="br b" style="padding:1px 3px;">Uraian Pajak :</td>
        </tr>

        {{-- ══ ROW 15 : Sub Kegiatan + kanan kosong ══ --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;">Berguna buat Sub Kegiatan</td>
            <td colspan="1" class="c">:</td>
            <td colspan="6" class="br sm" style="padding:1px 3px;">{{ $subKegText }}</td>
            <td colspan="8" class="br"></td>
        </tr>

        {{-- ══ ROW 16 : Sub Kegiatan lanjut + PPn ══ --}}
        <tr>
            <td colspan="5" class="bl"></td>
            <td colspan="1"></td>
            <td colspan="6" class="br sm" style="padding:1px 3px;"></td>
            <td colspan="4" style="padding:1px 3px;">1.&nbsp;PPn.</td>
            <td colspan="1" style="padding:1px 3px;">Rp.</td>
            <td colspan="3" class="br r" style="padding:1px 3px;">{{ $rp($ppn) }}</td>
        </tr>

        {{-- ══ ROW 17 : Kode rekening + PPh 21 ══ --}}
        <tr>
            <td colspan="5" class="bl" style="padding:1px 3px;">Kode rekening</td>
            <td colspan="1" class="c">:</td>
            <td colspan="6" class="br b" style="padding:1px 3px;">{{ $kodeSkpd && $kodeRek ? $kodeSkpd . '.' . $kodeRek : $kodeRek }}</td>
            <td colspan="4" style="padding:1px 3px;">2.&nbsp;PPh 21</td>
            <td colspan="1" style="padding:1px 3px;">Rp.</td>
            <td colspan="3" class="br r" style="padding:1px 3px;">{{ $rp($pph21) }}</td>
        </tr>

        {{-- ══ ROW 18 : Tanggal + PPh 22 ══ --}}
        <tr>
            <td colspan="5" class="bl"></td>
            <td colspan="1"></td>
            <td colspan="6" class="br c sm" style="padding:1px 3px;">
                Wonosobo,{{ $tanggalRaw ? ' ' . $tanggalRaw : '' }}
            </td>
            <td colspan="4" style="padding:1px 3px;">3.&nbsp;PPh 22</td>
            <td colspan="1" style="padding:1px 3px;">Rp.</td>
            <td colspan="3" class="br r" style="padding:1px 3px;">{{ $rp($pph22) }}</td>
        </tr>

        {{-- ══ ROW 19 : Yang berhak menerima + PPh 23 ══ --}}
        <tr>
            <td colspan="5" class="bl"></td>
            <td colspan="1"></td>
            <td colspan="6" class="br c sm" style="padding:1px 3px;">Yang berhak menerima</td>
            <td colspan="4" style="padding:1px 3px;">4.&nbsp;PPh 23</td>
            <td colspan="1" style="padding:1px 3px;">Rp.</td>
            <td colspan="3" class="br bb r" style="padding:1px 3px;">{{ $rp($pph23) }}</td>
        </tr>

        {{-- ══ ROW 20 : Ruang TTD + Jumlah pajak ══ --}}
        <tr style="height:11mm;">
            <td colspan="5" class="bl"></td>
            <td colspan="1"></td>
            <td colspan="6" class="br"></td>
            <td colspan="4" class="c b" style="padding:1px 3px; vertical-align:top;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Jumlah</td>
            <td colspan="1" style="padding:1px 3px; vertical-align:top;">Rp.</td>
            <td colspan="3" class="br r" style="padding:1px 3px; vertical-align:top;">{{ $rp($totalPajak) }}</td>
        </tr>

        {{-- ══ ROW 21 : Nama penerima + Pengeluaran/pembelian ══ --}}
        <tr>
            <td colspan="5" class="bl"></td>
            <td colspan="1"></td>
            <td colspan="6" class="br c b u vb" style="padding:1px 3px;">Terlampir</td>
            <td colspan="8" class="br c sm" style="padding:1px 3px;">
                Pengeluaran / pembelian dilakukan berdasarkan :
            </td>
        </tr>

        {{-- ══ ROW 22 : NIP penerima + bb penutup body ══ --}}
        <tr style="height:6mm;">
            <td colspan="5" class="bl bb"></td>
            <td colspan="1" class="bb"></td>
            <td colspan="6" class="br bb c sm" style="padding:1px 3px;"></td>
            <td colspan="8" class="br bb"></td>
        </tr>

        {{-- ══ SIGNATURE AREA (rows 23–27) ══ --}}
        {{-- 4 kolom sama lebar: 5 + 5 + 5 + 5 = 20 --}}
        {{-- Garis: bl/br outer + br divider antar kolom. bb pada baris terakhir --}}

        {{-- ROW 23 : Header TTD — 4+5+6+5=20 --}}
        <tr>
            <td colspan="4"  class="bl br"></td>
            <td colspan="5"  class="br b c" style="padding:1px 3px;">Mengetahui &amp; Menyetujui</td>
            <td colspan="6"  class="br b c" style="padding:1px 3px;">Yang Membayarkan</td>
            <td colspan="5"  class="br"></td>
        </tr>

        {{-- ROW 24 : Sub-label TTD --}}
        <tr>
            <td colspan="4"  class="bl br c sm" style="padding:1px 3px;">Yang menerima barang</td>
            <td colspan="5"  class="br c sm"    style="padding:1px 3px;">Pengguna Anggaran</td>
            <td colspan="6"  class="br c sm"    style="padding:1px 3px;">Bendahara Pengeluaran</td>
            <td colspan="5"  class="br c sm"    style="padding:1px 3px;">PPTK</td>
        </tr>

        {{-- ROW 25 : Ruang TTD --}}
        <tr class="h-ttd">
            <td colspan="4"  class="bl br"></td>
            <td colspan="5"  class="br"></td>
            <td colspan="6"  class="br"></td>
            <td colspan="5"  class="br"></td>
        </tr>

        {{-- ROW 26 : Nama pejabat --}}
        <tr>
            <td colspan="4"  class="bl br c" style="padding:1px 3px;">( &#8230;&#8230;&#8230;&#8230;&#8230;&#8230;&#8230;&#8230;. )</td>
            <td colspan="5"  class="br b c"  style="padding:1px 3px;">{{ $pa ? strtoupper($pa->nama) : '—' }}</td>
            <td colspan="6"  class="br b c"  style="padding:1px 3px;">{{ $bendahara ? strtoupper($bendahara->nama) : '—' }}</td>
            <td colspan="5"  class="br b c"  style="padding:1px 3px;">{{ $spj->personil ? strtoupper($spj->personil->nama) : '—' }}</td>
        </tr>

        {{-- ROW 27 : NIP pejabat + bb penutup bawah --}}
        <tr>
            <td colspan="4"  class="bl br bb"></td>
            <td colspan="5"  class="br bb c sm" style="padding:1px 3px;">{{ $pa?->nip ? 'NIP. ' . $pa->nip : '' }}</td>
            <td colspan="6"  class="br bb c sm" style="padding:1px 3px;">{{ $bendahara?->nip ? 'NIP. ' . $bendahara->nip : '' }}</td>
            <td colspan="5"  class="br bb c sm" style="padding:1px 3px;">{{ $spj->personil?->nip ? 'NIP. ' . $spj->personil->nip : '' }}</td>
        </tr>
    </table>

    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
