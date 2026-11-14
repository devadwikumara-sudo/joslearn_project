@extends('layouts.app')

@section('title', 'Kenaikan & Mutasi — JosBook')

@section('content')
<h2>Kenaikan &amp; Mutasi</h2>
<p class="sub">Tahun ajaran {{ $taAktif }}. Proses kenaikan kelas dan catat perubahan siswa pada halaman yang sama.</p>

<form method="GET" action="{{ route($routePrefix . '.kenaikan-mutasi') }}" class="card">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="bar">
        <div>
            <label for="filter-tahun-ajaran">Tahun ajaran</label>
            <select id="filter-tahun-ajaran" name="tahun_ajaran">
                @forelse($tahunAjaranList as $tahunAjaran)
                    <option value="{{ $tahunAjaran->id }}" {{ (int) $taAktifId === (int) $tahunAjaran->id ? 'selected' : '' }}>
                        {{ $tahunAjaran->nama }}
                    </option>
                @empty
                    <option value="">{{ $taAktif }}</option>
                @endforelse
            </select>
        </div>
        <div>
            <label for="filter-kelas">Kelas</label>
            <select id="filter-kelas" name="kelas">
                @if($isAdmin)
                    <option value="*" {{ $kelasAsal === '*' ? 'selected' : '' }}>Semua kelas</option>
                @endif
                @foreach($kelasAsalList as $namaKelas)
                    <option value="{{ $namaKelas }}" {{ $kelasAsal === $namaKelas ? 'selected' : '' }}>{{ $namaKelas }}</option>
                @endforeach
            </select>
        </div>
        <span class="sp"></span>
        <button type="submit" class="btn o">Terapkan filter</button>
    </div>
</form>

<nav class="seg" aria-label="Tab Kenaikan dan Mutasi">
    <a
        href="{{ route($routePrefix . '.kenaikan-mutasi', ['tab' => 'kenaikan', 'tahun_ajaran' => $taAktifId ?: null, 'kelas' => $kelasAsal]) }}"
        class="tab-btn {{ $tab === 'kenaikan' ? 'on' : '' }}"
        @if($tab === 'kenaikan') aria-current="page" @endif
    >Kenaikan Kelas</a>
    @if($isAdmin)
        <a
            href="{{ route($routePrefix . '.kenaikan-mutasi', ['tab' => 'mutasi', 'tahun_ajaran' => $taAktifId ?: null, 'kelas' => $kelasAsal]) }}"
            class="tab-btn {{ $tab === 'mutasi' ? 'on' : '' }}"
            @if($tab === 'mutasi') aria-current="page" @endif
        >Mutasi Siswa</a>
    @endif
</nav>

@if($tab === 'kenaikan')
    @include('admin._kenaikan-mutasi-kenaikan')
@elseif($isAdmin && $tab === 'mutasi')
    @include('admin._kenaikan-mutasi-mutasi')
@endif

@include('admin._kenaikan-mutasi-riwayat')
@endsection
