@extends('layouts.app')

@section('title', 'Import Ketidakhadiran - JosBook Guru')

@section('content')
<h2>Import Ketidakhadiran</h2>
<p class="sub">Unggah data ketidakhadiran / absensi siswa</p>

<div class="g g2">
    <div class="card">
        <h3>📤 Unggah File Ketidakhadiran</h3>
        <div class="note">
            Format: <b>.xlsx / .xls</b> — Kolom: <b>NISN, Nama, Hadir, Izin, Sakit, Alpa</b>
        </div>
        <form method="POST" action="{{ route('guru.import-ketidakhadiran.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;gap:12px;margin-bottom:14px">
                <div>
                    <label>Kelas</label>
                    <select name="kelas" style="width:100%" required>
                        <option value="">Pilih Kelas</option>
                        @foreach($kelasList ?? [] as $k)
                            <option>{{ $k }}</option>
                        @endforeach
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
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2023/2024</option>
                    </select>
                </div>
                <div>
                    <label>Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" style="width:100%" placeholder="Kosongkan jika kehadiran umum" value="{{ Auth::user()->mata_pelajaran ?? '' }}">
                </div>
            </div>
            <div style="border:2px dashed var(--ln);border-radius:12px;padding:24px;text-align:center;margin-bottom:14px">
                <div style="font-size:32px;margin-bottom:8px">📋</div>
                <input type="file" name="file" id="fileHadir" accept=".xlsx,.xls" style="display:none" required
                    onchange="document.getElementById('namaHadir').textContent='📄 '+this.files[0].name">
                <button type="button" class="btn o" onclick="document.getElementById('fileHadir').click()">Pilih File Excel</button>
                <div id="namaHadir" style="margin-top:8px;font-size:12px;color:var(--mu)"></div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="submit" class="btn">⬆ Import Ketidakhadiran</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>🕒 Riwayat Import</h3>
        @forelse($riwayat ?? [] as $r)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $r->kelas }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $r->bulan_label }} {{ $r->tahun_ajaran }}</div>
            </div>
            <span class="bd {{ $r->status === 'sukses' ? 'ok' : 'w' }}">{{ ucfirst($r->status) }}</span>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
@endsection

