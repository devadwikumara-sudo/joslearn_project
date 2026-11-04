@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Data Siswa</h2>
        <p class="sub">Total {{ count($siswa) }} siswa terdaftar dalam sistem</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="note er">{{ session('error') }}</div>
@endif

<div class="bar">
    <form method="GET" action="{{ route('admin.data-siswa') }}" style="display:contents">
        <input type="text" name="search" placeholder="Cari NISN, NIS, atau nama..." value="{{ request('search') }}">
        <select name="kelas" onchange="this.form.submit()">
            <option value="">Semua Kelas</option>
            <option value="X-1" {{ request('kelas')=='X-1' ? 'selected' : '' }}>X-1</option>
            <option value="XI-1" {{ request('kelas')=='XI-1' ? 'selected' : '' }}>XI-1</option>
            <option value="XII-1" {{ request('kelas')=='XII-1' ? 'selected' : '' }}>XII-1</option>
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="Aktif" {{ request('status')=='Aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="Lulus" {{ request('status')=='Lulus' ? 'selected' : '' }}>Lulus</option>
            <option value="Pindah" {{ request('status')=='Pindah' ? 'selected' : '' }}>Pindah</option>
            <option value="Keluar" {{ request('status')=='Keluar' ? 'selected' : '' }}>Keluar</option>
        </select>
        <button type="submit" class="btn">Cari</button>
    </form>
    <a href="{{ route('admin.data-siswa.export') }}" class="btn o">⬇ Export Excel</a>
    <a href="{{ route('admin.import.nilai') }}" class="btn o">⬆ Import Data</a>
    <a href="{{ route('admin.form-pendataan') }}" class="btn a">+ Tambah Siswa</a>
</div>

<div class="card wide">
    <table class="tw">
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>JK</th>
                <th>TTL</th>
                <th>Agama</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($siswa as $index => $s)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $s->nisn ?? '-' }}</td>
                <td>{{ $s->nis ?? '-' }}</td>
                <td>
                    <strong>{{ $s->nama ?? '-' }}</strong>
                </td>
                <td>{{ $s->kelas ?? '-' }}</td>
                <td>{{ $s->jenis_kelamin ?? '-' }}</td>
                <td>{{ $s->tempat_lahir ?? '-' }}, {{ isset($s->tanggal_lahir) ? \Carbon\Carbon::parse($s->tanggal_lahir)->format('d/m/Y') : '-' }}</td>
                <td>{{ $s->agama ?? '-' }}</td>
                <td>
                    @if(($s->status ?? '') === 'Aktif')
                        <span class="bd ok">Aktif</span>
                    @elseif(($s->status ?? '') === 'Lulus')
                        <span class="bd">Lulus</span>
                    @elseif(($s->status ?? '') === 'Pindah')
                        <span class="bd w">Pindah</span>
                    @elseif(($s->status ?? '') === 'Keluar')
                        <span class="bd e">Keluar</span>
                    @else
                        <span class="bd">{{ $s->status ?? 'Aktif' }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.data-siswa.show', $s->id ?? 1) }}" class="btn o">Lihat</a>
                    <a href="{{ route('admin.data-siswa.pdf', $s->id ?? 1) }}" class="btn o">PDF</a>
                    <a href="{{ route('admin.form-pendataan.edit', $s->id ?? 1) }}" class="btn o">Edit</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center;padding:2rem;color:var(--mu)">
                    Belum ada data siswa
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
