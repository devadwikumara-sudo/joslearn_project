@extends('layouts.app')

@section('title', 'Import Kehadiran - JosBook Admin')

@section('content')
<h2>Import Kehadiran</h2>
<p class="sub">Unggah data kehadiran / absensi siswa</p>

<div class="g g2">
    <div class="card">
        <h3>📤 Unggah File Kehadiran</h3>
        <div class="note">
            Format file: <b>.xlsx / .xls</b> — Kolom wajib: <b>NISN, Nama, Hadir, Izin, Sakit, Alpa, Bulan</b>
        </div>
        <form method="POST" action="{{ route('admin.import.kehadiran.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="fl" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2023/2024</option>
                    </select>
                </div>
                <div>
                    <label>Bulan</label>
                    <select name="bulan" style="width:100%">
                        <option value="7">Juli</option><option value="8">Agustus</option>
                        <option value="9">September</option><option value="10">Oktober</option>
                        <option value="11">November</option><option value="12">Desember</option>
                        <option value="1">Januari</option><option value="2">Februari</option>
                        <option value="3">Maret</option><option value="4">April</option>
                        <option value="5">Mei</option><option value="6">Juni</option>
                    </select>
                </div>
                <div>
                    <label>Kelas</label>
                    <select name="kelas" style="width:100%">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList ?? [] as $k)
                            <option>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="border:2px dashed var(--ln);border-radius:12px;padding:24px;text-align:center;margin-bottom:14px">
                <div style="font-size:32px;margin-bottom:8px">📋</div>
                <p style="color:var(--mu);margin-bottom:12px">File Excel data kehadiran</p>
                <input type="file" name="file" accept=".xlsx,.xls" id="fileHadir" style="display:none" required onchange="this.nextElementSibling.textContent='📄 '+this.files[0].name">
                <button type="button" class="btn o" onclick="document.getElementById('fileHadir').click()">Pilih File</button>
                <div style="font-size:12px;color:var(--mu);margin-top:6px"></div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="submit" class="btn">⬆ Import Kehadiran</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>🕒 Riwayat Import Kehadiran</h3>
        @forelse($riwayat ?? [] as $r)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $r->nama_file }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $r->kelas }} · {{ $r->bulan_label }} {{ $r->tahun_ajaran }}</div>
            </div>
            <span class="bd {{ $r->status === 'sukses' ? 'ok' : 'w' }}">{{ ucfirst($r->status) }}</span>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
@endsection

