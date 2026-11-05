{{-- Buku Induk Register Peserta Didik (Hal 1-3: A–I) Sesuai Prototype (reg(s)) --}}
@php
    $blnIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgi = function($val) use ($blnIndo) {
        if (!$val || $val === '-') return '-';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$val, $m)) {
            $mIdx = (int)$m[2];
            return ((int)$m[3]) . ' ' . ($blnIndo[$mIdx] ?? '') . ' ' . $m[1];
        }
        return $val;
    };
    $v = function($val, $suffix = '') {
        $val = trim((string)$val);
        if ($val === '' || $val === '-') return '-';
        return $val . $suffix;
    };
    $namaSekolah = strtoupper($sekolah->nama ?? 'SMA NEGERI 1 NGANJUK');
    $fotoSrc = $siswa->foto ? (str_starts_with($siswa->foto, 'data:') ? $siswa->foto : asset('storage/' . $siswa->foto)) : null;

    $jkLabel = ($siswa->jenis_kelamin === 'L' || strtolower($siswa->jenis_kelamin) === 'laki-laki')
        ? 'Laki-laki'
        : (($siswa->jenis_kelamin === 'P' || strtolower($siswa->jenis_kelamin) === 'perempuan') ? 'Perempuan' : ($siswa->jenis_kelamin ?: '-'));
@endphp

{{-- ==================== HALAMAN 1 (A - C) ==================== --}}
<div class="pg dc">
    <div class="hd">
        <div class="hl">
            <div class="ptt">
                I. BUKU INDUK REGISTER PESERTA DIDIK<br>
                KURIKULUM MERDEKA<br>
                {{ $namaSekolah }}
            </div>
            <div class="nn">
                <span>NOMOR INDUK : <u>{{ $siswa->nomor_induk ?: '-' }}</u></span>
                <span>NISN : <u>{{ $siswa->nisn ?: '-' }}</u></span>
            </div>
        </div>
        <div class="ph1">
            @if($fotoSrc)
                <img src="{{ $fotoSrc }}" alt="Pas Foto">
            @else
                <span>Pas foto<br>3 &times; 4</span>
            @endif
        </div>
    </div>

    <div class="fh">A. KETERANGAN PESERTA DIDIK</div>
    <div class="fr"><span>1.</span><span>a. Nama Lengkap</span><span class="v">: {{ $v($siswa->nama_lengkap) }}</span></div>
    <div class="fr"><span></span><span>b. Nama Panggilan</span><span class="v">: {{ $v($siswa->nama_panggilan) }}</span></div>
    <div class="fr"><span>2.</span><span>Jenis Kelamin</span><span class="v">: {{ $jkLabel }}</span></div>
    <div class="fr"><span>3.</span><span>a. Tempat Lahir</span><span class="v">: {{ $v($siswa->tempat_lahir) }}</span></div>
    <div class="fr"><span></span><span>b. Tanggal Lahir</span><span class="v">: {{ $tgi($siswa->tanggal_lahir) }}</span></div>
    <div class="fr"><span>4.</span><span>Agama</span><span class="v">: {{ $v($siswa->agama) }}</span></div>
    <div class="fr"><span>5.</span><span>Kewarganegaraan</span><span class="v">: {{ $v($siswa->kewarganegaraan) }}</span></div>
    <div class="fr"><span>6.</span><span>Nomor Induk Kependudukan</span><span class="v">: {{ $v($siswa->nik) }}</span></div>
    <div class="fr"><span>7.</span><span>Anak ke</span><span class="v">: {{ $v($siswa->anak_ke) }}</span></div>
    <div class="fr"><span>8.</span><span>Jumlah Saudara Kandung</span><span class="v">: {{ $v($siswa->jumlah_saudara) }}</span></div>
    <div class="fr"><span>9.</span><span>Anak Yatim/Yatim Piatu</span><span class="v">: {{ $v($siswa->status_yatim) }}</span></div>
    <div class="fr"><span>10.</span><span>Bahasa Sehari-hari</span><span class="v">: {{ $v($siswa->bahasa_sehari_hari) }}</span></div>

    <div class="fh">B. KETERANGAN TEMPAT TINGGAL</div>
    <div class="fr"><span>12.</span><span>Alamat</span><span></span></div>
    <div class="fr in"><span></span><span>Jalan</span><span class="v">: {{ $v($siswa->alamat_jalan) }}</span></div>
    <div class="fr in"><span></span><span>RT/RW</span><span class="v">: {{ $v($siswa->alamat_rt_rw) }}</span></div>
    <div class="fr in"><span></span><span>Desa</span><span class="v">: {{ $v($siswa->alamat_desa) }}</span></div>
    <div class="fr in"><span></span><span>Kecamatan</span><span class="v">: {{ $v($siswa->alamat_kecamatan) }}</span></div>
    <div class="fr in"><span></span><span>Kabupaten</span><span class="v">: {{ $v($siswa->alamat_kabupaten) }}</span></div>
    <div class="fr in"><span></span><span>Provinsi</span><span class="v">: {{ $v($siswa->alamat_provinsi) }}</span></div>
    <div class="fr in"><span></span><span>Kode Pos</span><span class="v">: {{ $v($siswa->alamat_kode_pos) }}</span></div>
    <div class="fr in"><span></span><span>Nomor Telepon/HP</span><span class="v">: {{ $v($siswa->nomor_hp) }}</span></div>
    <div class="fr"><span>13.</span><span>Tempat Tinggal</span><span class="v">: {{ $v($siswa->tempat_tinggal) }}</span></div>
    <div class="fr"><span>14.</span><span>Jarak ke sekolah</span><span class="v">: {{ is_numeric($siswa->jarak_ke_sekolah_km) ? str_replace('.', ',', (string)$siswa->jarak_ke_sekolah_km) . ' Kilometer' : $v($siswa->jarak_ke_sekolah_km) }}</span></div>

    <div class="fh">C. KETERANGAN KESEHATAN</div>
    <div class="fr"><span>15.</span><span>Golongan Darah</span><span class="v">: {{ $v($siswa->golongan_darah) }}</span></div>
    <div class="fr"><span>16.</span><span>Penyakit yang diderita</span><span class="v">: {{ $v($siswa->penyakit) }}</span></div>
    <div class="fr"><span>17.</span><span>Kelainan Jasmani/Fisik</span><span class="v">: {{ $v($siswa->kelainan_fisik) }}</span></div>
    <div class="fr"><span>18.</span><span>a. Tinggi Badan</span><span class="v">: {{ is_numeric($siswa->tinggi_badan_cm) ? $siswa->tinggi_badan_cm . ' Cm' : $v($siswa->tinggi_badan_cm) }}</span></div>
    <div class="fr"><span></span><span>b. Berat Badan</span><span class="v">: {{ is_numeric($siswa->berat_badan_kg) ? $siswa->berat_badan_kg . ' Kg' : $v($siswa->berat_badan_kg) }}</span></div>
