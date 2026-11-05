<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Siswa</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 5px; }
        p { color: #475569; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #94a3b8; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>Daftar Seluruh Siswa</h1>
    <p>Tahun ajaran: {{ $tahunAjaranLabel }} | Kelas: {{ $kelasLabel }} | Jumlah: {{ $siswaList->count() }} siswa</p>
    <table>
        <thead>
            <tr>
                <th style="width: 4%">No.</th>
                <th style="width: 12%">NISN</th>
                <th style="width: 12%">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 10%">Kelas</th>
                <th style="width: 8%">JK</th>
                <th style="width: 18%">Tempat, Tanggal Lahir</th>
                <th style="width: 12%">Tahun Ajaran</th>
                <th style="width: 10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($siswaList as $index => $siswa)
                @php($riwayat = $siswa->riwayatKelas->first())
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $siswa->nisn ?? '-' }}</td>
                    <td>{{ $siswa->nomor_induk ?? '-' }}</td>
                    <td>{{ $siswa->nama_lengkap ?? '-' }}</td>
                    <td>{{ $riwayat?->kelas?->nama ?? '-' }}</td>
                    <td>{{ $siswa->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ?? '-' }}</td>
                    <td>{{ $riwayat?->tahunAjaran?->nama ?? '-' }}</td>
                    <td>{{ $riwayat?->status ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center">Tidak ada siswa pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
