<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $jenis_surat ?? 'Surat Pengantar' }} – {{ $nomor_surat ?? '' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm 20mm 25mm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
        }

        /* ── toolbar no-print ─────────────────────────────────── */
        .no-print {
            padding: 8px 14px;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .no-print button {
            padding: 5px 16px;
            background: #1d4ed8;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 9pt;
        }
        .no-print button.btn-back {
            background: #64748b;
        }
        .no-print span {
            font-size: 8pt;
            color: #64748b;
        }
        @media print {
            .no-print { display: none !important; }
        }

        /* ── page wrapper ─────────────────────────────────────── */
        .page {
            max-width: 170mm;
            margin: 0 auto;
            padding: 10mm 0;
        }

        /* ── header kop surat ─────────────────────────────────── */
        .kop {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .kop-logo {
            width: 20mm;
            margin-right: 6mm;
            flex-shrink: 0;
        }
        .kop-logo img {
            width: 100%;
            height: auto;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        .kop-text .instansi-atas {
            font-size: 11pt;
            font-weight: normal;
            line-height: 1.3;
        }
        .kop-text .instansi-nama {
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.2;
        }
        .kop-text .instansi-alamat {
            font-size: 9pt;
            line-height: 1.4;
        }
        .kop-text .instansi-email {
            font-size: 9pt;
            font-style: italic;
            text-decoration: underline;
        }

        /* ── tanggal kanan ───────────────────────────────────── */
        .tanggal-baris {
            text-align: right;
            margin-top: 6mm;
            margin-bottom: 6mm;
            font-size: 11pt;
        }

        /* ── alamat tujuan ───────────────────────────────────── */
        .kepada-blok {
            margin-bottom: 6mm;
            font-size: 11pt;
            line-height: 1.5;
        }
        .kepada-blok .tempat {
            text-decoration: underline;
            font-weight: bold;
        }

        /* ── judul surat ─────────────────────────────────────── */
        .judul-blok {
            text-align: center;
            margin-bottom: 4mm;
        }
        .judul-blok .judul {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
        }
        .judul-blok .nomor {
            font-size: 11pt;
        }

        /* ── tabel isi ───────────────────────────────────────── */
        table.isi {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8mm;
            font-size: 11pt;
        }
        table.isi th,
        table.isi td {
            border: 1pt solid #000;
            padding: 2mm 3mm;
            vertical-align: top;
        }
        table.isi th {
            font-weight: bold;
            text-align: center;
            background: #fff;
        }
        .col-no    { width: 10mm; text-align: center; }
        .col-jenis { width: 60mm; }
        .col-byk   { width: 22mm; text-align: center; }
        .col-ket   { /* remaining */ }
        .ket-text  { font-size: 10.5pt; }

        /* ── footer ttd ──────────────────────────────────────── */
        .footer-ttd {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 2mm;
            font-size: 11pt;
        }
        .col-penerima {
            width: 55%;
        }
        .col-pengirim {
            width: 40%;
            text-align: center;
        }
        .ttd-garis {
            border-bottom: 1pt solid #000;
            margin-top: 12mm;
            margin-bottom: 2mm;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        .penerima-baris {
            margin-bottom: 2mm;
        }
        .penerima-garis {
            display: block;
            width: 55mm;
            border-bottom: 1pt solid #000;
            margin-top: 14mm;
            margin-bottom: 1mm;
        }
        .penerima-garis2 {
            display: block;
            width: 55mm;
            border-bottom: 1pt solid #000;
            margin-top: 6mm;
            margin-bottom: 1mm;
        }
    </style>
</head>
<body>
    {{-- Toolbar --}}
    <div class="no-print">
        <button onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
        <button class="btn-back" onclick="history.back()">&#8592; Kembali</button>
        <span>Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    <div class="page">

        {{-- KOP SURAT --}}
        <div class="kop">
            <div class="kop-logo">
                <img src="{{ asset('images/logo-wonosobo.png') }}" alt="Logo Wonosobo">
            </div>
            <div class="kop-text">
                <div class="instansi-atas">PEMERINTAH KABUPATEN WONOSOBO</div>
                <div class="instansi-nama">KECAMATAN WATUMALANG</div>
                <div class="instansi-alamat">
                    Jalan Jebeng Lintang Nomor 29 Watumalang Wonosobo, Jawa Tengah, 56352<br>
                    Telpon ( 0286 ) 3304957<br>
                    Laman: kecamatanwatumalang.wonosobokab.go.id
                </div>
                <div class="instansi-email">Pos-el watumalang08@gmail.com</div>
            </div>
        </div>

        {{-- TANGGAL --}}
        <div class="tanggal-baris">
            Wonosobo, {{ $tanggal }}
        </div>

        {{-- KEPADA --}}
        <div class="kepada-blok">
            Yth. {{ $kepada }}<br>
            di<br>
            <span class="tempat">TEMPAT</span>
        </div>

        {{-- JUDUL --}}
        <div class="judul-blok">
            <div class="judul">{{ strtoupper($jenis_surat) }}</div>
            <div class="nomor">Nomor : {{ $nomor_surat }}</div>
        </div>

        {{-- TABEL ISI --}}
        <table class="isi">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-jenis">JENIS SURAT/BARANG YANG DI KIRIM</th>
                    <th class="col-byk">BANYAKNYA</th>
                    <th class="col-ket">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-no">1.</td>
                    <td class="col-jenis" style="white-space:pre-wrap;">{{ $isi_pengantar }}</td>
                    <td class="col-byk">1 Bendel</td>
                    <td class="col-ket ket-text">
                        Dikirim dengan hormat untuk menjadikan perhatian dan dipergunakan bagaimana mestinya.
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- FOOTER TTD --}}
        <div class="footer-ttd">
            {{-- Penerima --}}
            <div class="col-penerima">
                <div class="penerima-baris">Diterima tanggal …………………..</div>
                <div class="penerima-baris">Penerima</div>
                <span class="penerima-garis"></span>
                <div style="margin-bottom:1mm;">…………………………………….</div>
                <span class="penerima-garis2"></span>
                <div style="margin-bottom:4mm;">…………………………………….</div>
                <div>Nomor telepon / WA …………………</div>
            </div>

            {{-- Pengirim --}}
            <div class="col-pengirim">
                <div>Pengirim</div>
                <div>{{ $jabatan }}</div>
                <div class="ttd-garis" style="margin-top:16mm;"></div>
                <div class="ttd-nama">{{ $nama_camat }}</div>
                <div>{{ $pangkat }} / {{ $golongan }}</div>
                <div>NIP. {{ $nip }}</div>
            </div>
        </div>

    </div>{{-- /.page --}}

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