</div>

{{-- ==================== HALAMAN 2 (D - E) ==================== --}}
<div class="pg dc">
    <div class="fh">D. KETERANGAN PENDIDIKAN</div>
    <div class="fr"><span>19.</span><span>Pendidikan Sebelumnya</span><span></span></div>
    <div class="fr in"><span></span><span>a. Sekolah Asal</span><span class="v">: {{ $v($siswa->sekolah_asal) }}</span></div>
    <div class="fr in"><span></span><span>b. Tanggal dan Nomor Ijazah</span><span class="v">: {{ $tgi($siswa->tanggal_ijazah_smp) }} dan {{ $v($siswa->nomor_ijazah_smp) }}</span></div>
    <div class="fr in"><span></span><span>c. Lama Belajar</span><span class="v">: {{ is_numeric($siswa->lama_belajar_tahun) ? $siswa->lama_belajar_tahun . ' Tahun' : $v($siswa->lama_belajar_tahun) }}</span></div>
    <div class="fr"><span>20.</span><span>Pindahan</span><span class="v">: {{ $v($siswa->jenis_registrasi) }}</span></div>
    <div class="fr in"><span></span><span>a. Dari Sekolah</span><span class="v">: {{ $v($siswa->pindahan_dari_sekolah) }}</span></div>
    <div class="fr in"><span></span><span>b. Alasan</span><span class="v">: {{ $v($siswa->alasan_pindah) }}</span></div>
    <div class="fr"><span>21.</span><span>Diterima di sekolah ini</span><span></span></div>
    <div class="fr in"><span></span><span>a. Di kelas</span><span class="v">: {{ $v($siswa->kelas_diterima) }}</span></div>
    <div class="fr in"><span></span><span>b. Rombel Kelas ke</span><span class="v">: {{ trim(($siswa->kelas_diterima ?? '') . '-' . ($siswa->rombel_diterima ?? ''), '-') ?: '-' }}</span></div>
    <div class="fr in"><span></span><span>c. Tanggal</span><span class="v">: {{ $tgi($siswa->tanggal_diterima) }}</span></div>

    <div class="fh">E. KETERANGAN ORANG TUA KANDUNG</div>
    <div class="fr"><span>22.</span><span>Ayah Kandung</span><span></span></div>
    <div class="fr in"><span></span><span>a. Nama</span><span class="v">: {{ $v($siswa->nama_ayah) }}</span></div>
    <div class="fr in"><span></span><span>b. Agama</span><span class="v">: {{ $v($siswa->agama_ayah) }}</span></div>
    <div class="fr in"><span></span><span>c. Kewarganegaraan</span><span class="v">: {{ $v($siswa->kewarganegaraan_ayah) }}</span></div>
    <div class="fr in"><span></span><span>d. Pendidikan Terakhir</span><span class="v">: {{ $v($siswa->pendidikan_ayah) }}</span></div>
    <div class="fr in"><span></span><span>e. Pekerjaan</span><span class="v">: {{ $v($siswa->pekerjaan_ayah) }}</span></div>
    <div class="fr in"><span></span><span>f. Penghasilan per bulan</span><span class="v">: {{ $v($siswa->penghasilan_ayah) }}</span></div>
    <div class="fr in"><span></span><span>g. Nomor Telepon/HP</span><span class="v">: {{ $v($siswa->nomor_hp_ayah) }}</span></div>
    <div class="fr in"><span></span><span>h. Keadaan</span><span class="v">: {{ $v($siswa->keadaan_ayah) }}</span></div>

    <div class="fr" style="margin-top:6px"><span>23.</span><span>Ibu Kandung</span><span></span></div>
    <div class="fr in"><span></span><span>a. Nama</span><span class="v">: {{ $v($siswa->nama_ibu) }}</span></div>
    <div class="fr in"><span></span><span>b. Agama</span><span class="v">: {{ $v($siswa->agama_ibu) }}</span></div>
    <div class="fr in"><span></span><span>c. Kewarganegaraan</span><span class="v">: {{ $v($siswa->kewarganegaraan_ibu) }}</span></div>
    <div class="fr in"><span></span><span>d. Pendidikan Terakhir</span><span class="v">: {{ $v($siswa->pendidikan_ibu) }}</span></div>
    <div class="fr in"><span></span><span>e. Pekerjaan</span><span class="v">: {{ $v($siswa->pekerjaan_ibu) }}</span></div>
    <div class="fr in"><span></span><span>f. Penghasilan per bulan</span><span class="v">: {{ $v($siswa->penghasilan_ibu) }}</span></div>
    <div class="fr in"><span></span><span>g. Nomor Telepon/HP</span><span class="v">: {{ $v($siswa->nomor_hp_ibu) }}</span></div>
    <div class="fr in"><span></span><span>h. Keadaan</span><span class="v">: {{ $v($siswa->keadaan_ibu) }}</span></div>

    <div class="fr" style="margin-top:6px"><span>24.</span><span>Alamat Orang Tua</span><span></span></div>
    <div class="fr in"><span></span><span>Jalan</span><span class="v">: {{ $v($siswa->ortu_alamat_jalan ?: $siswa->alamat_jalan) }}</span></div>
    <div class="fr in"><span></span><span>RT/RW</span><span class="v">: {{ $v($siswa->ortu_alamat_rt_rw ?: $siswa->alamat_rt_rw) }}</span></div>
    <div class="fr in"><span></span><span>Desa</span><span class="v">: {{ $v($siswa->ortu_alamat_desa ?: $siswa->alamat_desa) }}</span></div>
    <div class="fr in"><span></span><span>Kecamatan</span><span class="v">: {{ $v($siswa->ortu_alamat_kecamatan ?: $siswa->alamat_kecamatan) }}</span></div>
    <div class="fr in"><span></span><span>Kabupaten</span><span class="v">: {{ $v($siswa->ortu_alamat_kabupaten ?: $siswa->alamat_kabupaten) }}</span></div>
    <div class="fr in"><span></span><span>Provinsi</span><span class="v">: {{ $v($siswa->ortu_alamat_provinsi ?: $siswa->alamat_provinsi) }}</span></div>
    <div class="fr in"><span></span><span>Kode Pos</span><span class="v">: {{ $v($siswa->ortu_alamat_kode_pos ?: $siswa->alamat_kode_pos) }}</span></div>
