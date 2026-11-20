<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Biodata Siswa - {{ $siswa->nama_lengkap }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border-bottom: 2.5px solid #0f3661;
            padding-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-box {
            width: 44px;
            height: 44px;
            background-color: #0f3661;
            color: #ffffff;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            line-height: 44px;
            border-radius: 6px;
        }
        .school-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f3661;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .school-subtitle {
            font-size: 9.5px;
            color: #64748b;
            margin: 0;
        }
        .doc-badge {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            text-align: right;
            display: inline-block;
        }
        .section-banner {
            background-color: #0f3661;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 10px;
            margin: 14px 0 8px 0;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 9px;
            text-align: left;
            vertical-align: top;
            font-size: 9.5px;
        }
        .data-table th {
            width: 30%;
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }
        .data-table td {
            background-color: #ffffff;
            color: #0f172a;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fbfcfe;
        }
        .status-pill {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .footer-note {
            margin-top: 24px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    @php($riwayat = $siswa->riwayatKelas->first())
    <table class="header-table">
        <tr>
            <td style="width: 54px;">
                <div class="logo-box">JB</div>
            </td>
            <td>
                <div class="school-title">Biodata Peserta Didik</div>
                <div class="school-subtitle">JosBook Digital School System &bull; Dokumen Resmi Buku Induk Siswa</div>
            </td>
            <td style="text-align: right;">
                <span class="doc-badge">NISN: {{ $siswa->nisn ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <div class="section-banner">Data Pribadi Peserta Didik</div>
    <table class="data-table">
        <tbody>
            <tr><th>Nama Lengkap</th><td><b>{{ $siswa->nama_lengkap }}</b></td></tr>
            <tr><th>NISN</th><td>{{ $siswa->nisn }}</td></tr>
            <tr><th>Nomor Induk Siswa (NIS)</th><td>{{ $siswa->nomor_induk ?? '-' }}</td></tr>
            <tr><th>Kelas</th><td>{{ $riwayat?->kelas?->nama ?? '-' }}</td></tr>
            <tr><th>Tahun Ajaran</th><td>{{ $riwayat?->tahunAjaran?->nama ?? '-' }}</td></tr>
            <tr><th>Status Siswa</th><td><span class="status-pill">{{ $riwayat?->status ?? 'Aktif' }}</span></td></tr>
            <tr><th>Jenis Kelamin</th><td>{{ $siswa->jenis_kelamin ?? '-' }}</td></tr>
            <tr><th>Tempat, Tanggal Lahir</th><td>{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ?? '-' }}</td></tr>
            <tr><th>Agama</th><td>{{ $siswa->agama ?? '-' }}</td></tr>
            <tr><th>Nama Ayah</th><td>{{ $siswa->nama_ayah ?? '-' }}</td></tr>
            <tr><th>Nama Ibu</th><td>{{ $siswa->nama_ibu ?? '-' }}</td></tr>
            <tr><th>Alamat Tempat Tinggal</th><td>{{ $siswa->alamat_jalan ?? '-' }}</td></tr>
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan secara elektronik oleh JosBook School Management System pada {{ date('d F Y, H:i') }} WIB.
    </div>
</body>
</html>
