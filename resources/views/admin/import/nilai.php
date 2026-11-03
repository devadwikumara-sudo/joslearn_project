@extends('layouts.app')

@section('title', 'Import Nilai - JosBook Admin')

@section('content')
<h2>Import Nilai</h2>
<p class="sub">Unggah file Excel untuk import nilai siswa secara massal</p>

<div class="g g2">
    {{-- Upload Form --}}
    <div class="card">
        <h3>📤 Unggah File Nilai</h3>
        <div class="note">
            Format file: <b>.xlsx / .xls</b> — Kolom wajib: <b>NISN, Nama, Semester, Mata Pelajaran, Nilai Harian, Nilai UTS, Nilai UAS</b>
        </div>
        <form method="POST" action="{{ route('admin.import.nilai.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="fl" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option>
                        <option>2023/2024</option>
                        <option>2022/2023</option>
                    </select>
                </div>
                <div>
                    <label>Semester</label>
                    <select name="semester" style="width:100%">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
                <div>
                    <label>Kelas</label>
                    <select name="kelas" style="width:100%">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList ?? ['X IPA 1','X IPA 2','XI IPA 1','XII IPA 1'] as $k)
                            <option>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" placeholder="Matematika / kosongkan untuk semua" style="width:100%">
                </div>
            </div>
            <div style="border:2px dashed var(--ln);border-radius:12px;padding:24px;text-align:center;margin-bottom:14px" id="dropZone">
                <div style="font-size:32px;margin-bottom:8px">📁</div>
                <p style="color:var(--mu);margin-bottom:12px">Seret file Excel ke sini atau klik untuk pilih</p>
                <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" style="display:none" required onchange="tampilkanNamaFile(this)">
                <button type="button" class="btn o" onclick="document.getElementById('fileInput').click()">Pilih File Excel</button>
                <div id="namaFile" style="margin-top:8px;font-size:12px;color:var(--mu)"></div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn o" onclick="unduhTemplate()">⬇ Unduh Template</button>
                <button type="submit" class="btn">⬆ Import Sekarang</button>
            </div>
        </form>
    </div>

    {{-- Riwayat Import --}}
    <div class="card">
        <h3>🕒 Riwayat Import</h3>
        @forelse($riwayatImport ?? [] as $r)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $r->nama_file }}</b>
                <div style="font-size:11px;color:var(--mu)">
                    {{ $r->kelas }} · Sem {{ $r->semester }} · {{ $r->tahun_ajaran }}
                </div>
            </div>
            <div style="text-align:right">
                <span class="bd {{ $r->status === 'sukses' ? 'ok' : ($r->status === 'sebagian' ? 'w' : 'e') }}">
                    {{ ucfirst($r->status) }}
                </span>
                <div style="font-size:10px;color:var(--mu);margin-top:2px">
                    {{ \Carbon\Carbon::parse($r->created_at)->format('d M Y H:i') }}
                </div>
            </div>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada riwayat import.</p>
        @endforelse
    </div>
</div>

{{-- Pratinjau Hasil --}}
@if(session('preview'))
<div class="card">
    <h3>👁 Pratinjau Data ({{ count(session('preview')) }} baris)</h3>
    <div class="tw">
        <table>
            <thead><tr>
                @foreach(array_keys(session('preview')[0] ?? []) as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @foreach(session('preview') as $row)
                <tr>@foreach($row as $v)<td>{{ $v }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <form method="POST" action="{{ route('admin.import.nilai.confirm') }}">
            @csrf
            <button type="submit" class="btn">✅ Konfirmasi & Simpan</button>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function tampilkanNamaFile(input) {
    var f = input.files[0];
    document.getElementById('namaFile').textContent = f ? '📄 ' + f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)' : '';
}
function unduhTemplate() {
    alert('Template Excel akan tersedia setelah konfigurasi server.');
}
// Drag & drop
var dz = document.getElementById('dropZone');
dz.addEventListener('dragover', function(e){ e.preventDefault(); dz.style.borderColor='var(--p)'; });
dz.addEventListener('dragleave', function(){ dz.style.borderColor='var(--ln)'; });
dz.addEventListener('drop', function(e){
    e.preventDefault(); dz.style.borderColor='var(--ln)';
    var files = e.dataTransfer.files;
    if(files.length){ document.getElementById('fileInput').files = files; tampilkanNamaFile(document.getElementById('fileInput')); }
});
</script>
@endpush

