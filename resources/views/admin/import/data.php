@extends('layouts.app')

@section('title', 'Import Data — JosBook')

@section('content')
<h2>Import Data</h2>
<p class="sub">Pilih jenis data, unggah file Excel, lalu periksa pratinjau sebelum menyimpan.</p>

<nav class="bar" aria-label="Jenis impor">
    @foreach($availableTypes as $availableType => $label)
        <a
            href="{{ route($indexRoute, ['jenis' => $availableType, 'tahun_ajaran_id' => $tahunAjaranTerpilihId]) }}"
            class="btn {{ $type === $availableType ? 'a' : 'o' }}"
            @if($type === $availableType) aria-current="page" @endif
        >Import {{ $label }}</a>
    @endforeach
</nav>

<div class="card" style="margin-top:14px">
    <h3>Import {{ $typeLabel }}</h3>
    <div class="fl">
        @if(in_array($type, ['siswa', 'guru'], true))
            <div>
                <label for="tahun_ajaran">Tahun ajaran</label>
                <input value="{{ $taAktif }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
                <small class="sub">Sistem memakai tahun ajaran aktif jika tidak ditemukan di file.</small>
            </div>
        @endif

        @if($type === 'siswa')
            <div>
                <label for="mode">Mode</label>
                <select name="mode" id="mode">
                    <option value="kelas">Satu kelas</option>
                    <option value="semua" selected>Semua kelas (dari kolom kelas di Excel)</option>
                </select>
            </div>
            <div>
                <label for="kelas">Kelas (ketik baru = dibuat otomatis)</label>
                <input list="dlk" name="kelas" id="kelas" value="{{ $kelasList->first() ?? '' }}">
                <datalist id="dlk">
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas }}">
                    @endforeach
                </datalist>
            </div>
        @elseif(in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
            <div>
                <label for="kelas">Kelas</label>
                <select name="kelas" id="kelas">
                    <option value="*" selected>Semua kelas yang diizinkan</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas }}">{{ $kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="semester">Semester</label>
                <select name="semester" id="semester">
                    <option value="auto" selected>Deteksi dari kolom Excel</option>
                    <option value="1">Semester 1 / Ganjil (sesuai tingkat kelas)</option>
                    <option value="2">Semester 2 / Genap (sesuai tingkat kelas)</option>
                    <option value="local-ganjil">Ganjil (sesuai tingkat kelas)</option>
                    <option value="local-genap">Genap (sesuai tingkat kelas)</option>
                    <option value="3">Semester global 3 (XI Ganjil)</option>
                    <option value="4">Semester global 4 (XI Genap)</option>
                    <option value="5">Semester global 5 (XII Ganjil)</option>
                    <option value="6">Semester global 6 (XII Genap)</option>
                </select>
            </div>
        @endif

        @if(in_array($type, ['siswa', 'guru'], true))
            <div>
                <label for="duplikat">{{ $type === 'siswa' ? 'Jika NISN/NIS sudah ada' : 'Jika NIP sudah ada' }}</label>
                <select name="duplikat" id="duplikat">
                    <option value="skip" {{ $type === 'siswa' ? 'selected' : '' }}>Lewati</option>
                    @if($type === 'guru')
                        <option value="timpa" selected>Timpa</option>
                    @endif
                </select>
            </div>
        @endif
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, '{{ $typeJs }}')">
        <a href="{{ route($templateRoute, ['jenis' => $type]) }}" class="btn">⬇ Template Excel</a>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Data akan diperiksa lebih dahulu. Baris yang tidak valid atau tidak sesuai hak akses tidak disimpan.
    </p>
</div>

<div id="josbook-import-preview-area"></div>

@if($isAdmin && $type === 'siswa')
    <div class="card" style="margin-top:14px">
        <h3>Data hasil impor {{ $taAktif ?: 'Tahun ajaran belum diatur' }}</h3>
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
                    @forelse($riwayatImport as $row)
                        <tr>
                            <td><strong>{{ $row->kelas }}</strong></td>
                            <td>{{ $taAktif ?: 'Belum diatur' }}</td>
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
@elseif($isAdmin && $type === 'guru')
    <div class="card" style="margin-top:14px">
        <h3>Guru terdaftar di sistem</h3>
        <p class="sub">Total <strong>{{ $totalGuru }}</strong> guru terdaftar pada tahun ajaran <strong>{{ $taAktif ?: '-' }}</strong>.</p>
        <div class="bar" style="margin-top:10px">
            <a href="{{ route('admin.data-guru') }}" class="btn o">Lihat di Data Guru</a>
        </div>
    </div>
@elseif($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
    @include('admin.import._batch-list', [
        'type' => $type,
        'indexRoute' => $indexRoute,
        'batches' => $batches,
        'tahunAjaranList' => $tahunAjaranList,
        'tahunAjaranTerpilihId' => $tahunAjaranTerpilihId,
    ])
@endif
@endsection
