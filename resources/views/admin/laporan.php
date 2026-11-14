@extends('layouts.app')

@section('title', 'Laporan & Statistik — JosBook Admin')

@section('content')
<h2>Laporan &amp; Statistik</h2>

<nav class="seg" aria-label="Tab Laporan">
    <a
        href="{{ route('admin.laporan', ['tab' => 'laporan']) }}"
        class="tab-btn {{ $tab === 'laporan' ? 'on' : '' }}"
        @if($tab === 'laporan') aria-current="page" @endif
    >Laporan</a>
    <a
        href="{{ route('admin.laporan', ['tab' => 'kualitas']) }}"
        class="tab-btn {{ $tab === 'kualitas' ? 'on' : '' }}"
        @if($tab === 'kualitas') aria-current="page" @endif
    >Kualitas Data</a>
</nav>

@if($tab === 'laporan')
    @include('admin._laporan-rekap')
@elseif($tab === 'kualitas')
    @include('admin._laporan-kualitas')
@endif
@endsection
