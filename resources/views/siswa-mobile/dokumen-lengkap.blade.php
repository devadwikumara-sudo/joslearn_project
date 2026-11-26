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
    </style>
</head>
<body>
    <div class="ft">Buku Induk Peserta Didik &middot; Kurikulum Merdeka &middot; JosBook Digital System</div>

    @php $adaKonten = false; @endphp

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
