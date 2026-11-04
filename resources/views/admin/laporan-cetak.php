<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekap {{ $ta }} — JosBook</title>
    <style>
        @page { size: 210mm 297mm; margin: 15mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .kop {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .kop .logo-placeholder {
            width: 50px;
            height: 50px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            font-style: italic;
        }
        .kop h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .kop p { margin: 2px 0 0; font-size: 11px; color: #444; }
        h4 { text-align: center; text-transform: uppercase; margin: 16px 0; font-size: 14px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .ttd {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 40px;
        }
        .ttd > div {
            text-align: center;
            width: 200px;
        }
        .no-print {
            margin-bottom: 16px;
            text-align: right;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px; background: #0b2240; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="kop">
        <div class="logo-placeholder">BI</div>
        <div>
            <h2>{{ strtoupper($identitas->nama ?? 'SMA NEGERI 1') }}</h2>
            <p>{{ $identitas->alamat ?? 'Jl. Pendidikan No. 1' }} · NPSN: {{ $identitas->npsn ?? '20539300' }}</p>
        </div>
    </div>

    <h4>LAPORAN REKAP TAHUN AJARAN {{ $ta }}</h4>

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
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
                <td><b>{{ $r['kelas'] }}</b></td>
                <td style="text-align: center;">{{ $r['siswa_count'] }}</td>
                <td style="text-align: center;">{{ $r['rata_nilai'] }}</td>
                <td style="text-align: center;">{{ $r['kehadiran'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd">
        <div>
            Mengetahui,<br>
            Wali Kelas<br><br><br><br>
            ( .................................................. )
        </div>
        <div>
            {{ $identitas->kabupaten ?? 'Kabupaten' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
            Kepala Sekolah<br><br><br><br>
            <b>{{ $identitas->nama_kepala_sekolah ?? '..................................................' }}</b><br>
            NIP. {{ $identitas->nip_kepala_sekolah ?? '..............................' }}
        </div>
    </div>
</body>
</html>

