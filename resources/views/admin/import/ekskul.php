@extends('layouts.app')

@section('title', 'Import Ekskul - JosBook Admin')

@section('content')
<h2>Import Ekskul</h2>
<p class="sub">Unggah data kegiatan ekstrakurikuler siswa</p>

<div class="g g2">
    <div class="card">
        <h3>📤 Unggah File Ekskul</h3>
        <div class="note">
            Format file: <b>.xlsx / .xls</b> — Kolom wajib: <b>NISN, Nama Siswa, Nama Ekskul, Nilai, Keterangan</b>
        </div>
        <form method="POST" action="{{ route('admin.import.ekskul.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="fl" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2023/2024</option>
                    </select>
                </div>
                <div>
                    <label>Semester</label>
                    <select name="semester" style="width:100%">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
            </div>
            <div style="border:2px dashed var(--ln);border-radius:12px;padding:24px;text-align:center;margin-bottom:14px">
                <div style="font-size:32px;margin-bottom:8px">🏃</div>
                <p style="color:var(--mu);margin-bottom:12px">File Excel data ekskul</p>
                <input type="file" name="file" accept=".xlsx,.xls" id="fileEkskul" style="display:none" required onchange="this.nextElementSibling.textContent='📄 '+this.files[0].name">
                <button type="button" class="btn o" onclick="document.getElementById('fileEkskul').click()">Pilih File</button>
                <div style="font-size:12px;color:var(--mu);margin-top:6px"></div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="submit" class="btn">⬆ Import Ekskul</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>🕒 Riwayat Import Ekskul</h3>
        @forelse($riwayat ?? [] as $r)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $r->nama_file }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $r->tahun_ajaran }} · Sem {{ $r->semester }}</div>
            </div>
            <span class="bd {{ $r->status === 'sukses' ? 'ok' : 'w' }}">{{ ucfirst($r->status) }}</span>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
@endsection

