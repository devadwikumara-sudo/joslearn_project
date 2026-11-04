@extends('layouts.app')

@section('title', 'Import Data Guru — JosBook Admin')

@section('content')
<h2>Import Data Guru</h2>
<p class="sub">Unggah file Excel berisi data guru/tenaga pendidik. Kolom NIP, NUPTK, nama, mapel, dan kelas diampu dideteksi otomatis.</p>

{{-- Card 1: Unggah file & pengaturan --}}
<div class="card">
    <h3>1. Unggah file & pengaturan</h3>
    <div class="fl">
        <div>
            <label>Tahun ajaran</label>
            <input value="{{ $taAktif?->nama ?? '2024/2025' }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
        </div>
        <div>
            <label>Jika NIP sudah ada</label>
            <select name="duplikat" id="duplikat">
                <option value="skip">Lewati</option>
                <option value="timpa" selected>Timpa</option>
            </select>
        </div>
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'guru')">
        <button type="button" class="btn" onclick="downloadTemplate('guru')">↓ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nip</code>, <code>nuptk</code>, <code>nama</code>, <code>jk</code>, <code>tempat_lahir</code>, <code>tanggal_lahir</code>,
        <code>agama</code>, <code>golongan</code>, <code>mapel</code>, <code>no_hp</code>, <code>mulai_bertugas</code>, <code>status</code>, dsb.
    </p>
</div>

{{-- Container Preview Otomatis --}}
<div id="josbook-import-preview-area"></div>

{{-- Card 4: Jumlah Guru Terdaftar --}}
<div class="card" style="margin-top:14px">
    <h3>Guru terdaftar di sistem</h3>
    <p class="sub">Total <strong>{{ $totalGuru }}</strong> guru terdaftar pada tahun ajaran <strong>{{ $taAktif?->nama ?? '-' }}</strong>.</p>
    <div class="bar" style="margin-top:10px">
        <a href="{{ route('admin.data-guru') }}" class="btn o">Lihat di Data Guru</a>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/josbook-excel.js') }}"></script>
@endpush

