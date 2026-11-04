@extends('layouts.app')

@section('title', 'Import Nilai — JosBook Admin')

@section('content')
<h2>Import Nilai</h2>
<p class="sub">Unggah file Excel dari sekolah. Jenis data, kolom, kelas, dan mata pelajaran dideteksi otomatis; yang belum ada di Master dibuat sendiri.</p>

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
            <input value="{{ $taAktif ?? '2024/2025' }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
        </div>
        <div>
            <label>Kelas (ketik baru = dibuat otomatis)</label>
            <input list="dlk" name="kelas" id="kelas" value="{{ $kelasList[0] ?? 'X-1' }}">
            <datalist id="dlk">
                @foreach($kelasList ?? [] as $k)
                    <option value="{{ $k }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label>Semester</label>
            <select name="semester" id="semester">
                <option value="Ganjil">Semester 1 (Ganjil)</option>
                <option value="Genap" selected>Semester 2 (Genap)</option>
            </select>
        </div>
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'nilai')">
        <button type="button" class="btn" onclick="loadDemoNilai()">Pakai data contoh</button>
        <button type="button" class="btn" onclick="downloadTemplateNilai()">⬇ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>kelas</code>, <code>sem</code>, dan kode/nama mapel (<code>mtk</code>, <code>ind</code>, <code>big</code>, <code>pjk</code>, <code>pnc</code>, <code>agm</code>, <code>ipa</code>, <code>ips</code>, <code>inf</code>, <code>sbd</code>, <code>sej</code>, dsb). Format tabel lebar (satu kolom per mapel) maupun panjang (kolom mapel + kolom nilai).
    </p>
</div>

{{-- Container Preview Otomatis (Card 2 & 3: Deteksi + Pratinjau + Eksekusi Batch) --}}
<div id="josbook-import-preview-area"></div>

{{-- Card 4: Data Hasil Impor --}}
<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $taAktif ?? '2024/2025' }}</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Semester</th>
                    <th>Baris Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $row->semester }}</td>
                    <td><b>{{ $row->baris }}</b> nilai</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data nilai diimpor pada tahun ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function downloadTemplateNilai() {
    if (!window.XLSX) {
        alert('Library SheetJS belum selesai termuat.');
        return;
    }
    var cols = ['nisn', 'nama', 'kelas', 'sem', 'agm', 'pnc', 'ind', 'mtk', 'big', 'pjk', 'sbd', 'inf', 'sej'];
    var sample = [
        ['0081234561', 'Ahmad Faiz', 'X-1', 'Genap', 85, 88, 90, 82, 80, 85, 84, 88, 86],
        ['0081234562', 'Bunga Citra', 'X-1', 'Genap', 90, 86, 92, 88, 85, 80, 88, 90, 87]
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols, ...sample]), 'Nilai');
    XLSX.writeFile(w, 'template-nilai.xlsx');
}

function loadDemoNilai() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: 'Genap', mtk: 85, ind: 88, big: 82, pnc: 90, pjk: 80 },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: 'Genap', mtk: 90, ind: 92, big: 85, pnc: 88, pjk: 84 },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: 'Genap', mtk: 78, ind: 85, big: 80, pnc: 82, pjk: 86 }
    ];

    var ws = XLSX.utils.json_to_sheet(dummy);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Nilai');
    var wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    var blob = new Blob([wbout], { type: 'application/octet-stream' });
    var file = new File([blob], 'demo_nilai.xlsx');

    smartFile({ target: { files: [file] } }, 'nilai');
}
</script>
@endpush
@endsection
