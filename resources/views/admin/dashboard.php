@extends('layouts.app')

@section('title', 'Dashboard Admin - JosBook')

@section('content')
<h2>Dashboard</h2>
<p class="sub">Selamat datang, <b>{{ Auth::user()->nama ?? Auth::user()->username }}</b> · Ringkasan data sekolah</p>

{{-- Stat Cards --}}
<div class="g g4" style="margin-bottom:14px">
    <div class="card st">
        <span>Total Siswa</span>
        <b>{{ $totalSiswa ?? '—' }}</b>
    </div>
    <div class="card st">
        <span>Total Guru</span>
        <b>{{ $totalGuru ?? '—' }}</b>
    </div>
    <div class="card st">
        <span>Kelas Aktif</span>
        <b>{{ $totalKelas ?? '—' }}</b>
    </div>
    <div class="card st">
        <span>Import Bulan Ini</span>
        <b>{{ $totalImport ?? '—' }}</b>
    </div>
</div>

<div class="g g2">
    {{-- Grafik Siswa per Kelas --}}
    <div class="card">
        <h3>Distribusi Siswa per Kelas</h3>
        <div class="bars" style="margin-top:10px">
            @forelse($distribusiKelas ?? [] as $kelas => $jumlah)
                <i style="height:{{ min(100, ($jumlah / max(1, $maxKelas ?? 40)) * 100) }}%">
                    <span style="display:block;margin-top:2px">{{ $jumlah }}</span>
                    <span style="font-size:9px">{{ $kelas }}</span>
                </i>
            @empty
                <div style="color:var(--mu);font-size:12px">Belum ada data kelas</div>
            @endforelse
        </div>
    </div>

    {{-- Aktivitas Terbaru --}}
    <div class="card">
        <h3>Aktivitas Terbaru</h3>
        @forelse($aktivitas ?? [] as $log)
            <div class="row">
                <div>
                    <b style="font-size:12px">{{ $log->keterangan }}</b>
                    <div style="font-size:11px;color:var(--mu)">{{ $log->pengguna->nama ?? '-' }}</div>
                </div>
                <span style="font-size:11px;color:var(--mu)">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
            </div>
        @empty
            <p style="color:var(--mu);font-size:12px">Belum ada aktivitas tercatat.</p>
        @endforelse
    </div>
</div>

{{-- Siswa bermasalah / kualitas data --}}
<div class="card">
    <div class="bar">
        <h3 style="margin:0">⚠️ Perhatian</h3>
    </div>
    <div class="g g2">
        <div style="padding:10px;border:1px solid var(--ln);border-radius:10px">
            <div style="font-size:11px;color:var(--mu)">Data Siswa Tidak Lengkap</div>
            <b style="font-size:22px;color:#c4412f">{{ $siswaKurang ?? '0' }}</b>
        </div>
        <div style="padding:10px;border:1px solid var(--ln);border-radius:10px">
            <div style="font-size:11px;color:var(--mu)">Import Menunggu Validasi</div>
            <b style="font-size:22px;color:#946200">{{ $importPending ?? '0' }}</b>
        </div>
    </div>
</div>
@endsection
