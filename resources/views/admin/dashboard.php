@extends('layouts.app')

@section('title', 'Dashboard Admin — JosBook')

@section('content')
{{-- Hero Banner --}}
<div class="hero2">
    <div>
        <span class="pl2">📊 BUKU INDUK DIGITAL</span>
        <h1>Dashboard Admin</h1>
        <p>Tahun Ajaran 2024/2025 · SMA Negeri 1 Rejoso Nganjuk</p>
        <div class="chs">
            <span>Semester Genap</span>
            <span>{{ now()->format('d M Y') }}</span>
        </div>
    </div>
    <div class="dt">
        <small>Total Siswa Aktif</small>
        <b style="font-size:28px">0</b>
        <small style="margin-top:4px">tahun ini</small>
    </div>
</div>

{{-- 4 Stat Cards --}}
<div class="g g4" style="margin-bottom:14px">
    <div class="card st">
        <span>Total Siswa</span>
        <b>0</b>
    </div>
    <div class="card st">
        <span>Total Guru</span>
        <b>0</b>
    </div>
    <div class="card st">
        <span>Kelas Aktif</span>
        <b>0</b>
    </div>
    <div class="card st">
        <span>Import Bulan Ini</span>
        <b>0</b>
    </div>
</div>

{{-- Row 2: Angkatan + Alumni --}}
<div class="g g2" style="margin-bottom:14px">
    <div class="card">
        <h3>Siswa per Angkatan</h3>
        <div class="angs">
            <div class="ang">
                <i>X</i>
                <small>Kelas X</small>
                <b>0 Siswa</b>
            </div>
            <div class="ang">
                <i>XI</i>
                <small>Kelas XI</small>
                <b>0 Siswa</b>
            </div>
            <div class="ang">
                <i>XII</i>
                <small>Kelas XII</small>
                <b>0 Siswa</b>
            </div>
        </div>
    </div>
    <div class="card">
        <h3>Alumni per Tahun Lulus</h3>
        <p class="sub" style="color:var(--mu)">Belum ada data alumni.</p>
        <h3 style="margin-top:14px">Aktivitas Terbaru</h3>
        <p class="sub" style="color:var(--mu)">Belum ada aktivitas.</p>
    </div>
</div>

{{-- Row 3: Komposisi Kelas + Kelengkapan Data --}}
<div class="g g2" style="margin-bottom:14px">
    <div class="card">
        <h3>Komposisi per Kelas</h3>
        <div class="tw">
            <table>
                <tr>
                    <th>Kelas</th>
                    <th>L</th>
                    <th>P</th>
                    <th>Total</th>
                    <th>Kelengkapan</th>
                </tr>
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--mu);padding:26px">
                        Belum ada data siswa
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="card">
        <h3>Perlu Dilengkapi</h3>
        <p class="sub" style="color:var(--mu)">Semua data siswa aktif sudah lengkap 🎉</p>
    </div>
</div>

{{-- Row 4: Distribusi Siswa (bar chart) --}}
<div class="card" style="margin-bottom:0">
    <h3>Distribusi Siswa per Kelas</h3>
    <div class="bars" style="margin-top:14px">
        <div style="display:flex;flex-direction:column;align-items:center;flex:1">
            <div style="flex:1;width:100%;background:var(--a);border-radius:4px 4px 0 0;min-height:4px"></div>
            <small style="margin-top:4px;color:var(--mu)">X-1</small>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;flex:1">
            <div style="flex:1;width:100%;background:var(--a);border-radius:4px 4px 0 0;min-height:4px"></div>
            <small style="margin-top:4px;color:var(--mu)">XI-1</small>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;flex:1">
            <div style="flex:1;width:100%;background:var(--a);border-radius:4px 4px 0 0;min-height:4px"></div>
            <small style="margin-top:4px;color:var(--mu)">XII-1</small>
        </div>
    </div>
</div>
@endsection