</div>

{{-- ==================== HALAMAN 3 (F - I) ==================== --}}
<div class="pg dc">
    <div class="fh">F. KETERANGAN WALI</div>
    <div class="fr"><span>25.</span><span>Wali</span><span></span></div>
    <div class="fr in"><span></span><span>a. Nama</span><span class="v">: {{ $v($siswa->nama_wali) }}</span></div>
    <div class="fr in"><span></span><span>b. Agama</span><span class="v">: {{ $v($siswa->agama_wali) }}</span></div>
    <div class="fr in"><span></span><span>c. Kewarganegaraan</span><span class="v">: {{ $v($siswa->kewarganegaraan_wali) }}</span></div>
    <div class="fr in"><span></span><span>d. Pendidikan Terakhir</span><span class="v">: {{ $v($siswa->pendidikan_wali) }}</span></div>
    <div class="fr in"><span></span><span>e. Pekerjaan</span><span class="v">: {{ $v($siswa->pekerjaan_wali) }}</span></div>
    <div class="fr in"><span></span><span>f. Penghasilan per bulan</span><span class="v">: {{ $v($siswa->penghasilan_wali) }}</span></div>
    <div class="fr in"><span></span><span>g. Nomor Telepon/HP</span><span class="v">: {{ $v($siswa->nomor_hp_wali) }}</span></div>
    <div class="fr in"><span></span><span>h. Alamat</span><span></span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Jalan</span><span class="v">: {{ $v($siswa->wali_alamat_jalan) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>RT/RW</span><span class="v">: {{ $v($siswa->wali_alamat_rt_rw) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Desa</span><span class="v">: {{ $v($siswa->wali_alamat_desa) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Kecamatan</span><span class="v">: {{ $v($siswa->wali_alamat_kecamatan) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Kabupaten</span><span class="v">: {{ $v($siswa->wali_alamat_kabupaten) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Provinsi</span><span class="v">: {{ $v($siswa->wali_alamat_provinsi) }}</span></div>
    <div class="fr in" style="padding-left:60px"><span></span><span>Kode Pos</span><span class="v">: {{ $v($siswa->wali_alamat_kode_pos) }}</span></div>

    <div class="fh">G. MENINGGALKAN SEKOLAH</div>
    <div class="fr"><span>26.</span><span>Meninggalkan Sekolah</span><span></span></div>
    <div class="fr in"><span></span><span>a. Tanggal</span><span class="v">: {{ $tgi($siswa->tanggal_keluar) }}</span></div>
    <div class="fr in"><span></span><span>b. Alasan</span><span class="v">: {{ $v($siswa->alasan_keluar) }}</span></div>
    <div class="fr in"><span></span><span>c. Sekolah Tujuan</span><span class="v">: {{ $v($siswa->sekolah_tujuan) }}</span></div>
    <div class="fr"><span>27.</span><span>Tamat Belajar/Lulus</span><span></span></div>
    <div class="fr in"><span></span><span>a. Tanggal SKL</span><span class="v">: {{ $tgi($siswa->tanggal_keluar) }}</span></div>
    <div class="fr in"><span></span><span>b. Nomor SKL</span><span class="v">: {{ $v($siswa->nomor_skl) }}</span></div>
    <div class="fr in"><span></span><span>c. Tanggal Ijazah</span><span class="v">: {{ $tgi($siswa->tanggal_ijazah_sma) }}</span></div>
    <div class="fr in"><span></span><span>d. Nomor Ijazah</span><span class="v">: {{ $v($siswa->nomor_ijazah_sma) }}</span></div>
    <div class="fr in"><span></span><span>e. Nomor Transkrip Nilai</span><span class="v">: {{ $v($siswa->nomor_transkrip) }}</span></div>

    <div class="fh">H. KETERANGAN SETELAH SELESAI PENDIDIKAN</div>
    <div class="fr"><span>28.</span><span>Melanjutkan Studi / Bekerja</span><span class="v">: {{ $v($siswa->kegiatan_setelah_lulus) }}</span></div>

    <div class="fh">I. CATATAN</div>
    <div style="border-bottom:1px solid #000;min-height:30px;padding:4px 2px;font-size:11px">
        {{ $siswa->catatan && $siswa->catatan !== '-' ? $siswa->catatan : '' }}
    </div>
</div>

