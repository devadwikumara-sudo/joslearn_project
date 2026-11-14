@extends('layouts.app')

@section('title', 'Guru & Master — JosBook Admin')

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

<nav class="seg" aria-label="Tab Guru dan Master">
    @foreach(['guru' => 'Guru', 'kelas' => 'Kelas', 'mapel' => 'Mapel'] as $tabName => $label)
        <a
            href="{{ route('admin.guru-master', ['tab' => $tabName]) }}"
            class="tab-btn {{ $tab === $tabName ? 'on' : '' }}"
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
