@extends('layouts.app')

@section('title', 'Laporan & Statistik — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Laporan &amp; Statistik</h2>
        <p class="sub">Rekap data akademik dan kehadiran per kelas</p>
    </div>
</div>

<div class="bar">
    <form method="GET" action="{{ route('admin.laporan') }}" style="display:contents">
        <select name="tahun_ajaran" onchange="this.form.submit()">
            <option value="">Semua Tahun Ajaran</option>
            <option value="2024/2025" selected>2024/2025</option>
            <option value="2023/2024">2023/2024</option>
        </select>
        <select name="semester" onchange="this.form.submit()">
            <option value="">Semua Semester</option>
            <option value="1" {{ request('semester')=='1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
            <option value="2" {{ request('semester')=='2' || !request('semester') ? 'selected' : '' }}>Semester 2 (Genap)</option>
        </select>
        <select name="kelas" onchange="this.form.submit()">
            <option value="">Semua Kelas</option>
            @foreach($kelasList ?? ['X-1', 'XI-1', 'XII-1'] as $kls)
            <option value="{{ $kls }}" {{ request('kelas') == $kls ? 'selected' : '' }}>{{ $kls }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn">Filter</button>
    </form>
    <a href="{{ route('admin.laporan.cetak') }}" class="btn a">🖨 Cetak PDF</a>
</div>

{{-- Tabel Rekap --}}
<div class="card wide">
    <h3 style="margin:0 0 1rem;font-size:1rem;color:var(--p)">Rekap Akademik per Kelas</h3>
    <table class="tw">
        <thead>
            <tr>
                <th>Kelas</th>
                <th>Jumlah Siswa</th>
                <th>Rata-rata Nilai</th>
                <th>Ketidakhadiran — Sakit</th>
                <th>Ketidakhadiran — Izin</th>
                <th>Ketidakhadiran — Alpa</th>
                <th>Total Absensi</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['X-1', 'XI-1', 'XII-1'] as $k)
            <tr>
                <td><strong>{{ $k }}</strong></td>
                <td>0 siswa</td>
                <td>-</td>
                <td>0 hari</td>
                <td>0 hari</td>
                <td>0 hari</td>
                <td>0 hari</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Grafik / Statistik --}}
<div class="card" style="margin-top:14px">
    <h3>Grafik Distribusi Capaian</h3>
    <p class="sub">Rata-rata nilai per tingkatan kelas</p>
    <div class="bars" style="margin-top:20px;height:120px">
        <div style="flex:1;display:flex;flex-direction:column;align-items:center">
            <div style="flex:1;width:100%;background:var(--p);border-radius:6px 6px 0 0;min-height:6px"></div>
            <small style="margin-top:6px;font-weight:600">Kelas X</small>
        </div>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center">
            <div style="flex:1;width:100%;background:var(--a);border-radius:6px 6px 0 0;min-height:6px"></div>
            <small style="margin-top:6px;font-weight:600">Kelas XI</small>
        </div>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center">
            <div style="flex:1;width:100%;background:#0b3c83;border-radius:6px 6px 0 0;min-height:6px"></div>
            <small style="margin-top:6px;font-weight:600">Kelas XII</small>
        </div>
    </div>
</div>
@endsection
