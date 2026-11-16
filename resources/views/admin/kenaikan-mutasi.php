@extends('layouts.app')

@section('title', 'Kenaikan & Mutasi — JosBook')

@section('content')
<h2>Kenaikan &amp; Mutasi</h2>
<p class="sub">Tahun ajaran {{ $taAktif }} · proses kenaikan kelas dan catat perubahan siswa.</p>

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

<form method="GET" action="{{ route($routePrefix . '.kenaikan-mutasi') }}" class="page-filter">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="tahun_ajaran" aria-label="Tahun ajaran" onchange="this.form.submit()">
        @forelse($tahunAjaranList as $tahunAjaran)
            <option value="{{ $tahunAjaran->id }}" {{ (int) $taAktifId === (int) $tahunAjaran->id ? 'selected' : '' }}>
                Tahun ajaran {{ $tahunAjaran->nama }}
            </option>
        @empty
            <option value="">Tahun ajaran {{ $taAktif }}</option>
        @endforelse
    </select>
    <select name="kelas" aria-label="Kelas" onchange="this.form.submit()">
        @if($isAdmin)
            <option value="*" {{ $kelasAsal === '*' ? 'selected' : '' }}>Semua kelas</option>
        @endif
        @foreach($kelasAsalList as $namaKelas)
            <option value="{{ $namaKelas }}" {{ $kelasAsal === $namaKelas ? 'selected' : '' }}>Kelas {{ $namaKelas }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn o">Terapkan</button></noscript>
</form>

@if($tab === 'kenaikan')
    @include('admin._kenaikan-mutasi-kenaikan')
@elseif($isAdmin && $tab === 'mutasi')
    @include('admin._kenaikan-mutasi-mutasi')
@endif

@include('admin._kenaikan-mutasi-riwayat')
@endsection