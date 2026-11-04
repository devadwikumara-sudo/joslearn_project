@extends('layouts.app')

@section('title', 'Import Nilai — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Import Nilai</h2>
        <p class="sub">Upload file Excel/CSV untuk mengimpor data nilai siswa ke dalam sistem.</p>
    </div>
</div>

<div class="g g2">
    {{-- Card 1: Upload --}}
    <div class="card">
        <h3>1. Upload File</h3>

        <form action="{{ route('admin.import.nilai.store') }}" method="POST" enctype="multipart/form-data">
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
                        @foreach($kelasList ?? ['X-1', 'XI-1', 'XII-1'] as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
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
                <a href="{{ route('admin.import.nilai.template') }}" class="btn o">⬇ Download Template</a>
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
            <b>nisn</b>, <b>kelas</b>, <b>agm</b>, <b>pnc</b>, <b>ind</b>, <b>mtk</b>, <b>ipa</b>, <b>ips</b>, <b>big</b>, <b>pjk</b>, <b>sej</b>, <b>sbd</b>, <b>inf</b>
        </div>
        <div class="note" style="margin-top:12px">
            ℹ️ Nilai berupa angka 0–100. Kolom yang kosong akan dianggap belum diajarkan (X) dan tidak dihitung ke rata-rata.
        </div>
    </div>
</div>

{{-- Card 3: Riwayat / Data Tersimpan --}}
<div class="card" style="margin-top:14px">
    <h3>Data Nilai Tersimpan</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Semester</th>
                    <th>Tahun Ajaran</th>
                    <th>Jumlah Baris</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>X-1</td>
                    <td>Genap</td>
                    <td>2024/2025</td>
                    <td>0 baris</td>
                    <td><button class="btn o" style="padding:3px 8px;font-size:11px">Lihat</button></td>
                </tr>
                <tr>
                    <td>XI-1</td>
                    <td>Genap</td>
                    <td>2024/2025</td>
                    <td>0 baris</td>
                    <td><button class="btn o" style="padding:3px 8px;font-size:11px">Lihat</button></td>
                </tr>
                <tr>
                    <td>XII-1</td>
                    <td>Genap</td>
                    <td>2024/2025</td>
                    <td>0 baris</td>
                    <td><button class="btn o" style="padding:3px 8px;font-size:11px">Lihat</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
