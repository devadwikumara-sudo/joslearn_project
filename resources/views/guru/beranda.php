@extends('layouts.app')

@section('title', 'Beranda Guru - JosBook')

@section('content')
<h2>Beranda</h2>
<p class="sub">Selamat datang, <b>{{ Auth::user()->nama ?? Auth::user()->username }}</b> · Ringkasan aktivitas mengajar</p>

{{-- Stat Cards --}}
<div class="g g4" style="margin-bottom:14px">
    <div class="card st">
        <span>Kelas Diampu</span>
        <b>{{ $kelasDiampu ?? '—' }}</b>
    </div>
    <div class="card st">
        <span>Total Siswa</span>
        <b>{{ $totalSiswa ?? '—' }}</b>
    </div>
    <div class="card st">
        <span>Nilai Belum Input</span>
        <b style="color:{{ ($nilaiPending ?? 0) > 0 ? '#946200' : 'var(--ok)' }}">{{ $nilaiPending ?? '0' }}</b>
    </div>
    <div class="card st">
        <span>Import Bulan Ini</span>
        <b>{{ $importBulanIni ?? '0' }}</b>
    </div>
</div>

<div class="g g2">
    {{-- Kelas yang Diampu --}}
    <div class="card">
        <h3>Kelas yang Diampu</h3>
        @forelse($kelasList ?? [] as $kls)
        <div class="row">
            <div>
                <b>{{ $kls->nama_kelas }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $kls->mata_pelajaran }} · {{ $kls->jumlah_siswa }} siswa</div>
            </div>
            <a href="{{ route('guru.data-siswa') }}?kelas={{ urlencode($kls->nama_kelas) }}" class="btn o" style="padding:4px 10px;font-size:11px">Lihat</a>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Data kelas belum tersedia.</p>
        @endforelse
    </div>

    {{-- Tugas dan Pengingat --}}
    <div class="card">
        <h3>Pengingat</h3>
        @if(($nilaiPending ?? 0) > 0)
        <div class="note">
            Ada <b>{{ $nilaiPending }} siswa</b> yang nilainya belum diinput.
            <a href="{{ route('guru.input-nilai-mapel') }}" style="color:var(--p);font-weight:700;display:block;margin-top:4px">→ Input Nilai Sekarang</a>
        </div>
        @endif

        @forelse($pengingat ?? [] as $p)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $p->judul }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $p->deskripsi }}</div>
            </div>
            <span style="font-size:11px;color:var(--mu)">{{ \Carbon\Carbon::parse($p->tanggal)->format('d M') }}</span>
        </div>
        @empty
        @if(($nilaiPending ?? 0) === 0)
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Tidak ada pengingat saat ini. 🎉</p>
        @endif
        @endforelse
    </div>
</div>

{{-- Akses Cepat --}}
<div class="card">
    <h3>Akses Cepat</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('guru.import-nilai') }}" class="btn">📊 Import Nilai</a>
        <a href="{{ route('guru.import-ekskul') }}" class="btn o">🏃 Import Ekskul</a>
        <a href="{{ route('guru.import-ketidakhadiran') }}" class="btn o">📋 Import Ketidakhadiran</a>
        <a href="{{ route('guru.input-nilai-mapel') }}" class="btn o">✏️ Input Nilai Mapel</a>
        <a href="{{ route('guru.data-siswa') }}" class="btn o">👨‍🎓 Lihat Data Siswa</a>
    </div>
</div>
@endsection

