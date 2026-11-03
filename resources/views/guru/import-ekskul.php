@extends('layouts.app')

@section('title', 'Import Ekskul - JosBook Guru')

@section('content')
<h2>Import Ekskul</h2>
<p class="sub">Unggah data kegiatan ekstrakurikuler siswa</p>

<div class="g g2">
    <div class="card">
        <h3>📤 Unggah File Ekskul</h3>
        <div class="note">
            Format: <b>.xlsx / .xls</b> — Kolom: <b>NISN, Nama Siswa, Nama Ekskul, Nilai/Predikat, Keterangan</b>
        </div>
        <form method="POST" action="{{ route('guru.import-ekskul.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;gap:12px;margin-bottom:14px">
                <div>
                    <label>Nama Ekskul</label>
                    <input type="text" name="nama_ekskul" style="width:100%" required placeholder="Pramuka / Basket / dll">
                </div>
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
                    <label>Semester</label>
                    <select name="semester" style="width:100%">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2023/2024</option>
                    </select>
                </div>
            </div>
            <div style="border:2px dashed var(--ln);border-radius:12px;padding:24px;text-align:center;margin-bottom:14px">
                <div style="font-size:32px;margin-bottom:8px">🏃</div>
                <input type="file" name="file" id="fileEkskul" accept=".xlsx,.xls" style="display:none" required
                    onchange="document.getElementById('namaEkskul').textContent='📄 '+this.files[0].name">
                <button type="button" class="btn o" onclick="document.getElementById('fileEkskul').click()">Pilih File Excel</button>
                <div id="namaEkskul" style="margin-top:8px;font-size:12px;color:var(--mu)"></div>
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
                <b style="font-size:12px">{{ $r->nama_ekskul }}</b>
                <div style="font-size:11px;color:var(--mu)">{{ $r->kelas }} · Sem {{ $r->semester }}</div>
            </div>
            <span class="bd {{ $r->status === 'sukses' ? 'ok' : 'w' }}">{{ ucfirst($r->status) }}</span>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada riwayat.</p>
        @endforelse
    </div>
</div>
@endsection

