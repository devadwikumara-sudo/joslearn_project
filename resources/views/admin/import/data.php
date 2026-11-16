@extends('layouts.app')

@section('title', 'Import Data — JosBook')

@push('styles')
<style>
    .km-tabs{display:inline-flex;gap:4px;max-width:100%;margin-bottom:14px;padding:4px;overflow-x:auto;border:1px solid var(--ln);border-radius:12px;background:#fff}
    .km-tabs a{padding:8px 18px;border-radius:9px;color:var(--mu);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:background .15s,color .15s}
    .km-tabs a:hover{background:#f1f5fb;color:var(--p)}
    .km-tabs a.on{background:var(--p);color:#fff;box-shadow:0 2px 6px #0b224030}
    .km-tabs a:focus-visible{outline:2px solid var(--a);outline-offset:2px}
</style>
@endpush

@section('content')
<h2>Import Data</h2>
<p class="sub">Pilih jenis data, unggah file Excel, lalu periksa pratinjau sebelum menyimpan.</p>

<nav class="km-tabs" aria-label="Jenis impor">
    @foreach($availableTypes as $availableType => $label)
        <a
            href="{{ route($indexRoute, ['tab' => $availableType, 'tahun_ajaran_id' => $tahunAjaranTerpilihId]) }}"
            class="{{ $tab === $availableType ? 'on' : '' }}"
            @if($tab === $availableType) aria-current="page" @endif
        >Import {{ $label }}</a>
    @endforeach
</nav>

@if(! $isAdmin && in_array('nilai', $allowedTypes, true) && in_array('kehadiran', $allowedTypes, true))
    <div class="bar" style="margin-top:12px">
        <span class="sub">Pengisian nilai per siswa:</span>
        <a href="{{ route('guru.input-nilai-mapel') }}" class="btn o">Input Nilai Mapel</a>
    </div>
@endif

@include('admin.import.tabs.'.$tabView)

@if($batchDetail !== null)
    @include('admin.import._batch-detail', $batchDetail)
@endif
@endsection