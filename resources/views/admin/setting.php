@extends('layouts.app')

@section('title', 'Setting — JosBook Admin')

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

<nav class="bar" aria-label="Pengaturan admin">
    @foreach($tabs as $tabName => $label)
        <a
            href="{{ route('admin.setting', ['tab' => $tabName]) }}"
            class="btn {{ $tab === $tabName ? 'a' : 'o' }}"
            @if($tab === $tabName) aria-current="page" @endif
        >{{ $label }}</a>
    @endforeach
</nav>

@include('admin.setting.tabs.'.$tab)
@endsection
