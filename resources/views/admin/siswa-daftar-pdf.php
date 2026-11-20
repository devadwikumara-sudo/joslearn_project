<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Siswa — JosBook</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2.5px solid #0f3661;
            padding-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-box {
            width: 38px;
            height: 38px;
            background-color: #0f3661;
            color: #ffffff;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            line-height: 38px;
            border-radius: 5px;
        }
        .title-block {
            padding-left: 10px;
        }
        .title-main {
            font-size: 15px;
            font-weight: bold;
            color: #0f3661;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .title-sub {
            font-size: 9px;
            color: #64748b;
            margin: 0;
        }
        .meta-pills {
            text-align: right;
            font-size: 8.5px;
        }
        .pill {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-left: 4px;
        }
        table.content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.content-table th, table.content-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: middle;
            font-size: 8px;
        }
        table.content-table th {
            background-color: #0f3661;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.3px;
        }
        table.content-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.content-table tr {
            page-break-inside: avoid;
        }
        .status-badge {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
        }
        .footer-table {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .footer-table td {
            border: none;
            padding: 4px 0;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 44px;">
                <div class="logo-box">JB</div>
            </td>
            <td class="title-block">
                <div class="title-main">Daftar Seluruh Peserta Didik</div>
                <div class="title-sub">JosBook Digital School Management System &bull; Dokumen Rekap Buku Induk</div>
            </td>
            <td class="meta-pills">
                <span class="pill">Tahun Ajaran: {{ $tahunAjaranLabel }}</span>
                <span class="pill">Kelas: {{ $kelasLabel }}</span>
                <span class="pill" style="background-color: #fef3c7; border-color: #fde68a; color: #92400e;">Total: {{ $siswaList->count() }} Siswa</span>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 3%">No.</th>
                <th style="width: 11%">NISN</th>
                <th style="width: 10%">NIS</th>
                <th>Nama Lengkap Peserta Didik</th>
                <th style="width: 8%; text-align: center;">Kelas</th>
                <th style="width: 5%; text-align: center;">JK</th>
                <th style="width: 20%">Tempat, Tanggal Lahir</th>
                <th style="width: 11%; text-align: center;">Tahun Ajaran</th>
                <th style="width: 9%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($siswaList as $index => $siswa)
                @php($riwayat = $siswa->riwayatKelas->first())
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="font-weight: bold; color: #0f3661;">{{ $siswa->nisn ?? '-' }}</td>
                    <td>{{ $siswa->nomor_induk ?? '-' }}</td>
                    <td style="font-weight: 600;">{{ $siswa->nama_lengkap ?? '-' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $riwayat?->kelas?->nama ?? '-' }}</td>
                    <td style="text-align: center;">{{ $siswa->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ?? '-' }}</td>
                    <td style="text-align: center;">{{ $riwayat?->tahunAjaran?->nama ?? '-' }}</td>
                    <td style="text-align: center;">
                        <span class="status-badge">{{ $riwayat?->status ?? 'Aktif' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #64748b;">
                        Tidak ada data siswa ditemukan pada filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td>Dicetak pada {{ date('d F Y, H:i') }} WIB &bull; Sistem Informasi Buku Induk Siswa (JosBook)</td>
            <td style="text-align: right;">Halaman 1 &bull; Dokumen Sah Sekolah</td>
        </tr>
    </table>
</body>
</html>
