<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $jenis_surat ?? 'Surat Pengantar' }} – {{ $nomor_surat ?? '' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 20mm 20mm 25mm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Arial", Times, serif;
            font-size: 12pt;
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
            flex-wrap: wrap;
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
        .no-print button.btn-back  { background: #64748b; }
        .no-print button.btn-edit  { background: #d97706; }
        .no-print button.btn-apply { background: #16a34a; }
        .no-print button.btn-cancel{ background: #64748b; }
        .no-print label.ttd-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 8.5pt;
            color: #334155;
            cursor: pointer;
            user-select: none;
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            border-radius: 4px;
            padding: 4px 10px;
        }
        .no-print label.ttd-toggle input[type="checkbox"] {
            width: 14px;
            height: 14px;
            cursor: pointer;
        }
        .no-print span.hint {
            font-size: 8pt;
            color: #64748b;
        }

        /* ── panel edit ──────────────────────────────────────── */
        #edit-panel {
            display: none;
            background: #fefce8;
            border-bottom: 1px solid #fde047;
            padding: 10px 16px;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        #edit-panel .ep-row {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 160px;
        }
        #edit-panel .ep-row.ep-wide {
            flex: 1;
            min-width: 260px;
        }
        #edit-panel label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #78350f;
        }
        #edit-panel input,
        #edit-panel textarea {
            font-size: 9pt;
            padding: 3px 6px;
            border: 1px solid #fcd34d;
            border-radius: 3px;
            background: #fff;
            width: 100%;
            font-family: Arial, sans-serif;
        }
        #edit-panel textarea { resize: vertical; }
        #edit-panel .ep-actions {
            display: flex;
            gap: 6px;
            align-items: flex-end;
            padding-top: 16px;
        }

        @media print {
            .no-print,
            #edit-panel { display: none !important; }
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
        .kop-logo img { width: 100%; height: auto; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text .instansi-atas  { font-size: 11pt; font-weight: normal; line-height: 1.3; }
        .kop-text .instansi-nama  { font-size: 15pt; font-weight: bold; line-height: 1.2; }
        .kop-text .instansi-alamat{ font-size: 9pt; line-height: 1.4; }
        .kop-text .instansi-email { font-size: 9pt; font-style: italic; text-decoration: underline; }

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
        .kepada-blok .tempat { text-decoration: underline; font-weight: bold; }

        /* ── judul surat ─────────────────────────────────────── */
        .judul-blok { text-align: center; margin-bottom: 4mm; }
        .judul-blok .judul { font-size: 12pt; font-weight: bold; text-decoration: underline; }
        .judul-blok .nomor { font-size: 11pt; }

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
        table.isi th { font-weight: bold; text-align: center; background: #fff; }
        .col-no    { width: 10mm; text-align: center; }
        .col-jenis { width: 60mm; }
        .col-byk   { width: 22mm; text-align: center; }
        .ket-text  { font-size: 10.5pt; }

        /* ── footer ttd ──────────────────────────────────────── */
        .footer-ttd {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 2mm;
            font-size: 11pt;
        }
        .col-penerima { width: 55%; }
        .col-pengirim { width: 40%; text-align: center; }
        .ttd-garis    { margin-bottom: 2mm; }
        .ttd-nama     { font-weight: bold; text-decoration: underline; }
        .penerima-baris { margin-bottom: 2mm; }
        .ttd-ruang-penerima { height: 16mm; }
        .ttd-ruang-pengirim { height: 22mm; }
        .srikandi-wrapper {
            display: none;
            height: 25mm;
            align-items: center;
            justify-content: center;
        }
        .srikandi-code {
            font-family: "Courier New", Courier, monospace;
            font-size: 9pt;
            color: #1e40af;
            line-height: 1;
        }
    </style>
</head>
<body>
    {{-- ── Toolbar ── --}}
    <div class="no-print">
        <label class="ttd-toggle" title="Tambahkan kode TTD Srikandi pada area tanda tangan pengirim">
            <input type="checkbox" id="toggleSrikandi" onchange="toggleSrikandiMode(this.checked)">
            &#9997; TTD Srikandi
        </label>
        <button class="btn-edit" onclick="openEdit()">&#9998; Edit</button>
        <button onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
        <button class="btn-back" onclick="history.back()">&#8592; Kembali</button>
        <span class="hint">Pilih "Save as PDF" pada dialog cetak untuk menyimpan sebagai PDF.</span>
    </div>

    {{-- ── Panel Edit ── --}}
    <div id="edit-panel">
        <div class="ep-row">
            <label>Tanggal</label>
            <input type="text" id="ep-tanggal">
        </div>
        <div class="ep-row">
            <label>Nomor Surat</label>
            <input type="text" id="ep-nomor">
        </div>
        <div class="ep-row ep-wide">
            <label>Kepada (Yth.)</label>
            <textarea id="ep-kepada" rows="2"></textarea>
        </div>
        <div class="ep-row ep-wide">
            <label>Isi Pengantar</label>
            <textarea id="ep-isi" rows="3"></textarea>
        </div>
        <div class="ep-row">
            <label>Nama Pengirim</label>
            <input type="text" id="ep-nama">
        </div>
        <div class="ep-row">
            <label>Pangkat / Golongan</label>
            <input type="text" id="ep-pangkat">
        </div>
        <div class="ep-row">
            <label>NIP</label>
            <input type="text" id="ep-nip">
        </div>
        <div class="ep-actions">
            <button class="btn-apply" onclick="applyEdit()">&#10003; Terapkan</button>
            <button class="btn-cancel" onclick="closeEdit()">Batal</button>
        </div>
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
            Wonosobo, <span id="val-tanggal">{{ $tanggal }}</span>
        </div>

        {{-- KEPADA --}}
        <div class="kepada-blok">
            Yth. <span id="val-kepada">{!! nl2br(e($kepada)) !!}</span><br>
            di<br>
            <span class="tempat">TEMPAT</span>
        </div>

        {{-- JUDUL --}}
        <div class="judul-blok">
            <div class="judul" id="val-jenis">{{ strtoupper($jenis_surat) }}</div>
            <div class="nomor">Nomor : <span id="val-nomor">{{ $nomor_surat }}</span></div>
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
                    <td id="val-isi" class="col-jenis" style="white-space:pre-wrap;">{{ $isi_pengantar }}</td>
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
                <div class="ttd-ruang-penerima"></div>
                <div style="margin-bottom:1mm;">…………………………………….</div>
                <div style="margin-bottom:4mm;">…………………………………….</div>
                <div>Nomor telepon / WA …………………</div>
            </div>

            {{-- Pengirim --}}
            <div class="col-pengirim">
                <div>Pengirim</div>
                <div>{{ $jabatan }}</div>

                <div id="ttd-normal">
                    <div class="ttd-ruang-pengirim"></div>
                    <div class="ttd-garis"></div>
                </div>

                <div id="ttd-srikandi" class="srikandi-wrapper">
                    <span class="srikandi-code">${ttd_pengirim}</span>
                </div>

                <div class="ttd-nama" id="val-nama">{{ $nama_camat }}</div>
                <div id="val-pangkat">{{ $pangkat }} / {{ $golongan }}</div>
                <div>NIP. <span id="val-nip">{{ $nip }}</span></div>
            </div>
        </div>

    </div>{{-- /.page --}}

    <script>
        /* ── Srikandi toggle ────────────────────────────── */
        function toggleSrikandiMode(enabled) {
            var normal   = document.getElementById('ttd-normal');
            var srikandi = document.getElementById('ttd-srikandi');
            if (enabled) {
                normal.style.display   = 'none';
                srikandi.style.display = 'flex';
            } else {
                normal.style.display   = 'block';
                srikandi.style.display = 'none';
            }
        }

        /* ── Edit panel ─────────────────────────────────── */
        function openEdit() {
            // Baca nilai saat ini dari DOM lalu isi input panel
            document.getElementById('ep-tanggal').value =
                document.getElementById('val-tanggal').textContent.trim();

            document.getElementById('ep-nomor').value =
                document.getElementById('val-nomor').textContent.trim();

            // kepada: konversi <br> kembali ke newline untuk textarea
            document.getElementById('ep-kepada').value =
                document.getElementById('val-kepada').innerHTML
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/&amp;/g,'&').replace(/&lt;/g,'<')
                    .replace(/&gt;/g,'>').replace(/&quot;/g,'"');

            document.getElementById('ep-isi').value =
                document.getElementById('val-isi').textContent;

            document.getElementById('ep-nama').value =
                document.getElementById('val-nama').textContent.trim();

            // pangkat / golongan disimpan bersama dalam satu elemen "A / B"
            document.getElementById('ep-pangkat').value =
                document.getElementById('val-pangkat').textContent.trim();

            document.getElementById('ep-nip').value =
                document.getElementById('val-nip').textContent.trim();

            document.getElementById('edit-panel').style.display = 'flex';
        }

        function closeEdit() {
            document.getElementById('edit-panel').style.display = 'none';
        }

        function applyEdit() {
            // Tanggal
            document.getElementById('val-tanggal').textContent =
                document.getElementById('ep-tanggal').value;

            // Nomor
            document.getElementById('val-nomor').textContent =
                document.getElementById('ep-nomor').value;

            // Kepada: newline → <br>
            var kepada = document.getElementById('ep-kepada').value
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                .replace(/\n/g,'<br>');
            document.getElementById('val-kepada').innerHTML = kepada;

            // Isi pengantar (pre-wrap, cukup textContent)
            document.getElementById('val-isi').textContent =
                document.getElementById('ep-isi').value;

            // Nama camat
            document.getElementById('val-nama').textContent =
                document.getElementById('ep-nama').value;

            // Pangkat/Golongan
            document.getElementById('val-pangkat').textContent =
                document.getElementById('ep-pangkat').value;

            // NIP
            document.getElementById('val-nip').textContent =
                document.getElementById('ep-nip').value;

            closeEdit();
        }

        /* ── Auto print on load ─────────────────────────── */
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
