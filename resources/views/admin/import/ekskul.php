@extends('layouts.app')

@section('title', 'Import Ekstrakurikuler — JosBook Admin')

@section('content')
<h2>Import Ekstrakurikuler</h2>
<p class="sub">Unggah file Excel ekstrakurikuler siswa (Pramuka, PMR, Paskibra, Olahraga, Seni, dll). Nama kegiatan dan predikat nilai (Sangat Baik / Baik / Cukup) dideteksi otomatis.</p>

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
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'ekskul')">
        <button type="button" class="btn" onclick="loadDemoEkskul()">Pakai data contoh</button>
        <button type="button" class="btn" onclick="downloadTemplateEkskul()">⬇ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>ekskul</code> (atau nama kegiatan), <code>predikat</code> (Sangat Baik, Baik, A, B).
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
                    <th>Baris Ekskul</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $row->semester }}</td>
                    <td><b>{{ $row->baris }}</b> kegiatan</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data ekstrakurikuler diimpor pada tahun ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function downloadTemplateEkskul() {
    if (!window.XLSX) {
        alert('Library SheetJS belum selesai termuat.');
        return;
    }
    var cols = ['nisn', 'nama', 'kelas', 'sem', 'ekskul', 'predikat'];
    var sample = [
        ['0081234561', 'Ahmad Faiz', 'X-1', 'Genap', 'Pramuka', 'Sangat Baik'],
        ['0081234562', 'Bunga Citra', 'X-1', 'Genap', 'PMR', 'Baik']
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols, ...sample]), 'Ekskul');
    XLSX.writeFile(w, 'template-ekskul.xlsx');
}

function loadDemoEkskul() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: 'Genap', ekskul: 'Pramuka Wajib', predikat: 'Sangat Baik' },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: 'Genap', ekskul: 'PMR', predikat: 'Baik' },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: 'Genap', ekskul: 'Futsal', predikat: 'Sangat Baik' }
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
