@extends('layouts.app')

@section('title', 'Import Data Siswa — JosBook Admin')

@section('content')
<h2>Import Data Siswa</h2>
<p class="sub">Unggah file Excel dari sekolah (DATABASE_SISWA.xlsx atau file siswa lainnya). Format kolom, sheet PesertaDidik &amp; Kontrol Induk, kelas, dan biodata dideteksi otomatis.</p>

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
            <label>Jika NISN sudah ada</label>
            <select name="duplikat" id="duplikat">
                <option value="skip">Lewati</option>
                <option value="timpa" selected>Timpa</option>
            </select>
        </div>
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'siswa')">
        <button type="button" class="btn" onclick="loadDemoData('siswa')">Pakai data contoh</button>
        <button type="button" class="btn" onclick="downloadTemplate('siswa')">⬇ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Kolom didukung: <code>nisn</code>, <code>nis</code>, <code>nama</code>, <code>kelas</code>, <code>jk</code>, <code>tmp</code>, <code>tgl</code>, <code>agama</code>, <code>nik</code>, <code>alamat</code>, <code>ayah</code>, <code>ibu</code>, <code>hp</code>, dsb. Mendukung sheet gabungan <b>PesertaDidik</b> + <b>Kontrol Induk</b>.
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
                    <th>Tahun Ajaran</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport ?? [] as $row)
                <tr>
                    <td><strong>{{ $row->kelas }}</strong></td>
                    <td>{{ $taAktif ?? '2024/2025' }}</td>
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

@push('scripts')
<script>
function downloadTemplate(t) {
    if (!window.XLSX) {
        alert('Library SheetJS belum selesai termuat.');
        return;
    }
    var cols = ['nisn','nomor_induk','nama_lengkap','kelas','jenis_kelamin','tempat_lahir','tanggal_lahir','agama','nik','alamat','nomor_hp','nama_ayah','nama_ibu'];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols]), 'PesertaDidik');
    XLSX.writeFile(w, 'template-' + t + '.xlsx');
}

function loadDemoData(t) {
    var dummy = [
        { nisn: '0081234561', nomor_induk: '1001', nama_lengkap: 'Ahmad Faiz Pratama', kelas: 'X-1', jenis_kelamin: 'L', tempat_lahir: 'Nganjuk', tanggal_lahir: '2008-04-12', agama: 'Islam', alamat: 'Jl. Ahmad Yani No. 12', nama_ayah: 'Supardi', nama_ibu: 'Siti Aminah' },
        { nisn: '0081234562', nomor_induk: '1002', nama_lengkap: 'Bunga Citra Lestari', kelas: 'X-1', jenis_kelamin: 'P', tempat_lahir: 'Nganjuk', tanggal_lahir: '2008-08-25', agama: 'Islam', alamat: 'Jl. Diponegoro No. 4', nama_ayah: 'Budi Santoso', nama_ibu: 'Endang' },
        { nisn: '0081234563', nomor_induk: '1003', nama_lengkap: 'Dimas Anggara', kelas: 'X-2', jenis_kelamin: 'L', tempat_lahir: 'Kediri', tanggal_lahir: '2008-01-19', agama: 'Islam', alamat: 'Jl. Pahlawan No. 9', nama_ayah: 'Joko Widodo', nama_ibu: 'Sri Rejeki' }
    ];

    // Trigger preview dengan data contoh
    var fakeEvent = {
        target: {
            files: []
        }
    };
    // Gunakan SheetJS untuk membuat file virtual lalu pass ke smartFile
    var ws = XLSX.utils.json_to_sheet(dummy);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'PesertaDidik');
    var wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    var blob = new Blob([wbout], { type: 'application/octet-stream' });
    var file = new File([blob], 'demo_data_siswa.xlsx');

    smartFile({ target: { files: [file] } }, 'siswa');
}
</script>
@endpush
@endsection

