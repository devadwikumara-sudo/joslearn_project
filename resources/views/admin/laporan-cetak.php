<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekap {{ $ta }} — JosBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: 210mm 297mm;
            margin: 12mm 15mm 15mm 15mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 24px;
            font-size: 11px;
            line-height: 1.5;
        }
        .no-print {
            background: #0f2b5c;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 14px rgba(15, 43, 92, 0.25);
        }
        .btn-print {
            padding: 7px 16px;
            font-size: 11.5px;
            font-weight: 600;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background: #1e40af;
        }
        .kop {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 2.5px solid #0f3661;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .kop .logo-placeholder {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #0f3661 0%, #1e40af 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 54, 97, 0.2);
        }
        .kop h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: #0f3661;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop p {
            margin: 3px 0 0;
            font-size: 10px;
            color: #475569;
        }
        .title-card {
            text-align: center;
            margin: 18px 0 20px;
        }
        .title-card h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #0f3661;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .title-card .badge {
            display: inline-block;
            margin-top: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 9.5px;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 12px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 24px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        th, td {
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 10px;
            text-align: left;
            font-size: 10.5px;
        }
        th:last-child, td:last-child {
            border-right: none;
        }
        tr:last-child td {
            border-bottom: none;
        }
        th {
            background: linear-gradient(180deg, #0f3661 0%, #0a2540 100%);
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .ttd {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 40px;
            font-size: 11px;
        }
        .ttd > div {
            text-align: center;
            width: 220px;
        }
        .ttd .sign-title {
            color: #475569;
            margin-bottom: 50px;
        }
        .ttd .sign-name {
            font-weight: 700;
            color: #0f3661;
            text-decoration: underline;
        }
        .ttd .sign-nip {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div style="font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 8px;">
            <span>📄 JosBook Cetak Laporan Rekapitulasi</span>
            <small style="opacity: 0.7;">· Tahun Ajaran {{ $ta }}</small>
        </div>
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="kop">
        <div class="logo-placeholder">JB</div>
        <div>
            <h2>{{ strtoupper($identitas->nama ?? 'SMA NEGERI 1') }}</h2>
            <p>{{ $identitas->alamat ?? 'Jl. Pendidikan No. 1' }} &bull; NPSN: {{ $identitas->npsn ?? '20539300' }}</p>
        </div>
    </div>

    <div class="title-card">
        <h4>LAPORAN REKAP TAHUN AJARAN {{ $ta }}</h4>
        <span class="badge">Sistem Informasi Buku Induk JosBook</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 44px; text-align: center;">No</th>
                <th>Kelas</th>
                <th style="text-align: center;">Jumlah Siswa</th>
                <th style="text-align: center;">Rata-rata Nilai</th>
                <th style="text-align: center;">Ketidakhadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $r)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td><b style="color: #0f3661;">{{ $r['kelas'] }}</b></td>
                <td style="text-align: center;">{{ $r['siswa_count'] }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $r['rata_nilai'] }}</td>
                <td style="text-align: center;">{{ $r['kehadiran'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd">
        <div>
            <div class="sign-title">Mengetahui,<br>Wali Kelas</div>
            <div class="sign-name">( .................................................. )</div>
        </div>
        <div>
            <div class="sign-title">{{ $identitas->kabupaten ?? 'Kabupaten' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Kepala Sekolah</div>
            <div class="sign-name">{{ $identitas->nama_kepala_sekolah ?? '..................................................' }}</div>
            <div class="sign-nip">NIP. {{ $identitas->nip_kepala_sekolah ?? '..............................' }}</div>
        </div>
    </div>
</body>
</html>
