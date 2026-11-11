<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dokumen Buku Induk — JosBook')</title>

    {{-- Font Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Dynamic @page style for F4 / A4 --}}
    <style id="pp">
        @page {
            size: {{ ($paper ?? 'F4') === 'A4' ? '210mm 297mm' : '215mm 330mm' }};
            margin: 10mm;
        }
    </style>

    <style>
        :root {
            --p: #12355b;
            --p2: #1d4f85;
            --a: #e8a317;
            --bg: #8b97a5;
            --ln: #cbd5e1;
            --mu: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #14202e;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Toolbar Layar (No Print) */
        .pdf-toolbar-wrap {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
            border-bottom: 2px solid #e2e8f0;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
            padding: 12px 20px;
        }
        .pdf-toolbar {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .pdf-toolbar-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pdf-toolbar-top h3 {
            margin: 0;
            font-size: 16px;
            color: var(--p);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pdf-preset-bar {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pdf-filters-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 8px;
        }
        .pdf-filters-bar label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
            color: #334155;
        }
        .pdf-filters-bar select {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            background: #fff;
        }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-pdf.primary {
            background: var(--p);
            color: #fff;
        }
        .btn-pdf.primary:hover {
            background: var(--p2);
        }
        .btn-pdf.outline {
            background: #fff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-pdf.outline:hover, .btn-pdf.outline.active {
            border-color: var(--p);
            background: #f1f5f9;
            color: var(--p);
        }
        .btn-pdf.outline.active {
            background: var(--p);
            color: #fff;
        }

        /* Container Dokumen Cetak */
        .pdf-container {
            max-width: 820px;
            margin: 24px auto 48px;
            padding: 0 12px;
        }

        /* Halaman Kertas Standar (.pg) */
        .pg {
            background: #fff;
            margin: 0 auto 20px;
            min-height: 1040px;
            box-shadow: 0 4px 18px rgba(11, 34, 64, 0.25);
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
        }
        .pg:last-child {
            margin-bottom: 0;
            page-break-after: auto;
        }

        /* Gaya Dokumen Resmi Kurikulum Merdeka (.dc) Sesuai Prototype */
        .dc {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.5;
            color: #14202e;
            padding: 30px 36px 44px;
            border-top: 7px solid var(--p);
            position: relative;
        }
        .dc:not(.cv)::after {
            content: "Buku Induk Peserta Didik \00B7 Kurikulum Merdeka";
            position: absolute;
            left: 36px;
            right: 36px;
            bottom: 14px;
            border-top: 1px solid #c9d2dd;
            padding-top: 5px;
            font-size: 8.5px;
            color: #7a8696;
            letter-spacing: .8px;
            text-align: center;
        }

        .hd {
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .hl {
            flex: 1;
        }
        .ptt {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .4px;
            color: var(--p);
            padding: 8px 0;
            border-bottom: 2px solid var(--a);
            margin-bottom: 10px;
            text-align: center;
            line-height: 1.4;
        }
        .nn {
            display: flex;
            justify-content: space-around;
            margin: 8px 0;
            font-size: 11px;
        }
        .nn u {
            border-bottom: 1px dotted #333;
            text-decoration: none;
            padding: 0 26px 0 6px;
            font-weight: 700;
        }

        .ph1 {
            width: 92px;
            height: 122px;
            flex: none;
            border: 1.5px solid var(--p);
            border-radius: 3px;
            display: grid;
            place-items: center;
            text-align: center;
            font-size: 9px;
            color: #8795a6;
            background: repeating-linear-gradient(45deg, #f6f8fb 0 6px, #eef2f7 6px 12px);
            overflow: hidden;
        }
        .ph1 img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .fh {
            background: linear-gradient(90deg, var(--p), #1d4f85);
            color: #fff;
            padding: 4px 11px;
            border-radius: 3px;
            letter-spacing: .7px;
            font-size: 10px;
            font-weight: 700;
            margin: 14px 0 7px;
        }

        .fr {
            display: grid;
            grid-template-columns: 30px 190px 1fr;
            padding: 2.2px 0;
            font-size: 10.5px;
        }
        .fr.in {
            grid-template-columns: 30px 164px 1fr;
            padding-left: 30px;
        }
        .fr > span:nth-child(2) {
            color: #4a5a6c;
        }
        .fr .v {
            border-bottom: 1px dotted #8a96a5;
            font-weight: 600;
            color: #0b1b2d;
            padding-left: 2px;
        }

        .lt {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 10.5px;
        }
        .lt th {
            background: var(--p);
            color: #fff;
            border: 1px solid var(--p);
            padding: 5px;
            text-align: center;
        }
        .lt td {
            border: 1px solid #9fb0c3;
            padding: 4px 6px;
            text-align: center;
        }
        .lt td.l {
            text-align: left;
        }
        .lt tr:nth-child(even) td {
            background: #f4f7fb;
        }
        .lt .gh td {
            background: #dfe8f3 !important;
            color: var(--p);
            font-weight: 700;
            text-align: left;
        }

        .kk {
            border: 1px solid #9fb0c3;
            margin: 8px 0;
            font-size: 10.5px;
        }
        .kk div {
            padding: 4px 8px;
        }
        .kk div:first-child {
            font-weight: 700;
            background: #dfe8f3;
            border-bottom: 1px solid #9fb0c3;
        }

        .ttd {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 36px;
        }

        /* Gaya Cover Sesuai Prototype (.cv) */
        .cv {
            border: 8px double #c8a23a;
            text-align: center;
            padding: 50px 40px;
            box-sizing: border-box;
        }
        .cvt {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        .npsn {
            display: flex;
            margin-bottom: 60px;
        }
        .npsn i {
            border: 1px solid #000;
            padding: 0 6px;
            font-style: normal;
            font-weight: 700;
        }
        .cvh {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.5;
            margin: 120px 0 30px;
        }
        .cvs {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 70px;
        }
        .cva {
            border: 3px double #000;
            padding: 16px 40px;
            text-align: left;
            display: inline-block;
            font-size: 13px;
            line-height: 1.6;
        }

        /* ==================== MEDIA PRINT ==================== */
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print, .pdf-toolbar-wrap {
                display: none !important;
            }
            .pdf-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .pg {
                box-shadow: none !important;
                margin: 0 !important;
                page-break-after: always !important;
            }
            .pg:last-child {
                page-break-after: auto !important;
            }
        }
    </style>
</head>
<body>

{{-- Toolbar Atas Interaktif --}}
<div class="pdf-toolbar-wrap no-print">
    <div class="pdf-toolbar">
        <div class="pdf-toolbar-top">
            @php
                $activeCount = (!empty($optBio) ? 1 : 0) + (!empty($optNilai) ? 1 : 0) + (!empty($optHadir) ? 1 : 0) + (!empty($optEkskul) ? 1 : 0) + (!empty($optNaik) ? 1 : 0);
                $isAll = ($activeCount === 5);
                $isBio = ($activeCount === 1 && !empty($optBio));
                $isNilai = ($activeCount === 1 && !empty($optNilai));
                $isHadir = ($activeCount === 1 && !empty($optHadir));
                $isEkskul = ($activeCount === 1 && !empty($optEkskul));
            @endphp
            <h3>
                <span>📄 JosBook Output PDF</span>
                <small style="font-weight:normal;color:var(--mu);font-size:12px">· @yield('doc-subtitle', 'Kurikulum Merdeka')</small>
            </h3>

            <div class="pdf-preset-bar">
                <button type="button" class="btn-pdf outline {{ $isBio ? 'active' : '' }}" onclick="applyPreset('bio')">⬇ Biodata</button>
                <button type="button" class="btn-pdf outline {{ $isNilai ? 'active' : '' }}" onclick="applyPreset('nilai')">⬇ Nilai</button>
                <button type="button" class="btn-pdf outline {{ $isHadir ? 'active' : '' }}" onclick="applyPreset('hadir')">⬇ Kehadiran</button>
                <button type="button" class="btn-pdf outline {{ $isEkskul ? 'active' : '' }}" onclick="applyPreset('ekskul')">⬇ Ekskul</button>
                <button type="button" class="btn-pdf outline {{ $isAll ? 'active' : '' }}" onclick="applyPreset('all')">⬇ Semua</button>
            </div>

            <div style="display:flex;gap:8px;align-items:center">
                <label style="margin:0;font-size:12px;font-weight:600">Kertas:</label>
                <select id="kertas-select" onchange="changePaper(this.value)">
                    <option value="F4" {{ ($paper ?? 'F4') === 'F4' ? 'selected' : '' }}>F4 (215 &times; 330 mm)</option>
                    <option value="A4" {{ ($paper ?? 'F4') === 'A4' ? 'selected' : '' }}>A4 (210 &times; 297 mm)</option>
                </select>

                <button type="button" class="btn-pdf primary" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
                <button type="button" class="btn-pdf outline" onclick="window.history.length > 1 ? window.history.back() : window.close()">✕ Tutup</button>
            </div>
        </div>

        <form id="filter-form" method="GET" class="pdf-filters-bar">
            <label><input type="checkbox" name="bio" value="1" {{ !empty($optBio) ? 'checked' : '' }} onchange="this.form.submit()"> Biodata (register A–I)</label>
            <label><input type="checkbox" name="nilai" value="1" {{ !empty($optNilai) ? 'checked' : '' }} onchange="this.form.submit()"> Nilai intrakurikuler</label>
            <label><input type="checkbox" name="hadir" value="1" {{ !empty($optHadir) ? 'checked' : '' }} onchange="this.form.submit()"> Kokurikuler &amp; ketidakhadiran</label>
            <label><input type="checkbox" name="ekskul" value="1" {{ !empty($optEkskul) ? 'checked' : '' }} onchange="this.form.submit()"> Ekstrakurikuler</label>
            <label><input type="checkbox" name="naik" value="1" {{ !empty($optNaik) ? 'checked' : '' }} onchange="this.form.submit()"> Kenaikan kelas</label>

            <span style="flex:1"></span>

            <label>Semester:
                <select name="sem" onchange="this.form.submit()">
                    <option value="Semua" {{ ($filterSem ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua</option>
                    <option value="1" {{ ($filterSem ?? '') === '1' ? 'selected' : '' }}>Semester 1 · X Ganjil</option>
                    <option value="2" {{ ($filterSem ?? '') === '2' ? 'selected' : '' }}>Semester 2 · X Genap</option>
                    <option value="3" {{ ($filterSem ?? '') === '3' ? 'selected' : '' }}>Semester 3 · XI Ganjil</option>
                    <option value="4" {{ ($filterSem ?? '') === '4' ? 'selected' : '' }}>Semester 4 · XI Genap</option>
                    <option value="5" {{ ($filterSem ?? '') === '5' ? 'selected' : '' }}>Semester 5 · XII Ganjil</option>
                    <option value="6" {{ ($filterSem ?? '') === '6' ? 'selected' : '' }}>Semester 6 · XII Genap</option>
                    <option value="Ganjil" {{ ($filterSem ?? '') === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ ($filterSem ?? '') === 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>
            </label>

            <label>Kelas:
                <select name="kelas_filter" onchange="this.form.submit()">
                    <option value="Semua" {{ ($filterKelas ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua kelas</option>
                    @foreach($filterKelasList ?? [] as $className)
                        <option value="{{ $className }}" {{ ($filterKelas ?? '') === $className ? 'selected' : '' }}>{{ $className }}</option>
                    @endforeach
                </select>
            </label>

            <label>Tahun Ajaran:
                <select name="ta" onchange="this.form.submit()">
                    <option value="Semua" {{ ($filterTa ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua</option>
                    @foreach($taList ?? [] as $t)
                        <option value="{{ $t }}" {{ ($filterTa ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </label>

            <input type="hidden" name="paper" id="form-paper" value="{{ $paper ?? 'F4' }}">
            @if(request()->has('kelas'))
                <input type="hidden" name="kelas" value="{{ request('kelas') }}">
            @endif
            @if(request()->has('ids'))
                <input type="hidden" name="ids" value="{{ is_array(request('ids')) ? implode(',', request('ids')) : request('ids') }}">
            @endif
        </form>
    </div>
</div>

{{-- Isi Dokumen --}}
<div class="pdf-container">
    @yield('content')
</div>

<script>
function changePaper(size) {
    document.getElementById('pp').textContent = '@page { size: ' + (size === 'A4' ? '210mm 297mm' : '215mm 330mm') + '; margin: 10mm; }';
    document.getElementById('form-paper').value = size;
}

function applyPreset(v) {
    const f = document.getElementById('filter-form');
    const keys = ['bio', 'nilai', 'hadir', 'ekskul', 'naik'];

    if (v === 'all') {
        keys.forEach(k => {
            if (f[k]) f[k].checked = true;
        });
        if (f.sem) f.sem.value = 'Semua';
        if (f.ta) f.ta.value = 'Semua';
    } else {
        keys.forEach(k => {
            if (f[k]) f[k].checked = (k === v);
        });
    }
    f.submit();
}
</script>

</body>
</html>
