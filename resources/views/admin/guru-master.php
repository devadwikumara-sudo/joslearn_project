@extends('layouts.app')

@section('title', 'Guru & Master — JosBook Admin')

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
<h2>Guru &amp; Master</h2>

@if(session('success'))
    <div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="note err">{{ session('error') }}</div>
@endif

@if($tab !== 'guru')
    <p class="sub">Daftar ini menjadi tabel <code>kelas</code> dan <code>mapel</code>. Kelas dan mapel baru langsung dipakai di seluruh aplikasi (filter, editor nilai, import, PDF). Hanya yang ditambahkan di sini yang bisa dihapus, dan hanya bila belum dipakai.</p>
@endif

<nav class="km-tabs" aria-label="Tab Guru dan Master">
    @foreach(['guru' => 'Guru', 'kelas' => 'Kelas', 'mapel' => 'Mapel'] as $tabName => $label)
        <a
            href="{{ route('admin.guru-master', ['tab' => $tabName]) }}"
            class="{{ $tab === $tabName ? 'on' : '' }}"
            @if($tab === $tabName) aria-current="page" @endif
        >{{ $label }}</a>
    @endforeach
</nav>

@if($tab === 'guru')
    @include('admin._guru-master-guru')
@elseif($tab === 'kelas')
    @include('admin._guru-master-kelas')
@elseif($tab === 'mapel')
    @include('admin._guru-master-mapel')
@endif
@endsection