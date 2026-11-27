{{--
    Dokumen PDF siswa untuk aplikasi mobile. Isi dan urutannya sama dengan halaman cetak web
    (pdf.dokumen): Buku Induk register A-I, lalu laporan capaian pembelajaran per periode.
    Partial pdf.register dan pdf.capaian dipakai bersama; yang berbeda hanya stylesheet ini,
    yang ditulis ulang agar DomPDF dapat merender (tanpa var(), flex, grid, gradien, bayangan).
    Warna mengikuti palet layout web (pdf.layout).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Induk Peserta Didik — JosBook</title>
    <style>
        @page { margin: 10mm; }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #0f172a;
        }

        /* Footer berulang di setiap halaman (di area margin bawah) */
        .ft {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -26px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Halaman */
        .pg { page-break-after: always; }
        .pg:last-child { page-break-after: auto; }

        .dc {
            padding: 14px 22px 6px;
            border-top: 5px solid #0f3661;
            font-size: 9px;
            line-height: 1.35;
            color: #0f172a;
        }

        /* Header dokumen: judul di kiri, pas foto di kanan */
        .hd { display: table; width: 100%; margin-bottom: 10px; }
        .hl { display: table-cell; vertical-align: top; padding-right: 14px; }
        .ptt {
            font-size: 12px;
            font-weight: bold;
            color: #0a2540;
            line-height: 1.4;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .ptt-sub { font-size: 9.5px; font-weight: bold; color: #154c8a; }
        .nn { margin-top: 6px; font-size: 9px; }
        .nn-pill {
            display: inline-block;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #0f3661;
            padding: 2px 8px;
            font-weight: bold;
            color: #334155;
            margin-right: 10px;
        }
        .nn u {
            text-decoration: none;
            border-bottom: 1px solid #0f3661;
            padding: 0 4px;
            color: #0a2540;
        }
        .ph1 {
            display: table-cell;
            width: 90px;
            height: 120px;
            border: 2px solid #0f3661;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
            font-weight: bold;
            color: #475569;
            background: #f1f5f9;
        }
        .ph1 img { width: 86px; height: 116px; }
        .ph1-placeholder-icon { display: none; }

        /* Judul bagian dan baris isian */
        .fh {
            background: #0f3661;
            color: #ffffff;
            padding: 4px 10px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 10px 0 5px;
            text-transform: uppercase;
            border-left: 3px solid #f59e0b;
        }
        .fr { display: table; width: 100%; padding: 1px 0; font-size: 9px; }
        .fr > span { display: table-cell; vertical-align: top; width: 190px; color: #334155; }
        .fr > span:first-child { width: 24px; color: #475569; font-weight: bold; }
        .fr > span:last-child { width: auto; }
        .fr .v {
            border-bottom: 1px solid #e2e8f0;
            font-weight: bold;
            color: #0a2540;
            padding: 1px 0 1px 4px;
        }
        .fr.in { padding-left: 24px; }
        .fr.in > span:nth-child(2) { width: 166px; }

        /* Tabel nilai, ekskul, ketidakhadiran */
        .lt {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8.8px;
            border: 1px solid #cbd5e1;
        }
        .lt th {
            background: #0f3661;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #154c8a;
            padding: 4px 5px;
            text-align: center;
            font-size: 8.8px;
        }
        .lt td {
            border: 1px solid #e2e8f0;
            padding: 3px 5px;
            text-align: center;
            color: #1e293b;
        }
        .lt td.l { text-align: left; }
        .lt tr:nth-child(even) td { background: #f8fafc; }
        .lt .gh td {
            background: #edf5ff !important;
            color: #0a2540;
            font-weight: bold;
            text-align: left;
            padding: 4px 7px;
        }
        .lt tr.summary-row td {
            background: #f1f5f9 !important;
            font-weight: bold;
            color: #0a2540;
        }

        /* Kotak kokurikuler */
        .kk { border: 1px solid #cbd5e1; margin: 6px 0; font-size: 9px; }
        .kk div { padding: 4px 8px; }
        .kk div:first-child {
            font-weight: bold;
            background: #eef5fc;
            border-bottom: 1px solid #e2e8f0;
            color: #0a2540;
        }
        .kk div:last-child { color: #334155; line-height: 1.4; }

        /* Tanda tangan: wali kelas di kiri, kepala sekolah di kanan (kelas XII) */
        .ttd { margin-top: 28px; padding: 0 16px; height: 110px; font-size: 9.5px; text-align: center; }
        .ttd > div { float: left; width: 200px; }
        .ttd > div + div { float: right; }
        .ttd-title { color: #334155; margin-bottom: 44px; }
        .ttd-name { font-weight: bold; color: #0a2540; text-decoration: underline; }
        .ttd-nip { font-size: 8.5px; color: #475569; margin-top: 2px; }

        /* Format resmi identik dengan referensi sekolah (ukuran dalam pt sesuai hasil ukur PDF referensi). */
        .cv {
            height: 858pt;
            padding: 0;
            text-align: center;
            position: relative;
            font-family: 'DejaVu Sans', sans-serif;
            border-top: 0;
        }
        .cv-ornament-frame { position:absolute; top:0; left:0; width:100%; height:100%; z-index:1; }
        .cv-inner { position:relative; z-index:2; padding:58pt 64pt 54pt; }
        .cv-top,.cv-center,.cv-bottom { position:relative; z-index:2; }
        .cv-top { margin-top: 0; }
        .cv-center { margin: 48pt 0 54pt; }
        .cv-bottom { margin-top: 0; }
        .cvt { display:table; width:100%; font-size:10pt; color:#111111; }
        .cvt-left { display:table-cell; text-align:left; vertical-align:top; }
        .cvt-right { display:table-cell; text-align:right; vertical-align:top; }
        .npsn { margin-top:4pt; }
        .npsn i { display:inline-block; width:15.5pt; height:17pt; line-height:14pt; border:1pt solid #111111; margin-right:-1pt; font-style:normal; font-weight:normal; text-align:center; font-size:10pt; background:#ffffff; padding-top:1pt; }
        .cv-crest { width:118pt; height:124pt; margin:6pt auto 16pt; }
        .cv-crest img { width:116pt; height:auto; }
        .cvh { font-size:16.5pt; line-height:1.38; font-weight:bold; margin:6pt 0 18pt; color:#111111; }
        .cvs { font-size:16.5pt; font-weight:bold; color:#111111; }
        .cva-outer { width:348pt; margin:0 auto; border:1pt solid #111111; padding:2.5pt; background:#ffffff; }
        .cva { border:1pt solid #111111; padding:12pt 20pt; text-align:left; font-size:12pt; line-height:1.45; color:#111111; }
        .cva-corner { display:none; }
        .cva-table { width:100%; border-collapse:collapse; }
        .cva-table td { padding:1.5pt 3pt; color:#111111; font-weight:normal; }
        .cva-table td:first-child { width:78pt; }
        .cva-table td:nth-child(2) { width:10pt; text-align:center; }

        .reg { font-family:'DejaVu Sans',sans-serif; color:#111111; border-top:0; font-size:9.3pt; line-height:1.3; padding:18pt 32pt 20pt 44pt; position:relative; }
        .reg-title { text-align:center; font-size:11.8pt; font-weight:bold; line-height:1.35; margin-bottom:12pt; }
        .reg-number { font-size:9.3pt; padding:0 0 6pt 96pt; line-height:1.3; }
        .reg-number > span { display:inline-block; width:49%; font-weight:normal; }
        .reg-num-val { display:inline-block; min-width:85pt; border-bottom:0.75pt solid #333333; padding:0 3pt; }
        .reg-p1-body { padding-right:86pt; }
        .reg-fh { background:#ffffff; color:#111111; border:0; border-left:0; padding:6pt 0 2pt; margin:7pt 0 2pt; font-size:9.3pt; font-weight:bold; }
        .reg .fr { display:table; width:100%; font-size:9.3pt; padding:1.4pt 0; }
        .reg .fr > span { display:table-cell; vertical-align:bottom; }
        .reg .fr > span:first-child { width:18pt; font-weight:normal; color:#111111; }
        .reg .fr > span:nth-child(2) { width:146pt; color:#111111; }
        .reg .fr.in { padding-left:0; }
        .reg .fr.in > span:nth-child(2) { width:146pt; }
        .reg .fr .c { width:8pt; color:#111111; }
        .reg .fr .v { width:auto; border-bottom:0.75pt solid #333333; color:#111111; font-weight:normal; padding-left:3pt; }
        .reg-photo-stack { position:absolute; right:32pt; top:96pt; width:72pt; }
        .reg-photo { width:70pt; height:88pt; border:0.8pt solid #111111; margin-bottom:10pt; padding:0; background:#ffffff; }
        .reg-photo-inner { width:100%; height:100%; border:0; position:relative; text-align:center; }
        .reg-photo-size { position:absolute; top:34pt; left:0; right:0; font-size:7.5pt; color:#444444; }
        .reg-photo-label { position:absolute; bottom:4pt; left:0; right:0; font-size:7.5pt; color:#111111; }
        .reg-photo img { width:70pt; height:88pt; object-fit:cover; }
        .reg-catatan-lines { margin-top:6pt; }
        .reg-catatan-line { border-bottom:0.75pt solid #111111; height:15pt; line-height:15pt; font-size:9.3pt; }

        .capaian { font-family:'DejaVu Sans',sans-serif; color:#111111; border-top:0; padding:18pt 32pt 20pt 44pt; }
        .capaian-title { text-align:left; padding-left:0; font-size:12.2pt; font-weight:bold; line-height:1.38; margin-bottom:12pt; }
        .capaian-title span { display:block; margin-top:2pt; padding-left:18pt; font-size:12.2pt; }
        .capaian-fh { background:#ffffff; color:#111111; border:0; padding:5pt 0 3pt; margin:9pt 0 4pt; font-size:9.8pt; font-weight:bold; }
        .capaian .fr { display:table; width:100%; font-size:9.8pt; padding:1.2pt 0; }
        .capaian .fr > span { display:table-cell; }
        .capaian .fr > span:first-child { width:20pt; }
        .capaian .fr > span:nth-child(2) { width:112pt; }
        .capaian .fr .c { width:8pt; }
        .capaian .fr .v { width:auto; border:0; font-weight:normal; color:#111111; }
        .capaian .lt { border:0.75pt solid #8db4e2; border-collapse:collapse; border-radius:0; font-size:9pt; margin:4pt 0; width:100%; }
        .capaian .lt.lt-blue-head { width:100%; }
        .capaian .lt th, .capaian .lt td { border:0.75pt solid #8db4e2; color:#111111; padding:2.5pt 4pt; text-align:center; }
        .capaian .lt td.l { text-align:left; }
        .capaian .lt.lt-intra thead th { background:#ffffff; color:#111111; font-weight:bold; }
        .capaian .lt.lt-blue-head thead th, .capaian .lt.kk-table thead th { background:#dce6f1; color:#111111; font-weight:bold; }
        .capaian .lt tr:nth-child(even) td { background:#ffffff; }
        .capaian .lt tr.alt td { background:#eaf2fb !important; }
        .capaian .lt .gh td, .capaian .lt tr.summary-row td { background:#dce6f1 !important; font-weight:bold; }
        .capaian .lt .gh td { text-align:left; padding:3pt 5pt; }
        .capaian-ket { font-size:8.5pt; color:#111111; margin-top:4pt; line-height:1.35; }
        .capaian-ket span { display:inline-block; padding-left:14pt; }
        .capaian .kk-table { margin:4pt 0 10pt; }
        .capaian .kk-table td.kk-no { vertical-align:top; text-align:center; padding-top:4pt; }
        .capaian .kk-table td.kk-desc { vertical-align:top; text-align:left; height:64pt; padding:4pt 6pt; line-height:1.35; }
        .kenaikan-keputusan { padding-left:20pt; font-size:9.6pt; margin-bottom:4pt; }
        .kenaikan-table { margin-left:20pt; border-collapse:collapse; font-size:9.6pt; }
        .kenaikan-table td.kl { width:225pt; text-align:right; padding:1.5pt 8pt 1.5pt 0; color:#111111; }
        .kenaikan-table td.kv { text-align:left; padding:1.5pt 0; color:#111111; }
        .capaian .ttd { margin-top:26pt; padding:0 12pt; font-size:9.6pt; }
        .capaian .ttd-left { float:left; width:175pt; text-align:center; }
        .capaian .ttd-right { float:right; width:175pt; text-align:center; }
        .capaian .ttd-title { color:#111111; margin-bottom:44pt; line-height:1.4; }
        .capaian .ttd-name { font-weight:bold; color:#111111; text-decoration:none; }
        .capaian .ttd-nip { font-size:9pt; color:#111111; margin-top:2pt; }

        /* Responsif ukuran kertas A4 (210 x 297 mm -> area cetak 538 x 785 pt) */
        body.paper-a4 .cv { height: 772pt; }
        body.paper-a4 .cv-inner { padding: 42pt 56pt 38pt; }
        body.paper-a4 .cv-center { margin: 28pt 0 32pt; }
        body.paper-a4 .cv-crest { width: 104pt; height: 108pt; margin: 4pt auto 12pt; }
        body.paper-a4 .cv-crest img { width: 102pt; }
        body.paper-a4 .cvh { font-size: 15.5pt; margin: 4pt 0 14pt; }
        body.paper-a4 .cvs { font-size: 15.5pt; }
        body.paper-a4 .cva { font-size: 11pt; padding: 10pt 18pt; }

        body.paper-a4 .reg { padding: 10pt 28pt 12pt 36pt; font-size: 8.6pt; line-height: 1.22; }
        body.paper-a4 .reg-title { font-size: 11pt; margin-bottom: 8pt; }
        body.paper-a4 .reg-number { font-size: 8.6pt; padding: 0 0 4pt 88pt; }
        body.paper-a4 .reg-fh { margin: 5pt 0 2pt; padding: 3pt 0 1pt; font-size: 8.6pt; }
        body.paper-a4 .reg .fr { font-size: 8.6pt; padding: 0.75pt 0; }
        body.paper-a4 .reg-p1-body { padding-right: 78pt; }
        body.paper-a4 .reg-photo-stack { right: 28pt; top: 82pt; width: 68pt; }
        body.paper-a4 .reg-photo { width: 66pt; height: 82pt; margin-bottom: 7pt; }
        body.paper-a4 .reg-photo img { width: 66pt; height: 82pt; }
        body.paper-a4 .reg-catatan-line { height: 13pt; line-height: 13pt; font-size: 8.6pt; }

        body.paper-a4 .capaian { padding: 10pt 28pt 12pt 36pt; }
        body.paper-a4 .capaian-title { font-size: 11.2pt; margin-bottom: 8pt; }
        body.paper-a4 .capaian-title span { font-size: 11.2pt; }
        body.paper-a4 .capaian-fh { font-size: 9pt; margin: 6pt 0 3pt; padding: 2pt 0; }
        body.paper-a4 .capaian .fr { font-size: 9pt; padding: 0.8pt 0; }
        body.paper-a4 .capaian .lt { font-size: 8pt; margin: 3pt 0; }
        body.paper-a4 .capaian .lt th,
        body.paper-a4 .capaian .lt td { padding: 1.8pt 3.5pt; }
        body.paper-a4 .capaian .kk-table { margin: 3pt 0 6pt; }
        body.paper-a4 .capaian .kk-table td.kk-desc { height: 40pt; padding: 3pt 5pt; line-height: 1.25; }
        body.paper-a4 .kenaikan-keputusan,
        body.paper-a4 .kenaikan-table,
        body.paper-a4 .capaian .ttd { font-size: 9pt; }
        body.paper-a4 .capaian .ttd { margin-top: 16pt; }
        body.paper-a4 .capaian .ttd-title { margin-bottom: 34pt; }
    </style>
</head>
<body class="paper-{{ strtolower($paper ?? 'F4') }}">

    @php $adaKonten = false; @endphp

    @php
        $kelasSampul = 'X-1';
        $angkatanSampul = null;
        foreach ($siswaItems as $itemSampul) {
            $siswaSampul = $itemSampul['siswa'];
            if (! $angkatanSampul && $siswaSampul->tanggal_diterima
                && preg_match('/^(\\d{4})/', (string) $siswaSampul->tanggal_diterima, $match)) {
                $angkatanSampul = (int) $match[1];
            }
            foreach ($itemSampul['riwayat'] as $riwayatSampul) {
                $kelasSampul = $riwayatSampul['riwayatKelas']->kelas?->nama ?? $kelasSampul;
                $taSampul = (string) ($riwayatSampul['taNama'] ?? '');
                if (! $angkatanSampul && preg_match('/^(\\d{4})/', $taSampul, $match)) {
                    $angkatanSampul = (int) $match[1];
                }
            }
        }
        $angkatanSampul = $angkatanSampul ?: 2023;
    @endphp
    @include('pdf.cover', [
        'sekolah' => $sekolah,
        'kelasNama' => $kelasSampul,
        'angkatan' => (string) $angkatanSampul,
    ])
    @php $adaKonten = true; @endphp

    @forelse($siswaItems as $item)
        @php
            $s = $item['siswa'];
            $riwayatList = $item['riwayat'];
        @endphp

        {{-- Register Peserta Didik (A-I) --}}
        @if(!empty($optBio))
            @include('pdf.register', [
                'siswa'   => $s,
                'sekolah' => $sekolah,
            ])
            @php $adaKonten = true; @endphp
        @endif

        {{-- Laporan Capaian Pembelajaran (nilai, kokurikuler, ekskul, kehadiran, kenaikan) --}}
        @if(!empty($optNilai) || !empty($optHadir) || !empty($optEkskul) || !empty($optNaik))
            @foreach($riwayatList as $rk)
                @include('pdf.capaian', [
                    'siswa'           => $s,
                    'sekolah'         => $sekolah,
                    'riwayatKelas'    => $rk['riwayatKelas'],
                    'kelasTujuanNama' => $rk['kelasTujuanNama'] ?? null,
                    'taNama'          => $rk['taNama'],
                    'kktp'            => $kktp ?? 75,
                    'filterSem'       => $reportFilterSem ?? 'Semua',
                    'nilaiMap'        => $rk['nilaiMap'] ?? [],
                    'kokurMap'        => $rk['kokurMap'] ?? [],
                    'ekskulList'      => $rk['ekskulList'] ?? [],
                    'hadirMap'        => $rk['hadirMap'] ?? [],
                    'waliKelasNama'   => $rk['waliKelasNama'] ?? null,
                    'waliKelasNip'    => $rk['waliKelasNip'] ?? null,
                    'nextKelasMap'    => $nextKelasMap ?? [],
                    'tingkatMapel'    => $tingkatMapel ?? null,
                    'optNilai'        => $optNilai,
                    'optHadir'        => $optHadir,
                    'optEkskul'       => $optEkskul,
                    'optNaik'         => $optNaik,
                    'slotSemesters'   => $rk['slotSemesters'] ?? null,
                    'emptySemesters'  => $rk['emptySemesters'] ?? [],
                ])
                @php $adaKonten = true; @endphp
            @endforeach
        @endif
    @empty
        <div class="pg dc">
            <h3 style="text-align:center;padding:60px 20px 0">Tidak ada data siswa ditemukan</h3>
        </div>
    @endforelse

    @if(!$adaKonten && count($siswaItems) > 0)
        <div class="pg dc">
            <h3 style="text-align:center;padding:60px 20px 0">Tidak ada data pada periode yang dipilih</h3>
        </div>
    @endif
</body>
</html>
