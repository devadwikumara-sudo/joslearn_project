@extends('layouts.app')

@section('title', 'Import Data Siswa — JosBook Admin')

@section('content')
<h2>Import Data Siswa</h2>
<p class="sub">Unggah file Excel dari sekolah. Format dan tatanan tabel dideteksi otomatis secara fleksibel; sheet PesertaDidik &amp; Kontrol Induk, maupun format tabel kelas apa pun terbaca langsung.</p>

{{-- Card 1: Unggah file & pengaturan (Sesuai Prototype impPg) --}}
<div class="card">
    <h3>1. Unggah file &amp; pengaturan</h3>
    <div class="fl">
        <div>
            <label>Mode</label>
            <select name="mode" id="mode">
                <option value="kelas">Satu kelas</option>
                <option value="semua" selected>Semua kelas (dari kolom "kelas" di Excel)</option>
            </select>
        </div>
        <div>
            <label>Tahun ajaran</label>
            <input value="{{ $taAktif ?? '' }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
            <small class="sub">Tahun ajaran dari Excel akan dipakai otomatis jika terdeteksi; jika tidak, sistem memakai tahun ajaran aktif.</small>
        </div>
        <div>
            <label>Kelas (ketik baru = dibuat otomatis)</label>
            <input list="dlk" name="kelas" id="kelas" value="{{ $kelasList[0] ?? '' }}">
            <datalist id="dlk">
                @foreach($kelasList ?? [] as $k)
                    <option value="{{ $k }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label>Jika NISN sudah ada</label>
            <select name="duplikat" id="duplikat">
                <option value="skip" selected>Lewati</option>
            </select>
        </div>
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'siswa')">
        <a href="{{ route('admin.import.siswa.template') }}" class="btn">⬇ Template Excel</a>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Siswa duplikat berdasarkan NISN atau NIS dan baris tidak valid akan dilewati dengan laporan nomor baris dan alasannya.
    </p>
</div>

{{-- Container Preview Otomatis (Card 2 & 3: Deteksi + Pratinjau + Eksekusi Batch) --}}
<div id="josbook-import-preview-area"></div>

{{-- Card 4: Data Hasil Impor --}}
<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $taAktif ?? 'Tahun ajaran belum diatur' }}</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Tahun Ajaran</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $taAktif ?? 'Belum diatur' }}</td>
                    <td><b>{{ $row->baris }}</b> baris</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data siswa terdaftar pada tahun ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
