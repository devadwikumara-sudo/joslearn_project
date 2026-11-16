@extends('layouts.app')

@section('title', 'Laporan & Statistik — JosBook Admin')

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
<h2>Laporan &amp; Statistik</h2>

<nav class="km-tabs" aria-label="Tab Laporan">
    <a
        href="{{ route('admin.laporan', ['tab' => 'laporan']) }}"
        class="{{ $tab === 'laporan' ? 'on' : '' }}"
        @if($tab === 'laporan') aria-current="page" @endif
    >Laporan</a>
    <a
        href="{{ route('admin.laporan', ['tab' => 'kualitas']) }}"
        class="{{ $tab === 'kualitas' ? 'on' : '' }}"
        @if($tab === 'kualitas') aria-current="page" @endif
    >Kualitas Data</a>
</nav>

@if($tab === 'laporan')
    @include('admin._laporan-rekap')
@elseif($tab === 'kualitas')
    @include('admin._laporan-kualitas')
@endif
@endsection