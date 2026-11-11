@extends('layouts.app')

@section('title', 'Import Data Siswa — JosBook Admin')

@section('content')
<h2>Import Data Siswa</h2>
<p class="sub">Unggah file Excel dari sekolah. Format dan tatanan tabel dideteksi otomatis secara fleksibel; sheet PesertaDidik &amp; Kontrol Induk, maupun format tabel kelas apa pun terbaca langsung.</p>

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
            <input value="{{ $taAktif ?? '' }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
            <small class="sub">Tahun ajaran dari Excel akan dipakai otomatis jika terdeteksi; jika tidak, sistem memakai tahun ajaran aktif.</small>
        </div>
        <div>
            <label>Kelas (ketik baru = dibuat otomatis)</label>
            <input list="dlk" name="kelas" id="kelas" value="{{ $kelasList[0] ?? '' }}">
            <datalist id="dlk">
                @foreach($kelasList ?? [] as $k)
                    <option value="{{ $k }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label>Jika NISN sudah ada</label>
            <select name="duplikat" id="duplikat">
                <option value="skip" selected>Lewati</option>
            </select>
        </div>
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, 'siswa')">
        <button type="button" class="btn" onclick="downloadTemplate('siswa')">⬇ Template Excel</button>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Siswa duplikat berdasarkan NISN atau NIS dan baris tidak valid akan dilewati dengan laporan nomor baris dan alasannya.
    </p>
</div>

{{-- Container Preview Otomatis (Card 2 & 3: Deteksi + Pratinjau + Eksekusi Batch) --}}
<div id="josbook-import-preview-area"></div>

{{-- Card 4: Data Hasil Impor --}}
<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $taAktif ?? 'Tahun ajaran belum diatur' }}</h3>
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
                    <td>{{ $taAktif ?? 'Belum diatur' }}</td>
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
    var cols = [
        'nisn','nis','nama','panggilan','jk','tmp','tgl','agama','kewarganegaraan','nik',
        'anak_ke','jml_saudara','yatim','bahasa','kelas','alamat','rt_rw','kelurahan',
        'kecamatan','kab','prov','kodepos','hp','tinggal','jarak','gol_darah','penyakit',
        'kelainan','tinggi','berat','asal','tgl_ijazah_smp','no_ijazah_smp','lama','masuk',
        'sekolah_pindah','alasan_pindah','kelas_masuk','rombel_masuk','tgl_masuk',
        'ayah','agama_ayah','wn_ayah','pend_ayah','kerja_ayah','gaji_ayah','hp_ayah','ket_ayah',
        'ibu','agama_ibu','wn_ibu','pend_ibu','kerja_ibu','gaji_ibu','hp_ibu','ket_ibu',
        'al_jalan','al_rt','al_desa','al_kec','al_kab','al_prov','al_pos',
        'wali','agama_wali','wn_wali','pend_wali','kerja_wali','gaji_wali','hp_wali',
        'wali_jalan','wali_rt','wali_desa','wali_kec','wali_kab','wali_prov','wali_pos',
        'status','tgl_keluar','kelas_keluar','alasan_keluar','tujuan','nopes','no_skl',
        'no_ijazah_sma','tgl_ijazah_sma','no_transkrip','tahun_lulus','lanjut','catatan'
    ];
    var w = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(w, XLSX.utils.aoa_to_sheet([cols]), 'PesertaDidik');
    XLSX.writeFile(w, 'template-' + t + '.xlsx');
}
</script>
@endpush
@endsection
