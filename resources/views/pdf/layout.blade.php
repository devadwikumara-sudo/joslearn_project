<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dokumen Buku Induk — JosBook')</title>

    {{-- Font Plus Jakarta Sans & Inter for Premium Crisp Typography --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Dynamic @page style for F4 / A4 --}}
    <style id="pp">
        @page {
            size: {{ ($paper ?? 'F4') === 'A4' ? '210mm 297mm' : '215mm 330mm' }};
            margin: 10mm;
        }
    </style>

    <style>
        :root {
            /* JosBook Modern Blue & Gold Luxury Palette */
            --jb-navy-950: #061938;
            --jb-navy-900: #0a2540;
            --jb-navy-800: #0f3661;
            --jb-navy-700: #154c8a;
            --jb-blue-600: #2563eb;
            --jb-blue-500: #3b82f6;
            --jb-blue-100: #dbeafe;
            --jb-blue-50:  #eff6ff;

            --jb-gold-800: #92400e;
            --jb-gold-600: #d97706;
            --jb-gold-500: #f59e0b;
            --jb-gold-400: #fbbf24;
            --jb-gold-100: #fef3c7;
            --jb-gold-50:  #fffbeb;

            --jb-slate-900: #0f172a;
            --jb-slate-800: #1e293b;
            --jb-slate-700: #334155;
            --jb-slate-600: #475569;
            --jb-slate-400: #94a3b8;
            --jb-slate-300: #cbd5e1;
            --jb-slate-200: #e2e8f0;
            --jb-slate-100: #f1f5f9;
            --jb-slate-50:  #f8fafc;

            /* Legacy variable mapping for compatibility */
            --p: var(--jb-navy-800);
            --p2: var(--jb-navy-700);
            --a: var(--jb-gold-600);
            --bg: #0b1523;
            --ln: var(--jb-slate-300);
            --mu: var(--jb-slate-400);
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            background: #0d1b2a;
            background-image: 
                radial-gradient(ellipse at 50% 0%, rgba(37, 99, 235, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 10% 100%, rgba(15, 54, 97, 0.3) 0%, transparent 50%);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--jb-slate-900);
            min-height: 100vh;
        }

        /* ==========================================================================
           TOOLBAR LAYAR (NO PRINT) - Sleek Modern Dark Glassmorphic Bar
           ========================================================================== */
        .pdf-toolbar-wrap {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10, 25, 47, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5);
            padding: 10px 24px;
        }

        .pdf-toolbar {
            max-width: 1200px;
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
            gap: 12px;
        }

        .pdf-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pdf-brand-badge {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--jb-blue-500) 0%, var(--jb-navy-800) 100%);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .pdf-brand-text h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pdf-brand-text small {
            font-weight: 500;
            color: var(--jb-slate-400);
            font-size: 11.5px;
        }

        .pdf-preset-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 13px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }

        .btn-pdf.primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-pdf.primary:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.6);
        }

        .btn-pdf.outline {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
        }

        .btn-pdf.outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .btn-pdf.outline.active {
            background: linear-gradient(135deg, #1d4ed8 0%, #0f3661 100%);
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
        }

        .pdf-actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pdf-select-dark {
            padding: 6px 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            font-size: 11.5px;
            background: rgba(15, 23, 42, 0.85);
            color: #e2e8f0;
            font-family: inherit;
            cursor: pointer;
            outline: none;
        }
        .pdf-select-dark:focus {
            border-color: var(--jb-blue-500);
        }

        .pdf-filters-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            font-size: 11.5px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 10px;
            color: #cbd5e1;
        }

        .pdf-filters-bar label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
            color: #cbd5e1;
            user-select: none;
            transition: color 0.15s ease;
        }

        .pdf-filters-bar label:hover {
            color: #ffffff;
        }

        .pdf-filters-bar input[type="checkbox"] {
            accent-color: var(--jb-blue-500);
            width: 14px;
            height: 14px;
            cursor: pointer;
        }

        .pdf-slot-picker {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .pdf-slot-list {
            display: flex;
            align-items: center;
            gap: 8px 14px;
            flex-wrap: wrap;
        }

        .pdf-slot-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ==========================================================================
           CONTAINER DOKUMEN CETAK (CANVAS)
           ========================================================================== */
        .pdf-container {
            max-width: 860px;
            margin: 28px auto 60px;
            padding: 0 16px;
        }

        /* ==========================================================================
           HALAMAN KERTAS (.pg) - Prestigious Document Sheet
           ========================================================================== */
        .pg {
            background: #ffffff;
            margin: 0 auto 32px;
            min-height: 1060px;
            border-radius: 4px;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.08),
                0 14px 35px -8px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }

        .pg:last-child {
            margin-bottom: 0;
            page-break-after: auto;
        }

        /* Gaya Dokumen Resmi Kurikulum Merdeka (.dc) */
        .dc {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: var(--jb-slate-900);
            padding: 28px 36px 42px;
            border-top: 6px solid var(--jb-navy-800);
            position: relative;
            background: #ffffff;
        }

        /* Multi-tone Top Accent Line */
        .dc::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, 
                var(--jb-navy-900) 0%, 
                var(--jb-navy-800) 65%, 
                var(--jb-gold-500) 65%, 
                var(--jb-gold-500) 100%
            );
        }

        /* Elegant Running Footer */
        .dc:not(.cv)::after {
            content: "Buku Induk Peserta Didik \00B7 Kurikulum Merdeka \00B7 JosBook Digital System";
            position: absolute;
            left: 36px;
            right: 36px;
            bottom: 12px;
            border-top: 1px solid var(--jb-slate-200);
            padding-top: 5px;
            font-size: 8px;
            font-weight: 500;
            color: var(--jb-slate-400);
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-align: center;
        }

        /* ==========================================================================
           HEADER DOKUMEN & IDENTITAS (.hd, .ptt, .nn, .ph1)
           ========================================================================== */
        .hd {
            display: flex;
            gap: 18px;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid var(--jb-slate-200);
        }

        .hl {
            flex: 1;
        }

        .ptt {
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: var(--jb-navy-900);
            line-height: 1.45;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .ptt-sub {
            font-size: 11px;
            font-weight: 700;
            color: var(--jb-navy-700);
            letter-spacing: 0.8px;
        }

        .nn {
            display: flex;
            gap: 16px;
            margin-top: 8px;
            font-size: 10.5px;
        }

        .nn-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--jb-slate-50);
            border: 1px solid var(--jb-slate-300);
            border-left: 3.5px solid var(--jb-navy-800);
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
            color: var(--jb-slate-700);
        }

        .nn-pill b {
            color: var(--jb-navy-900);
            font-weight: 700;
        }

        .nn u {
            text-decoration: none;
            border-bottom: 1.5px solid var(--jb-navy-800);
            padding: 0 4px;
            font-weight: 700;
            color: var(--jb-navy-900);
        }

        /* Pas Foto Frame */
        .ph1 {
            width: 90px;
            height: 120px;
            flex: none;
            border: 2px solid var(--jb-navy-800);
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 9px;
            font-weight: 600;
            color: var(--jb-slate-500);
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            position: relative;
        }

        .ph1 img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ph1-placeholder-icon {
            font-size: 22px;
            margin-bottom: 4px;
            opacity: 0.6;
        }

        /* ==========================================================================
           SECTION HEADERS & FORM FIELDS (.fh, .fr, .v)
           ========================================================================== */
        .fh {
            background: linear-gradient(90deg, var(--jb-navy-900) 0%, var(--jb-navy-800) 70%, var(--jb-navy-700) 100%);
            color: #ffffff;
            padding: 4.5px 12px;
            border-radius: 4px;
            letter-spacing: 0.6px;
            font-size: 10px;
            font-weight: 700;
            margin: 12px 0 6px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
            border-left: 3.5px solid var(--jb-gold-500);
        }

        .fr {
            display: grid;
            grid-template-columns: 24px 190px 1fr;
            padding: 2.2px 0;
            font-size: 10px;
            align-items: baseline;
        }

        .fr.in {
            grid-template-columns: 24px 166px 1fr;
            padding-left: 24px;
        }

        .fr > span:first-child {
            color: var(--jb-slate-600);
            font-weight: 600;
        }

        .fr > span:nth-child(2) {
            color: var(--jb-slate-700);
            font-weight: 500;
        }

        .fr .v {
            border-bottom: 1px solid var(--jb-slate-200);
            font-weight: 600;
            color: var(--jb-navy-900);
            padding: 1px 0 1px 4px;
            min-height: 16px;
        }

        /* ==========================================================================
           TABLES (.lt) - Modern Academic Data Ledger
           ========================================================================== */
        .lt {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 8px 0;
            font-size: 9.8px;
            border: 1px solid var(--jb-slate-300);
            border-radius: 4px;
            overflow: hidden;
        }

        .lt th {
            background: linear-gradient(180deg, var(--jb-navy-800) 0%, var(--jb-navy-900) 100%);
            color: #ffffff;
            font-weight: 700;
            border: 1px solid var(--jb-navy-700);
            padding: 5px 6px;
            text-align: center;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        .lt td {
            border-right: 1px solid var(--jb-slate-200);
            border-bottom: 1px solid var(--jb-slate-200);
            padding: 3.5px 6px;
            text-align: center;
            color: var(--jb-slate-800);
        }

        .lt td:last-child {
            border-right: none;
        }

        .lt tr:last-child td {
            border-bottom: none;
        }

        .lt td.l {
            text-align: left;
            font-weight: 500;
        }

        .lt tr:nth-child(even) td {
            background: var(--jb-slate-50);
        }

        .lt .gh td {
            background: linear-gradient(90deg, #edf5ff 0%, #f5f9ff 100%) !important;
            color: var(--jb-navy-900);
            font-weight: 700;
            text-align: left;
            padding: 4.5px 8px;
            border-bottom: 1px solid var(--jb-blue-100);
            border-top: 1px solid var(--jb-blue-100);
            letter-spacing: 0.3px;
        }

        .lt tr.summary-row td {
            background: #f1f5f9 !important;
            font-weight: 700;
            color: var(--jb-navy-900);
            border-top: 1.5px solid var(--jb-slate-300);
        }

        /* Kokurikuler Box (.kk) */
        .kk {
            border: 1px solid var(--jb-slate-300);
            border-radius: 5px;
            margin: 8px 0;
            font-size: 10px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .kk div {
            padding: 5px 10px;
        }

        .kk div:first-child {
            font-weight: 700;
            background: linear-gradient(90deg, #eef5fc 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--jb-slate-200);
            color: var(--jb-navy-900);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kk div:last-child {
            color: var(--jb-slate-700);
            line-height: 1.45;
        }

        /* Signatures (.ttd) */
        .ttd {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            text-align: center;
            margin-top: 32px;
            padding: 0 20px;
            font-size: 10.5px;
        }

        .ttd > div {
            min-width: 190px;
        }

        .ttd-title {
            color: var(--jb-slate-700);
            margin-bottom: 50px;
            font-weight: 500;
        }

        .ttd-name {
            font-weight: 700;
            color: var(--jb-navy-900);
            text-decoration: underline;
            letter-spacing: 0.3px;
        }

        .ttd-nip {
            font-size: 9.5px;
            color: var(--jb-slate-600);
            margin-top: 2px;
        }

        /* ==========================================================================
           SAMPUL KELAS / COVER (.cv) - Luxury Architectural Front Page
           ========================================================================== */
        .cv {
            text-align: center;
            padding: 44px 36px;
            box-sizing: border-box;
            background: #ffffff;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 1040px;
        }

        /* Double Ornamental Border Frame */
        .cv-frame {
            position: absolute;
            top: 14px;
            left: 14px;
            right: 14px;
            bottom: 14px;
            border: 2px solid var(--jb-navy-800);
            pointer-events: none;
            box-sizing: border-box;
        }

        .cv-frame-inner {
            position: absolute;
            top: 19px;
            left: 19px;
            right: 19px;
            bottom: 19px;
            border: 1px solid var(--jb-gold-500);
            pointer-events: none;
            box-sizing: border-box;
        }

        /* Geometric Corner Ornaments */
        .cv-corner {
            position: absolute;
            width: 14px;
            height: 14px;
            border-color: var(--jb-navy-800);
            border-style: solid;
        }
        .cv-corner.tl { top: 10px; left: 10px; border-width: 3px 0 0 3px; }
        .cv-corner.tr { top: 10px; right: 10px; border-width: 3px 3px 0 0; }
        .cv-corner.bl { bottom: 10px; left: 10px; border-width: 0 0 3px 3px; }
        .cv-corner.br { bottom: 10px; right: 10px; border-width: 0 3px 3px 0; }

        .cvt {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--jb-navy-900);
            padding: 0 8px;
            letter-spacing: 0.8px;
            margin-top: 6px;
        }

        .cvt-tag {
            background: var(--jb-navy-800);
            color: #ffffff;
            padding: 3px 12px;
            border-radius: 4px;
            font-size: 10.5px;
            letter-spacing: 0.5px;
        }

        .npsn-wrap {
            margin: 10px 0 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0 8px;
        }

        .npsn-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--jb-slate-600);
            letter-spacing: 0.8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .npsn {
            display: flex;
            gap: 2px;
        }

        .npsn i {
            width: 22px;
            height: 26px;
            border: 1.5px solid var(--jb-navy-800);
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-style: normal;
            font-weight: 800;
            font-size: 13px;
            color: var(--jb-navy-900);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .cv-crest {
            margin: 20px auto 14px;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            border: 2px solid var(--jb-gold-500);
            background: linear-gradient(135deg, var(--jb-navy-900) 0%, var(--jb-navy-700) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(15, 54, 97, 0.25);
            color: var(--jb-gold-400);
            font-size: 38px;
        }

        .cv-garuda {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }

        .cvh {
            font-size: 21px;
            font-weight: 800;
            line-height: 1.45;
            color: var(--jb-navy-900);
            margin: 18px 0 16px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .cvh-sub {
            display: inline-block;
            background: linear-gradient(90deg, var(--jb-gold-600), var(--jb-gold-500));
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 4px 16px;
            border-radius: 20px;
            margin-top: 10px;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
        }

        .cvs {
            font-size: 20px;
            font-weight: 800;
            color: var(--jb-navy-800);
            letter-spacing: 0.6px;
            margin: 20px 0 32px;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
        }

        .cvs::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--jb-gold-500) 50%, transparent 100%);
        }

        .cva {
            border: 1.5px solid var(--jb-navy-800);
            border-left: 5px solid var(--jb-navy-800);
            border-radius: 6px;
            padding: 16px 28px;
            text-align: left;
            display: inline-block;
            font-size: 11.5px;
            line-height: 1.7;
            background: var(--jb-slate-50);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            color: var(--jb-slate-800);
            margin: 0 auto 16px;
            max-width: 520px;
            box-sizing: border-box;
        }

        .cva-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cva-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .cva-table td:first-child {
            font-weight: 600;
            color: var(--jb-slate-600);
            width: 110px;
        }

        .cva-table td:nth-child(2) {
            width: 10px;
            font-weight: 600;
            color: var(--jb-slate-400);
        }

        .cva-table td:last-child {
            font-weight: 700;
            color: var(--jb-navy-900);
        }

        /* ==========================================================================
           MEDIA PRINT STYLES
           ========================================================================== */
        @media print {
            body {
                background: #ffffff !important;
                background-image: none !important;
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
                border-radius: 0 !important;
                page-break-after: always !important;
                border: none !important;
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
            <div class="pdf-brand">
                <div class="pdf-brand-badge">JB</div>
                <div class="pdf-brand-text">
                    <h3>
                        <span>Buku Induk Peserta Didik</span>
                    </h3>
                    <small>JosBook Digital · @yield('doc-subtitle', 'Kurikulum Merdeka')</small>
                </div>
            </div>

            <div class="pdf-preset-bar">
                <button type="button" class="btn-pdf outline {{ $isBio ? 'active' : '' }}" onclick="applyPreset('bio')">Biodata</button>
                <button type="button" class="btn-pdf outline {{ $isNilai ? 'active' : '' }}" onclick="applyPreset('nilai')">Nilai</button>
                <button type="button" class="btn-pdf outline {{ $isHadir ? 'active' : '' }}" onclick="applyPreset('hadir')">Kehadiran</button>
                <button type="button" class="btn-pdf outline {{ $isEkskul ? 'active' : '' }}" onclick="applyPreset('ekskul')">Ekskul</button>
                <button type="button" class="btn-pdf outline {{ $isAll ? 'active' : '' }}" onclick="applyPreset('all')">Semua Halaman</button>
            </div>

            <div class="pdf-actions-right">
                <select id="kertas-select" class="pdf-select-dark" onchange="changePaper(this.value)">
                    <option value="F4" {{ ($paper ?? 'F4') === 'F4' ? 'selected' : '' }}>Ukuran: F4 (215 &times; 330 mm)</option>
                    <option value="A4" {{ ($paper ?? 'F4') === 'A4' ? 'selected' : '' }}>Ukuran: A4 (210 &times; 297 mm)</option>
                </select>

                <button type="button" class="btn-pdf primary" onclick="window.print()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v8H6z"></path></svg>
                    Cetak / Simpan PDF
                </button>
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

            <div class="pdf-slot-picker">
                <span style="font-weight:700;color:#fff;">Slot akademik:</span>
                <div class="pdf-slot-list">
                    @foreach(['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'] as $slot)
                        <label class="pdf-slot-pill"><input type="checkbox" name="slots[]" value="{{ $slot }}" {{ in_array($slot, $selectedSlots ?? ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'], true) ? 'checked' : '' }} onchange="this.form.submit()"> {{ $slot }}</label>
                    @endforeach
                </div>
                <button type="button" class="btn-pdf outline" style="padding:3px 8px;font-size:10.5px" onclick="setSlots(true)">Pilih semua</button>
                <button type="button" class="btn-pdf outline" style="padding:3px 8px;font-size:10.5px" onclick="setSlots(false)">Kosongkan</button>
                <label style="margin-left:8px"><input type="checkbox" name="hide_empty" value="1" {{ !empty($hideEmptySlots) ? 'checked' : '' }} onchange="this.form.submit()"> Sembunyikan slot kosong</label>
            </div>

            <label>Semester:
                <select name="sem" class="pdf-select-dark" style="padding:3px 8px;" onchange="submitLegacyFilters()">
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
                <select name="kelas_filter" class="pdf-select-dark" style="padding:3px 8px;" onchange="this.form.submit()">
                    <option value="Semua" {{ ($filterKelas ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua kelas</option>
                    @foreach($filterKelasList ?? [] as $className)
                        <option value="{{ $className }}" {{ ($filterKelas ?? '') === $className ? 'selected' : '' }}>{{ $className }}</option>
                    @endforeach
                </select>
            </label>

            <label>Tahun Ajaran:
                <select name="ta" class="pdf-select-dark" style="padding:3px 8px;" onchange="this.form.submit()">
                    <option value="Semua" {{ ($filterTa ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua</option>
                    @foreach($taList ?? [] as $t)
                        <option value="{{ $t }}" {{ ($filterTa ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </label>

            <input type="hidden" name="paper" id="form-paper" value="{{ $paper ?? 'F4' }}">
            <input type="hidden" name="filter_applied" value="1">
            <input type="hidden" name="slots_present" value="1">
            @if(request()->has('kelas'))
                <input type="hidden" name="kelas" value="{{ request('kelas') }}">
            @endif
            @if(request()->has('ids'))
                <input type="hidden" name="ids" value="{{ is_array(request('ids')) ? implode(',', request('ids')) : request('ids') }}">
            @endif
            @foreach(['target_kelas', 'target_ta', 'q', 'st', 'tahun_ajaran'] as $preservedFilter)
                @if(request()->has($preservedFilter))
                    <input type="hidden" name="{{ $preservedFilter }}" value="{{ request($preservedFilter) }}">
                @endif
            @endforeach
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
        setSlots(true, false);
    } else {
        keys.forEach(k => {
            if (f[k]) f[k].checked = (k === v);
        });
    }
    f.submit();
}

function setSlots(selected, submit = true) {
    document.querySelectorAll('#filter-form input[name="slots[]"]').forEach((slot) => {
        slot.checked = selected;
    });
    if (submit) document.getElementById('filter-form').submit();
}

function submitLegacyFilters() {
    const form = document.getElementById('filter-form');
    form.querySelector('input[name="slots_present"]').disabled = true;
    form.querySelectorAll('input[name="slots[]"]').forEach((slot) => {
        slot.disabled = true;
    });
    form.submit();
}
</script>

</body>
</html>
