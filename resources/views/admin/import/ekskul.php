@extends('layouts.app')

@section('title', 'Import Ekskul — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Import Ekskul</h2>
        <p class="sub">Upload file Excel/CSV untuk mengimpor data ekstrakurikuler siswa.</p>
    </div>
</div>

<div class="g g2">
    {{-- Card 1: Upload --}}
    <div class="card">
        <h3>1. Upload File</h3>

        <form action="{{ route('admin.import.ekskul.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="fl">
                <div>
                    <label>Mode</label>
                    <select name="mode" id="mode">
                        <option value="satu_kelas">Satu Kelas</option>
                        <option value="semua_kelas">Semua Kelas</option>
                    </select>
                </div>
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran">
                        <option value="2024/2025" selected>2024/2025</option>
                        <option value="2023/2024">2023/2024</option>
                    </select>
                </div>
                <div>
                    <label>Kelas</label>
                    <select name="kelas">
                        <option value="X-1">X-1</option>
                        <option value="XI-1">XI-1</option>
                        <option value="XII-1">XII-1</option>
                    </select>
                </div>
            </div>

            <div class="fl" style="margin-top:10px">
                <div>
                    <label>Semester</label>
                    <select name="semester">
                        <option value="Ganjil">Semester 1 (Ganjil)</option>
                        <option value="Genap" selected>Semester 2 (Genap)</option>
                    </select>
                </div>
                <div>
                    <label>Jika Duplikat</label>
                    <select name="duplikat">
                        <option value="skip">Lewati</option>
                        <option value="overwrite">Timpa</option>
                    </select>
                </div>
            </div>

            <div style="margin: 1rem 0;">
                <label>File Excel / CSV</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="bar">
                <a href="{{ route('admin.import.ekskul.template') }}" class="btn o">⬇ Download Template</a>
                <span class="sp"></span>
                <button type="submit" class="btn a">⬆ Upload &amp; Validasi</button>
            </div>
        </form>
    </div>

    {{-- Card 2: Petunjuk Format --}}
    <div class="card">
        <h3>2. Format Kolom Excel</h3>
        <p class="sub">Pastikan file spreadsheet memiliki header kolom berikut:</p>
        <div style="background:#f4f7fb;padding:12px;border-radius:10px;font-family:monospace;font-size:12px;line-height:1.8">
            <b>nisn</b>, <b>kelas</b>, <b>ekskul</b>, <b>predikat</b>
        </div>
        <div class="note" style="margin-top:12px">
            ℹ️ Predikat diisi dengan Sangat Baik (A), Baik (B), Cukup (C), atau Kurang (D).
        </div>
    </div>
</div>
@endsection
