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
            max-width: 215mm;
            margin: 28px auto 60px;
            padding: 0;
        }
        body.paper-a4 .pdf-container {
            max-width: 210mm;
        }

        /* ==========================================================================
           HALAMAN KERTAS (.pg) - Prestigious Document Sheet
           ========================================================================== */
        .pg {
            background: #ffffff;
            margin: 0 auto 32px;
            min-height: 330mm;
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
        body.paper-a4 .pg {
            min-height: 297mm;
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

        /* Footer hanya untuk preview layar bila perlu; cetak resmi tidak memakai footer */
        .dc:not(.cv)::after {
            display: none;
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
           SAMPUL KELAS / COVER (.cv) - Format Resmi Sekolah
           ========================================================================== */
        .cv {
            text-align: center;
            padding: 0;
            box-sizing: border-box;
            background: #ffffff;
            position: relative;
            min-height: 850pt;
            font-family: 'Century Gothic', Arial, sans-serif;
            border-top: 0;
        }
        .cv-inner {
            position: relative;
            z-index: 2;
            padding: 58pt 64pt 54pt;
            min-height: 850pt;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .cv::before,
        .cv::after {
            display: none;
        }
        .cv-ornament-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        .cv-top,
        .cv-center,
        .cv-bottom {
            position: relative;
            z-index: 2;
        }
        .cv-top { margin-top: 0; }
        .cv-center { margin: auto 0; }
        .cv-bottom { padding-bottom: 12pt; }
        .cvt {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 10.2pt;
            color: #111111;
            letter-spacing: 0;
        }
        .cvt-left { text-align: left; }
        .cvt-right { text-align: right; }
        .npsn {
            display: flex;
            gap: 0;
            margin-top: 4pt;
        }
        .npsn i {
            width: 16pt;
            height: 18pt;
            border: 1pt solid #111111;
            margin-left: -1pt;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-style: normal;
            font-weight: 400;
            font-size: 10.2pt;
            color: #111111;
            background: #ffffff;
        }
        .npsn i:first-child { margin-left: 0; }
        .cv-crest {
            width: 118pt;
            height: 124pt;
            margin: 10pt auto 18pt;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cv-crest img {
            width: 116pt;
            height: auto;
        }
        .cvh {
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 18pt;
            font-weight: 700;
            line-height: 1.38;
            color: #111111;
            margin: 6pt 0 18pt;
            letter-spacing: 0;
        }
        .cvs {
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 18pt;
            font-weight: 700;
            color: #111111;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .cva-outer {
            width: 348pt;
            max-width: 82%;
            margin: 0 auto;
            border: 1pt solid #111111;
            padding: 2.5pt;
            background: #ffffff;
            position: relative;
        }
        .cva {
            border: 1pt solid #111111;
            padding: 12pt 20pt;
            text-align: left;
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 12.5pt;
            line-height: 1.45;
            background: #ffffff;
            color: #111111;
            position: relative;
        }
        .cva-corner {
            position: absolute;
            width: 10pt;
            height: 10pt;
            border: 1pt solid #111111;
            background: #ffffff;
        }
        .cva-tl { top: -3.5pt; left: -3.5pt; }
        .cva-tr { top: -3.5pt; right: -3.5pt; }
        .cva-bl { bottom: -3.5pt; left: -3.5pt; }
        .cva-br { bottom: -3.5pt; right: -3.5pt; }
        .cva-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cva-table td {
            padding: 1.5pt 3pt;
            vertical-align: top;
            font-weight: 400;
            color: #111111;
        }
        .cva-table td:first-child { width: 82pt; }
        .cva-table td:nth-child(2) { width: 12pt; text-align: center; }

        /* ==========================================================================
           REGISTER PESERTA DIDIK (.reg) - Format Resmi Sekolah
           ========================================================================== */
        .reg {
            font-family: Arial, sans-serif;
            color: #111111;
            border-top: 0;
            font-size: 9.6pt;
            line-height: 1.3;
            padding: 18pt 32pt 20pt 44pt;
        }
        .reg::before,
        .reg::after {
            display: none;
        }
        .reg-title {
            text-align: center;
            font-size: 12.2pt;
            font-weight: 800;
            line-height: 1.35;
            margin-bottom: 12pt;
        }
        .reg-number {
            display: flex;
            justify-content: space-between;
            padding: 0 86pt 6pt 96pt;
            font-weight: 400;
            font-size: 9.6pt;
        }
        .reg-num-val {
            display: inline-block;
            min-width: 90pt;
            border-bottom: 0.75pt solid #333333;
            padding: 0 3pt 1pt;
        }
        .reg-p1-body {
            padding-right: 86pt;
        }
        .reg-photo-stack {
            position: absolute;
            right: 32pt;
            top: 96pt;
            width: 72pt;
        }
        .reg-photo {
            width: 70pt;
            height: 88pt;
            border: 0.8pt solid #111111;
            margin-bottom: 10pt;
            padding: 0;
            background: #ffffff;
            box-sizing: border-box;
        }
        .reg-photo-inner {
            width: 100%;
            height: 100%;
            border: 0;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 4pt;
            box-sizing: border-box;
        }
        .reg-photo-size {
            position: absolute;
            top: 34pt;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5pt;
            color: #444444;
        }
        .reg-photo-label {
            font-size: 7.5pt;
            color: #111111;
        }
        .reg-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .reg-fh {
            background: transparent;
            color: #111111;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 6pt 0 2pt;
            margin: 7pt 0 2pt;
            font-size: 9.6pt;
            font-weight: 800;
            letter-spacing: 0;
        }
        .reg .fr {
            display: grid;
            grid-template-columns: 18pt 142pt 8pt 1fr;
            font-size: 9.6pt;
            padding: 1.4pt 0;
            align-items: end;
        }
        .reg .fr.in {
            grid-template-columns: 18pt 142pt 8pt 1fr;
            padding-left: 0;
        }
        .reg .fr > span:first-child { font-weight: 400; color: #111111; }
        .reg .fr > span:nth-child(2) { font-weight: 400; color: #111111; }
        .reg .fr .c { text-align: left; color: #111111; }
        .reg .fr .v {
            border-bottom: 0.75pt solid #333333;
            color: #111111;
            font-weight: 400;
            min-height: 12pt;
            padding-left: 3pt;
        }
        .reg-catatan-lines {
            margin-top: 6pt;
        }
        .reg-catatan-line {
            border-bottom: 0.75pt solid #111111;
            height: 15pt;
            line-height: 15pt;
            font-size: 9.6pt;
            padding-left: 2pt;
        }

        /* ==========================================================================
           LAPORAN CAPAIAN PEMBELAJARAN (.capaian) - Format Resmi Sekolah
           ========================================================================== */
        .capaian {
            font-family: Arial, sans-serif;
            color: #111111;
            border-top: 0;
            padding: 18pt 32pt 20pt 44pt;
        }
        .capaian::before,
        .capaian::after {
            display: none;
        }
        .capaian-title {
            text-align: left;
            padding-left: 0;
            font-size: 12.8pt;
            font-weight: 800;
            line-height: 1.38;
            margin: 0 0 12pt;
        }
        .capaian-title span {
            display: block;
            margin-top: 2pt;
            padding-left: 18pt;
            font-size: 12.8pt;
        }
        .capaian-fh,
        .fh.capaian-fh {
            background: transparent;
            color: #111111;
            border: 0;
            border-left: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 5pt 0 3pt;
            margin: 9pt 0 4pt;
            font-size: 10.1pt;
            font-weight: 800;
            letter-spacing: 0;
        }
        .capaian .fr {
            display: grid;
            grid-template-columns: 20pt 106pt 8pt 1fr;
            font-size: 10.1pt;
            padding: 1.2pt 0;
        }
        .capaian .fr > span:nth-child(2) { font-weight: 400; color: #111111; }
        .capaian .fr .c { color: #111111; }
        .capaian .fr .v {
            border: 0;
            color: #111111;
            font-weight: 400;
            padding: 0;
            min-height: 0;
        }
        .capaian .lt {
            width: 100%;
            border-collapse: collapse;
            border: 0.75pt solid #8db4e2;
            border-radius: 0;
            border-spacing: 0;
            font-size: 9.6pt;
            margin: 4pt 0;
            overflow: visible;
        }
        .capaian .lt.lt-blue-head {
            width: 100%;
        }
        .capaian .lt th,
        .capaian .lt td {
            border: 0.75pt solid #8db4e2;
            color: #111111;
            padding: 2.5pt 4pt;
            text-align: center;
            letter-spacing: 0;
        }
        .capaian .lt td.l { text-align: left; font-weight: 400; }
        .capaian .lt.lt-intra thead th {
            background: #ffffff !important;
            background-image: none !important;
            color: #111111;
            font-weight: 700;
        }
        .capaian .lt.lt-blue-head thead th,
        .capaian .lt.kk-table thead th {
            background: #dce6f1 !important;
            background-image: none !important;
            color: #111111;
            font-weight: 700;
        }
        .capaian .lt tr:nth-child(even) td {
            background: #ffffff !important;
        }
        .capaian .lt tr.alt td {
            background: #eaf2fb !important;
        }
        .capaian .lt .gh td,
        .capaian .lt tr.summary-row td {
            background: #dce6f1 !important;
            font-weight: 800;
        }
        .capaian .lt .gh td {
            text-align: left;
            padding: 3px 6px;
        }
        .capaian-ket {
            font-size: 8.8px;
            color: #111111;
            margin-top: 4px;
            line-height: 1.35;
        }
        .capaian-ket span {
            display: inline-block;
            padding-left: 16px;
        }
        .capaian .kk-table {
            margin: 4px 0 14px;
        }
        .capaian .kk-table td.kk-no {
            vertical-align: top;
            text-align: center;
            padding-top: 5px;
        }
        .capaian .kk-table td.kk-desc {
            vertical-align: top;
            text-align: left;
            height: 64pt;
            padding: 4pt 6pt;
            line-height: 1.35;
        }
        .kenaikan-keputusan {
            padding-left: 20pt;
            font-size: 10.1pt;
            margin-bottom: 4pt;
        }
        .kenaikan-table {
            margin-left: 20pt;
            border-collapse: collapse;
            font-size: 10.1pt;
        }
        .kenaikan-table td.kl {
            width: 225pt;
            text-align: right;
            padding: 1.5pt 8pt 1.5pt 0;
            color: #111111;
        }
        .kenaikan-table td.kv {
            text-align: left;
            padding: 1.5pt 0;
            color: #111111;
        }
        .capaian .ttd {
            margin-top: 26pt;
            padding: 0 12pt;
            font-size: 10.1pt;
            overflow: hidden;
        }
        .capaian .ttd-left {
            float: left;
            width: 175pt;
            text-align: center;
        }
        .capaian .ttd-right {
            float: right;
            width: 175pt;
            text-align: center;
        }
        .capaian .ttd-title {
            color: #111111;
            margin-bottom: 44pt;
            line-height: 1.4;
        }
        .capaian .ttd-name {
            font-weight: 800;
            color: #111111;
            text-decoration: none;
        }
        .capaian .ttd-nip {
            font-size: 9.5pt;
            color: #111111;
            margin-top: 2pt;
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
                min-height: 0 !important;
            }
            .pg:last-child {
                page-break-after: auto !important;
            }
            .cv,
            .cv-inner {
                min-height: 304mm !important;
                height: 304mm !important;
            }
            body.paper-a4 .cv,
            body.paper-a4 .cv-inner {
                min-height: 274mm !important;
                height: 274mm !important;
            }
        }

        /* ==========================================================================
           RESPONSIF UKURAN KERTAS A4 (210 x 297 mm)
           ========================================================================== */
        body.paper-a4 .cv,
        body.paper-a4 .cv-inner {
            min-height: 274mm;
        }
        body.paper-a4 .cv-inner { padding: 44pt 56pt 38pt; }
        body.paper-a4 .cv-crest { width: 104pt; height: 108pt; margin: 4pt auto 12pt; }
        body.paper-a4 .cv-crest img { width: 102pt; }
        body.paper-a4 .cvh { font-size: 16.5pt; margin: 4pt 0 14pt; }
        body.paper-a4 .cvs { font-size: 16.5pt; }
        body.paper-a4 .cva { font-size: 11.5pt; padding: 10pt 18pt; }

        body.paper-a4 .reg { padding: 10pt 28pt 12pt 36pt; font-size: 8.8pt; line-height: 1.22; }
        body.paper-a4 .reg-title { font-size: 11.2pt; margin-bottom: 8pt; }
        body.paper-a4 .reg-number { font-size: 8.8pt; padding: 0 78pt 4pt 88pt; }
        body.paper-a4 .reg-fh { margin: 5pt 0 2pt; padding: 3pt 0 1pt; font-size: 8.8pt; }
        body.paper-a4 .reg .fr { font-size: 8.8pt; padding: 0.8pt 0; }
        body.paper-a4 .reg-p1-body { padding-right: 78pt; }
        body.paper-a4 .reg-photo-stack { right: 28pt; top: 82pt; width: 68pt; }
        body.paper-a4 .reg-photo { width: 66pt; height: 82pt; margin-bottom: 7pt; }
        body.paper-a4 .reg-photo img { width: 66pt; height: 82pt; }
        body.paper-a4 .reg-catatan-line { height: 13pt; line-height: 13pt; font-size: 8.8pt; }

        body.paper-a4 .capaian { padding: 10pt 28pt 12pt 36pt; }
        body.paper-a4 .capaian-title { font-size: 11.5pt; margin-bottom: 8pt; }
        body.paper-a4 .capaian-title span { font-size: 11.5pt; }
        body.paper-a4 .capaian-fh { font-size: 9.2pt; margin: 6pt 0 3pt; padding: 2pt 0; }
        body.paper-a4 .capaian .fr { font-size: 9.2pt; padding: 0.8pt 0; }
        body.paper-a4 .capaian .lt { font-size: 8.2pt; margin: 3pt 0; }
        body.paper-a4 .capaian .lt th,
        body.paper-a4 .capaian .lt td { padding: 1.8pt 3.5pt; }
        body.paper-a4 .capaian .kk-table { margin: 3pt 0 6pt; }
        body.paper-a4 .capaian .kk-table td.kk-desc { height: 42pt; padding: 3pt 5pt; line-height: 1.28; }
        body.paper-a4 .kenaikan-keputusan,
        body.paper-a4 .kenaikan-table,
        body.paper-a4 .capaian .ttd { font-size: 9.2pt; }
        body.paper-a4 .capaian .ttd { margin-top: 18pt; }
        body.paper-a4 .capaian .ttd-title { margin-bottom: 36pt; }
    </style>
</head>
<body class="paper-{{ strtolower($paper ?? 'F4') }}">

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
                    @foreach(['X', 'XI', 'XII'] as $tingkat)
                        <label class="pdf-slot-pill"><input type="checkbox" name="slots[]" value="{{ $tingkat }}" {{ in_array($tingkat, $selectedSlotGrades ?? ['X', 'XI', 'XII'], true) ? 'checked' : '' }} onchange="this.form.submit()"> {{ $tingkat }}</label>
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
    document.body.classList.remove('paper-a4', 'paper-f4');
    document.body.classList.add('paper-' + size.toLowerCase());
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
