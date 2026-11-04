<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Biodata Siswa - {{ $siswa->nama_lengkap }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p { color: #64748b; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; vertical-align: top; }
        th { width: 30%; background: #f1f5f9; }
    </style>
</head>
<body>
    @php($riwayat = $siswa->riwayatKelas->first())
    <h1>Biodata Peserta Didik</h1>
    <p>Dokumen data siswa JosBook</p>
    <table>
        <tbody>
            <tr><th>Nama lengkap</th><td>{{ $siswa->nama_lengkap }}</td></tr>
            <tr><th>NISN</th><td>{{ $siswa->nisn }}</td></tr>
            <tr><th>Nomor induk</th><td>{{ $siswa->nomor_induk ?? '-' }}</td></tr>
            <tr><th>Kelas</th><td>{{ $riwayat?->kelas?->nama ?? '-' }}</td></tr>
            <tr><th>Tahun ajaran</th><td>{{ $riwayat?->tahunAjaran?->nama ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $riwayat?->status ?? '-' }}</td></tr>
            <tr><th>Jenis kelamin</th><td>{{ $siswa->jenis_kelamin ?? '-' }}</td></tr>
            <tr><th>Tempat, tanggal lahir</th><td>{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ?? '-' }}</td></tr>
            <tr><th>Agama</th><td>{{ $siswa->agama ?? '-' }}</td></tr>
            <tr><th>Nama ayah</th><td>{{ $siswa->nama_ayah ?? '-' }}</td></tr>
            <tr><th>Nama ibu</th><td>{{ $siswa->nama_ibu ?? '-' }}</td></tr>
            <tr><th>Alamat</th><td>{{ $siswa->alamat_jalan ?? '-' }}</td></tr>
        </tbody>
    </table>
</body>
</html>
