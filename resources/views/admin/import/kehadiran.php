@extends('layouts.app')

@section('title', 'Import Kehadiran — JosBook Admin')

@section('content')
<h2>Import Kehadiran</h2>
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
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'hadir')">
        <button type="button" class="btn" onclick="loadDemoHadir()">Pakai data contoh</button>
        <button type="button" class="btn" onclick="downloadTemplateHadir()">⬇ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>sakit</code>, <code>izin</code>, <code>alpa</code>, <code>kokur</code>. Nilai bukan angka pada kolom absensi akan otomatis dinormalkan ke 0.
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
                    <th>Baris Kehadiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $row->tahun_ajaran }}</td>
                    <td>{{ $row->semester_number ? 'Semester '.$row->semester_number : $row->semester }}</td>
                    <td><b>{{ $row->baris }}</b> baris</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data kehadiran yang diimpor.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function downloadTemplateHadir() {
    if (!window.XLSX) {
        alert('Library SheetJS belum selesai termuat.');
        return;
    }
    var cols = ['nisn', 'nama', 'kelas', 'sem', 'sakit', 'izin', 'alpa', 'kokur'];
    var sample = [
        ['0081234561', 'Ahmad Faiz', 'X-1', '2', 1, 0, 0, 'Sangat baik dalam kegiatan P5'],
        ['0081234562', 'Bunga Citra', 'X-1', '2', 0, 1, 0, 'Aktif dan kreatif dalam pameran karya']
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols, ...sample]), 'Kehadiran');
    XLSX.writeFile(w, 'template-kehadiran.xlsx');
}

function loadDemoHadir() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', sakit: 1, izin: 0, alpa: 0, kokur: 'Menunjukkan komitmen tinggi dalam proyek penguatan profil pelajar pancasila.' },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', sakit: 0, izin: 2, alpa: 0, kokur: 'Mampu berkolaborasi aktif dan menyelesaikan tugas kelompok dengan tuntas.' },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', sakit: 2, izin: 1, alpa: 0, kokur: 'Menunjukkan inisiatif kepemimpinan dalam kegiatan kokurikuler.' }
    ];

    var ws = XLSX.utils.json_to_sheet(dummy);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Kehadiran');
    var wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    var blob = new Blob([wbout], { type: 'application/octet-stream' });
    var file = new File([blob], 'demo_kehadiran.xlsx');

    smartFile({ target: { files: [file] } }, 'hadir');
}
</script>
@endpush
@endsection
