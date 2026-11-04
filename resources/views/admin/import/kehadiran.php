@extends('layouts.app')

@section('title', 'Import Kehadiran — JosBook Admin')

@section('content')
<h2>Import Kehadiran</h2>
<p class="sub">Unggah file Excel kehadiran siswa (Sakit, Izin, Alpa, dan Nilai Kokurikuler). Data dicatat per semester dan otomatis masuk ke Buku Induk.</p>

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
    <h3>Data hasil impor {{ $taAktif ?? '2024/2025' }}</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Semester</th>
                    <th>Baris Kehadiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $row->semester }}</td>
                    <td><b>{{ $row->baris }}</b> baris</td>
                    <td>
                        <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data kehadiran diimpor pada tahun ajaran ini.
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
        ['0081234561', 'Ahmad Faiz', 'X-1', 'Genap', 1, 0, 0, 'Sangat baik dalam kegiatan P5'],
        ['0081234562', 'Bunga Citra', 'X-1', 'Genap', 0, 1, 0, 'Aktif dan kreatif dalam pameran karya']
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols, ...sample]), 'Kehadiran');
    XLSX.writeFile(w, 'template-kehadiran.xlsx');
}

function loadDemoHadir() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: 'Genap', sakit: 1, izin: 0, alpa: 0, kokur: 'Menunjukkan komitmen tinggi dalam proyek penguatan profil pelajar pancasila.' },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: 'Genap', sakit: 0, izin: 2, alpa: 0, kokur: 'Mampu berkolaborasi aktif dan menyelesaikan tugas kelompok dengan tuntas.' },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: 'Genap', sakit: 2, izin: 1, alpa: 0, kokur: 'Menunjukkan inisiatif kepemimpinan dalam kegiatan kokurikuler.' }
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
