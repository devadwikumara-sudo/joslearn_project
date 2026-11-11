@extends('layouts.app')

@section('title', 'Detail Siswa — JosBook Guru')

@section('content')
<div class="bar">
    <div>
        <h2 style="margin:0">Detail Siswa</h2>
        <p class="sub" style="margin:4px 0 0">{{ $siswa->nama_lengkap }} · NISN {{ $siswa->nisn }}</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('guru.data-siswa') }}" class="btn o">Kembali</a>
</div>

<div class="card" style="margin-top:14px">
    <h3>Identitas Siswa</h3>
    <div class="fl">
        <div><label>Nama Lengkap</label><p>{{ $siswa->nama_lengkap }}</p></div>
        <div><label>NISN</label><p>{{ $siswa->nisn ?? '-' }}</p></div>
        <div><label>NIS</label><p>{{ $siswa->nomor_induk ?? '-' }}</p></div>
        <div><label>Jenis Kelamin</label><p>{{ $siswa->jenis_kelamin ?? '-' }}</p></div>
        <div><label>Tempat, Tanggal Lahir</label><p>{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d M Y') : '-' }}</p></div>
        <div><label>Agama</label><p>{{ $siswa->agama ?? '-' }}</p></div>
        <div><label>Orang Tua</label><p>{{ $siswa->nama_ayah ?? $siswa->nama_ibu ?? '-' }}</p></div>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <h3>Riwayat Kelas</h3>
    <div class="tw">
        <table>
            <thead>
                <tr><th>Tahun Ajaran</th><th>Kelas</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($siswa->riwayatKelas as $riwayat)
                    <tr>
                        <td>{{ $riwayat->tahunAjaran?->nama ?? '-' }}</td>
                        <td>{{ $riwayat->kelas?->nama ?? '-' }}</td>
                        <td>{{ $riwayat->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;padding:18px;color:var(--mu)">Belum ada riwayat kelas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<<<<<<< HEAD

@include('partials.siswa-akademik-content', [
    'canManageAcademicRecords' => false,
    'academicManagePermissions' => $academicManagePermissions,
])
=======
>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
@endsection
