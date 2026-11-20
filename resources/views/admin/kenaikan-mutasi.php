@extends('layouts.app')

@section('title', 'Kenaikan & Mutasi — JosBook')

@push('styles')
<style>
    .km-tabs{display:inline-flex;gap:4px;max-width:100%;margin-bottom:14px;padding:4px;overflow-x:auto;border:1px solid var(--ln);border-radius:12px;background:#fff}
    .km-tabs a{padding:8px 18px;border-radius:9px;color:var(--mu);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:background .15s,color .15s}
    .km-tabs a:hover{background:#f1f5fb;color:var(--p)}
    .km-tabs a.on{background:var(--p);color:#fff;box-shadow:0 2px 6px #0b224030}
    .km-tabs a:focus-visible{outline:2px solid var(--a);outline-offset:2px}
    .km-filter{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
    .km-filter select{min-width:170px;padding:8px 10px}
    .mn .target-status.target-status-hidden{display:none!important}
</style>
@endpush

@section('content')
<h2>Kenaikan &amp; Mutasi</h2>
<p class="sub">Tahun ajaran {{ $taAktif }} · proses kenaikan kelas dan catat perubahan siswa.</p>

<nav class="km-tabs" aria-label="Tab Kenaikan dan Mutasi">
    <a
        href="{{ route($routePrefix . '.kenaikan-mutasi', ['tab' => 'kenaikan', 'tahun_ajaran' => $taAktifId ?: null, 'kelas' => $kelasAsal]) }}"
        class="{{ $tab === 'kenaikan' ? 'on' : '' }}"
        @if($tab === 'kenaikan') aria-current="page" @endif
    >Kenaikan Kelas</a>
    @if($isAdmin)
        <a
            href="{{ route($routePrefix . '.kenaikan-mutasi', ['tab' => 'mutasi', 'tahun_ajaran' => $taAktifId ?: null, 'kelas' => $kelasAsal]) }}"
            class="{{ $tab === 'mutasi' ? 'on' : '' }}"
            @if($tab === 'mutasi') aria-current="page" @endif
        >Mutasi Siswa</a>
    @endif
</nav>

<form method="GET" action="{{ route($routePrefix . '.kenaikan-mutasi') }}" class="km-filter">
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
    <label for="km-filter-kelas" class="sub">Cari per kelas:</label>
    <select id="km-filter-kelas" name="kelas" aria-label="Cari per kelas" onchange="this.form.submit()">
        @if($isAdmin)
            <option value="*" {{ $kelasAsal === '*' ? 'selected' : '' }}>Semua kelas</option>
        @endif
        @foreach($kelasAsalList as $namaKelas)
            <option value="{{ $namaKelas }}" {{ $kelasAsal === $namaKelas ? 'selected' : '' }}>Kelas {{ $namaKelas }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn o">Terapkan</button></noscript>
</form>

@if($errors->any())
    <div class="note err" role="alert" style="margin-bottom:14px">
        <b>Periksa kembali data yang dikirim:</b>
        <ul style="margin:8px 0 0;padding-left:22px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($tab === 'kenaikan')
    @include('admin._kenaikan-mutasi-kenaikan')
    @include('admin._kenaikan-mutasi-riwayat')
@elseif($isAdmin && $tab === 'mutasi')
    @include('admin._kenaikan-mutasi-mutasi')
    @include('admin._kenaikan-mutasi-riwayat-mutasi')
@endif
@endsection