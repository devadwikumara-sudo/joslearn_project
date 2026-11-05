@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Guru')

@section('content')
<h2>Data Siswa</h2>
<p class="sub">Daftar ini terhubung langsung dengan data siswa yang dikelola admin.</p>

<div class="card">
    <div class="bar">
        <form method="GET" action="{{ route('guru.data-siswa') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select name="tahun_ajaran" onchange="this.form.submit()">
                <option value="">Semua tahun ajaran</option>
                @foreach($tahunAjaranList as $tahunAjaran)
                    <option value="{{ $tahunAjaran->id }}" {{ $tahunAjaranTerpilih === (string) $tahunAjaran->id ? 'selected' : '' }}>{{ $tahunAjaran->nama }}</option>
                @endforeach
            </select>

            <select name="kelas" onchange="this.form.submit()">
                <option value="">Semua kelas</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas }}" {{ $kelasTerpilih === $kelas ? 'selected' : '' }}>{{ $kelas }}</option>
                @endforeach
            </select>

            <input type="text" name="q" placeholder="Cari nama / NISN / NIS" value="{{ $search }}" style="width:200px">

            <select name="st" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $status)
                    <option value="{{ $status }}" {{ $statusFilter === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn" style="padding:6px 12px">Cari</button>
        </form>

        <span class="sp"></span>
        <b>{{ $siswa->total() }} siswa</b>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>NISN</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>JK</th>
                    <th>TTL</th>
                    <th>Agama</th>
                    <th>Orang tua</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa as $murid)
                    @php
                        $riwayat = $murid->riwayatKelas->first();
                        $kelasNama = $riwayat?->kelas?->nama ?? 'Belum ditetapkan';
                        $statusNama = $riwayat?->status ?? 'Belum diatur';
                        $badgeClass = in_array($statusNama, ['Aktif', 'Lulus'], true)
                            ? 'ok'
                            : (in_array($statusNama, ['Pindah keluar', 'Belum diatur'], true) ? 'w' : 'e');
                    @endphp
                    <tr>
                        <td><code>{{ $murid->nisn ?? '-' }}</code></td>
                        <td>{{ $murid->nomor_induk ?? '-' }}</td>
                        <td><b>{{ $murid->nama_lengkap ?? '-' }}</b></td>
                        <td>{{ $kelasNama }}</td>
                        <td>{{ $murid->jenis_kelamin ?? '-' }}</td>
                        <td>{{ $murid->tempat_lahir ?? '-' }}, {{ $murid->tanggal_lahir ? \Carbon\Carbon::parse($murid->tanggal_lahir)->translatedFormat('d M Y') : '-' }}</td>
                        <td>{{ $murid->agama ?? '-' }}</td>
                        <td>{{ $murid->nama_ayah ?? $murid->nama_ibu ?? '-' }}</td>
                        <td><span class="bd {{ $badgeClass }}">{{ $statusNama }}</span></td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('guru.data-siswa.show', $murid->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:28px;color:var(--mu)">
                            Tidak ada data siswa untuk kriteria ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($siswa->hasPages())
        <div style="margin-top:14px">{{ $siswa->links() }}</div>
    @endif
</div>
@endsection
