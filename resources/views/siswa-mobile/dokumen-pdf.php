<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }} - {{ $siswa->nama_lengkap }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin: 18px 0 6px; color: #0b2a6b; }
        p { color: #64748b; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .kunci { width: 30%; background: #f1f5f9; font-weight: bold; }
        .angka { text-align: right; width: 18%; }
        .tebal td { font-weight: bold; background: #f8fafc; }
    </style>
</head>
<body>
    @php
        $tgl = fn ($x) => $x ? \Illuminate\Support\Carbon::parse($x)->format('d-m-Y') : '-';
        $tampilBio    = in_array($jenis, ['bio', 'all']);
        $tampilNilai  = in_array($jenis, ['nilai', 'all']);
        $tampilHadir  = in_array($jenis, ['hadir', 'all']);
        $tampilEkskul = in_array($jenis, ['ekskul', 'all']);
    @endphp

    <h1>{{ $judul }} Peserta Didik</h1>
    <p>
        {{ $siswa->nama_lengkap }} &middot; NISN {{ $siswa->nisn }}
        @if ($riwayat) &middot; Kelas {{ $riwayat->kelas?->nama ?? '-' }} &middot; TA {{ $riwayat->tahunAjaran?->nama ?? '-' }} @endif
        @if ($semester) &middot; Semester {{ $semester }} @endif
    </p>

    @if ($tampilBio)
        <h2>Biodata</h2>
        <table>
            <tbody>
                <tr><td class="kunci">Nama lengkap</td><td>{{ $biodata['nama'] }}</td></tr>
                <tr><td class="kunci">NISN</td><td>{{ $biodata['nisn'] }}</td></tr>
                <tr><td class="kunci">Nomor induk</td><td>{{ $biodata['nomor_induk'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Tempat, tanggal lahir</td><td>{{ $biodata['tempat_lahir'] ?? '-' }}, {{ $tgl($biodata['tanggal_lahir']) }}</td></tr>
                <tr><td class="kunci">Jenis kelamin</td><td>{{ $biodata['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($biodata['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') }}</td></tr>
                <tr><td class="kunci">Agama</td><td>{{ $biodata['agama'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Alamat</td><td>{{ $biodata['alamat'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Nama ayah</td><td>{{ $biodata['nama_ayah'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Nama ibu</td><td>{{ $biodata['nama_ibu'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Nama wali</td><td>{{ $biodata['nama_wali'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Sekolah asal</td><td>{{ $biodata['sekolah_asal'] ?? '-' }}</td></tr>
                <tr><td class="kunci">Tanggal diterima</td><td>{{ $tgl($biodata['tanggal_diterima']) }}</td></tr>
                <tr><td class="kunci">Tinggi / berat badan</td><td>{{ $biodata['tinggi_badan_cm'] ?? '-' }} cm / {{ $biodata['berat_badan_kg'] ?? '-' }} kg</td></tr>
            </tbody>
        </table>
    @endif

    @if ($tampilNilai)
        <h2>Nilai Semester {{ $semester }}</h2>
        @if (! empty($data['nilai']))
            <table>
                <thead><tr><th>Mata Pelajaran</th><th class="angka">Nilai</th></tr></thead>
                <tbody>
                    @foreach ($data['nilai'] as $n)
                        <tr><td>{{ $n['mapel'] }}</td><td class="angka">{{ number_format($n['nilai'], 2) }}</td></tr>
                    @endforeach
                    <tr class="tebal"><td>Rata-rata</td><td class="angka">{{ number_format($data['rata_rata'], 2) }}</td></tr>
                </tbody>
            </table>
        @else
            <p>Belum ada nilai.</p>
        @endif
    @endif

    @if ($tampilHadir)
        <h2>Kehadiran Semester {{ $semester }}</h2>
        @if (! empty($data['kehadiran']))
            <table>
                <thead><tr><th>Sakit</th><th>Izin</th><th>Alpa</th></tr></thead>
                <tbody><tr>
                    <td>{{ $data['kehadiran']['sakit'] }} hari</td>
                    <td>{{ $data['kehadiran']['izin'] }} hari</td>
                    <td>{{ $data['kehadiran']['alpa'] }} hari</td>
                </tr></tbody>
            </table>
            @if (! empty($data['kehadiran']['kokurikuler']))
                <p style="margin-top:8px">Kokurikuler: {{ $data['kehadiran']['kokurikuler'] }}</p>
            @endif
        @else
            <p>Belum ada data kehadiran.</p>
        @endif
    @endif

    @if ($tampilEkskul)
        <h2>Ekstrakurikuler Semester {{ $semester }}</h2>
        @if (! empty($data['ekskul']))
            <table>
                <thead><tr><th>Kegiatan</th><th>Predikat</th></tr></thead>
                <tbody>
                    @foreach ($data['ekskul'] as $e)
                        <tr><td>{{ $e['kegiatan'] }}</td><td>{{ $e['predikat'] ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Belum ada data ekstrakurikuler.</p>
        @endif
    @endif
</body>
</html>