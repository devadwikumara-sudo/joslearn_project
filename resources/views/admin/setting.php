@extends('layouts.app')

@section('title', 'Setting — JosBook Admin')

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
<h2>Setting</h2>
<p class="sub">Kelola profil sekolah, tahun ajaran, cadangan, data terhapus, aktivitas, dan akun.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="note err">{{ session('error') }}</div>
@endif

@if($errors->any())
<div class="note err">
    <ul style="margin:0;padding-left:20px">
        @foreach($errors->all() as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
</div>
@endif

<nav class="km-tabs" aria-label="Pengaturan admin">
    @foreach($tabs as $tabName => $label)
        <a
            href="{{ route('admin.setting', ['tab' => $tabName]) }}"
            class="{{ $tab === $tabName ? 'on' : '' }}"
            @if($tab === $tabName) aria-current="page" @endif
        >{{ $label }}</a>
    @endforeach
</nav>

@include('admin.setting.tabs.'.$tab)
@endsection