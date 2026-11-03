@extends('layouts.app')

@section('title', 'Cek Kualitas Data - JosBook Admin')

@section('content')
<h2>Cek Kualitas Data</h2>
<p class="sub">Temukan dan perbaiki data siswa yang tidak lengkap atau bermasalah</p>

{{-- Summary cards --}}
<div class="g g4" style="margin-bottom:14px">
    <div class="card st">
        <span>Data Lengkap</span>
        <b style="color:var(--ok)">{{ $dataLengkap ?? 0 }}</b>
    </div>
    <div class="card st">
        <span>Tidak Lengkap</span>
        <b style="color:var(--er)">{{ $tidakLengkap ?? 0 }}</b>
    </div>
    <div class="card st">
        <span>Foto Belum Ada</span>
        <b style="color:#946200">{{ $belumFoto ?? 0 }}</b>
    </div>
    <div class="card st">
        <span>NISN Duplikat</span>
        <b style="color:var(--er)">{{ $nisnDuplikat ?? 0 }}</b>
    </div>
</div>

<div class="card">
    <div class="bar">
        <h3 style="margin:0">⚠️ Daftar Masalah Data</h3>
        <span class="sp"></span>
        <button class="btn" onclick="window.print()">🖨️ Cetak Laporan</button>
    </div>

    @forelse($masalah ?? [] as $m)
    <div style="padding:12px;border:1px solid #efc6bf;border-radius:10px;margin-bottom:8px;background:#fdf5f5">
        <div style="display:flex;justify-content:space-between;align-items:start">
            <div>
                <b>{{ $m->nama_lengkap }}</b>
                <span style="font-size:11px;color:var(--mu);margin-left:8px">{{ $m->nisn }} · {{ $m->kelas }}</span>
            </div>
            <a href="/admin/data-siswa/{{ $m->id }}" class="btn" style="padding:4px 10px;font-size:11px">Lengkapi</a>
        </div>
        <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
            @foreach($m->masalahList ?? [] as $item)
                <span class="bd e">{{ $item }}</span>
            @endforeach
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:var(--ok)">
        <div style="font-size:40px">✅</div>
        <b>Semua data siswa sudah lengkap!</b>
        <p style="color:var(--mu);font-size:12px;margin-top:6px">Tidak ada masalah kualitas data yang ditemukan.</p>
    </div>
    @endforelse
</div>
@endsection

