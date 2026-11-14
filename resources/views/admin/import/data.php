@extends('layouts.app')

@section('title', 'Import Data — JosBook')

@section('content')
<h2>Import Data</h2>
<p class="sub">Pilih jenis data, unggah file Excel, lalu periksa pratinjau sebelum menyimpan.</p>

<nav class="bar" aria-label="Jenis impor">
    @foreach($availableTypes as $availableType => $label)
        <a
            href="{{ route($indexRoute, ['tab' => $availableType, 'tahun_ajaran_id' => $tahunAjaranTerpilihId]) }}"
            class="btn {{ $tab === $availableType ? 'a' : 'o' }}"
            @if($tab === $availableType) aria-current="page" @endif
        >Import {{ $label }}</a>
    @endforeach
</nav>

@include('admin.import.tabs.'.$tabView)

@if($batchDetail !== null)
    @include('admin.import._batch-detail', $batchDetail)
@endif
@endsection
