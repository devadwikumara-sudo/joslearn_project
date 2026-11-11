@extends('layouts.app')

@section('title', 'Import Nilai — JosBook Admin')

@section('content')
<h2>Import Nilai</h2>
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
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'nilai')">
        <button type="button" class="btn" onclick="loadDemoNilai()">Pakai data contoh</button>
        <a href="{{ route('admin.import.nilai.template') }}" class="btn">⬇ Template Excel</a>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>kelas</code>, <code>sem</code>, dan kode/nama mapel (<code>mtk</code>, <code>ind</code>, <code>big</code>, <code>pjk</code>, <code>pnc</code>, <code>agm</code>, <code>ipa</code>, <code>ips</code>, <code>inf</code>, <code>sbd</code>, <code>sej</code>, dsb). Format tabel lebar (satu kolom per mapel) maupun panjang (kolom mapel + kolom nilai).
    </p>
</div>

{{-- Container Preview Otomatis (Card 2 & 3: Deteksi + Pratinjau + Eksekusi Batch) --}}
<div id="josbook-import-preview-area"></div>

@include('admin.import._batch-list', [
    'type' => 'nilai',
    'indexRoute' => 'admin.import.nilai',
    'batches' => $batches,
    'tahunAjaranList' => $tahunAjaranList,
    'tahunAjaranTerpilihId' => $tahunAjaranTerpilihId,
])

@push('scripts')
<script>
function loadDemoNilai() {
    var dummy = [
        { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', mtk: 85, ind: 88, big: 82, pnc: 90, pjk: 80 },
        { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', mtk: 90, ind: 92, big: 85, pnc: 88, pjk: 84 },
        { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', mtk: 78, ind: 85, big: 80, pnc: 82, pjk: 86 }
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
