@extends('layouts.app')

@section('title', 'Import Ekstrakurikuler — JosBook Admin')

@section('content')
<h2>Import Ekstrakurikuler</h2>
<p class="sub">Unggah file Excel dari berbagai format semester. Pilih kelas dan semester; sistem mencocokkan tahun ajaran dari riwayat kelas siswa. Nilai semester 1–2 di dalam kelas atau label Ganjil/Genap dikenali untuk kelas X, XI, dan XII.</p>

{{-- Card 1: Unggah file & pengaturan (Sesuai Prototype impPg) --}}
<div class="card">
    <h3>1. Unggah file &amp; pengaturan</h3>
    <div class="fl">
        <div>
            <label>Kelas</label>
            <select name="kelas" id="kelas">
                <option value="*" selected>Semua kelas (gunakan kolom kelas di Excel)</option>
                @foreach($kelasList ?? [] as $k)
                    <option value="{{ $k }}">{{ $k }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Semester</label>
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
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'ekskul')">
        <button type="button" class="btn" onclick="loadDemoEkskul()">Pakai data contoh</button>
<<<<<<< HEAD
        <a href="{{ route('admin.import.ekskul.template') }}" class="btn">⬇ Template Excel</a>
=======
        <button type="button" class="btn" onclick="downloadTemplateEkskul()">⬇ Template Excel</button>
>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>ekskul</code> (atau nama kegiatan), <code>predikat</code> (Sangat Baik, Baik, A, B).
    </p>
</div>

{{-- Container Preview Otomatis (Card 2 & 3: Deteksi + Pratinjau + Eksekusi Batch) --}}
<div id="josbook-import-preview-area"></div>

{{-- Card 4: Data Hasil Impor --}}
<div class="card" style="margin-top:14px">
    <h3>Data hasil impor seluruh tahun ajaran</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Tahun ajaran</th>
                    <th>Semester</th>
                    <th>Baris Ekskul</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $row->tahun_ajaran }}</td>
                    <td>{{ $row->semester_number ? 'Semester '.$row->semester_number : $row->semester }}</td>
                    <td><b>{{ $row->baris }}</b> kegiatan</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data ekstrakurikuler yang diimpor.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
<<<<<<< HEAD
=======
function downloadTemplateEkskul() {
    if (!window.XLSX) {
        alert('Library SheetJS belum selesai termuat.');
        return;
    }
    var cols = ['nisn', 'nama', 'kelas', 'sem', 'ekskul', 'predikat'];
    var sample = [
        ['0081234561', 'Ahmad Faiz', 'X-1', '2', 'Pramuka', 'Sangat Baik'],
        ['0081234562', 'Bunga Citra', 'X-1', '2', 'PMR', 'Baik']
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols, ...sample]), 'Ekskul');
    XLSX.writeFile(w, 'template-ekskul.xlsx');
}

>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
function loadDemoEkskul() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', ekskul: 'Pramuka Wajib', predikat: 'Sangat Baik' },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', ekskul: 'PMR', predikat: 'Baik' },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', ekskul: 'Futsal', predikat: 'Sangat Baik' }
    ];

    var ws = XLSX.utils.json_to_sheet(dummy);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Ekskul');
    var wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    var blob = new Blob([wbout], { type: 'application/octet-stream' });
    var file = new File([blob], 'demo_ekskul.xlsx');

    smartFile({ target: { files: [file] } }, 'ekskul');
}
</script>
@endpush
@endsection
