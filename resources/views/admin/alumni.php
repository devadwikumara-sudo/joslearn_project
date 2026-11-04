@extends('layouts.app')

@section('title', 'Alumni & Kontrol Induk — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Alumni &amp; Kontrol Induk</h2>
        <p class="sub">Kelola data alumni, siswa pindah, dan keluar dari sistem</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

<div class="bar">
    <form method="GET" action="{{ route('admin.alumni') }}" style="display:contents">
        <select name="jenis" onchange="this.form.submit()">
            <option value="">Semua Jenis</option>
            <option value="Lulus" {{ request('jenis')=='Lulus' ? 'selected' : '' }}>Alumni (Lulus)</option>
            <option value="Pindah" {{ request('jenis')=='Pindah' ? 'selected' : '' }}>Pindah / Keluar</option>
        </select>
        <select name="tahun" onchange="this.form.submit()">
            <option value="">Semua Tahun</option>
            @for($y = date('Y'); $y >= 2015; $y--)
            <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <input type="text" name="search" placeholder="Cari nama, NIS, atau NISN..." value="{{ request('search') }}">
        <button type="submit" class="btn">Cari</button>
    </form>
    <a href="{{ route('admin.alumni.export') }}" class="btn o">⬇ Export Excel</a>
</div>

<div class="note" style="border-left:4px solid var(--a);background:#e8f0fb;color:#1a4a8a">
    ℹ️ <strong>Kontrol Induk</strong> — Halaman ini menampilkan seluruh siswa yang sudah tidak aktif: lulus, pindah, maupun keluar. Data dapat diedit dan dicetak sebagai dokumen resmi sekolah.
</div>

<div class="card wide">
    <table class="tw">
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Jenis Registrasi</th>
                <th>Tanggal Masuk</th>
                <th>Alasan Keluar</th>
                <th>Tanggal Keluar</th>
                <th>Tahun Lulus</th>
                <th>Melanjutkan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumni as $index => $a)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $a->nis ?? '-' }}</td>
                <td><strong>{{ $a->nama ?? '-' }}</strong></td>
                <td>{{ $a->nisn ?? '-' }}</td>
                <td>{{ $a->jenis_registrasi ?? '-' }}</td>
                <td>{{ isset($a->tanggal_masuk) ? \Carbon\Carbon::parse($a->tanggal_masuk)->format('d/m/Y') : '-' }}</td>
                <td>{{ $a->alasan_keluar ?? '-' }}</td>
                <td>{{ isset($a->tanggal_keluar) ? \Carbon\Carbon::parse($a->tanggal_keluar)->format('d/m/Y') : '-' }}</td>
                <td>{{ $a->tahun_lulus ?? '-' }}</td>
                <td>{{ $a->melanjutkan ?? '-' }}</td>
                <td>
                    @if(($a->status ?? '') === 'Lulus')
                        <span class="bd ok">Lulus</span>
                    @elseif(($a->status ?? '') === 'Pindah')
                        <span class="bd w">Pindah</span>
                    @elseif(($a->status ?? '') === 'Keluar')
                        <span class="bd e">Keluar</span>
                    @else
                        <span class="bd">{{ $a->status ?? 'Lulus' }}</span>
                    @endif
                </td>
                <td class="angs">
                    <a href="{{ route('admin.data-siswa.show', $a->id ?? 1) }}" class="btn o">Lihat</a>
                    <a href="{{ route('admin.form-pendataan.edit', $a->id ?? 1) }}" class="btn o">Edit</a>
                    <a href="{{ route('admin.data-siswa.pdf', $a->id ?? 1) }}" class="btn o">PDF</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" style="text-align:center;padding:2rem;color:var(--mu)">
                    Belum ada data alumni
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
