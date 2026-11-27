@php
    $blnIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgi = function ($val) use ($blnIndo) {
        if (! $val || $val === '-') return '-';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $val, $m)) {
            $day = str_pad((string)(int)$m[3], 2, '0', STR_PAD_LEFT);
            return $day.' '.($blnIndo[(int)$m[2]] ?? '').' '.$m[1];
        }
        return $val;
    };
    $v = fn ($val, $suffix = '') => filled($val) && $val !== '-' ? trim((string)$val).$suffix : '-';
    $namaSekolahSetting = trim((string) ($sekolah->nama ?? ''));
    $namaSekolah = str_contains(strtolower($namaSekolahSetting), 'contoh') || $namaSekolahSetting === ''
        ? 'SMA NEGERI 1 REJOSO NGANJUK'
        : strtoupper($namaSekolahSetting);
    $fotoSrc = $siswa->foto ? (str_starts_with($siswa->foto, 'data:')
        ? $siswa->foto
        : route(request()->routeIs('guru.*') ? 'guru.data-siswa.foto' : 'admin.data-siswa.foto', ['id' => $siswa->id])) : null;
@endphp

{{-- ==================== HALAMAN 1: A, B, C + 4 Kotak Foto ==================== --}}
<div class="pg dc reg reg-p1">
    <div class="reg-title">
        I. BUKU INDUK REGISTER PESERTA DIDIK<br>
        KURIKULUM MERDEKA<br>
        {{ $namaSekolah }}
    </div>
    <div class="reg-number">
        <span>NOMOR INDUK : <span class="reg-num-val">{{ $v($siswa->nomor_induk) }}</span></span>
        <span class="reg-nisn-wrap">NISN : <span class="reg-num-val">{{ $v($siswa->nisn) }}</span></span>
    </div>

    <div class="reg-photo-stack">
        @for($i=0;$i<2;$i++)
            <div class="reg-photo">
                @if($i===0&&$fotoSrc)
                    <img src="{{ $fotoSrc }}" alt="Foto masuk">
                @else
                    <div class="reg-photo-inner">
                        <span class="reg-photo-size">{{ $i === 0 ? '2x3' : '3x4' }}</span>
                        <span class="reg-photo-label">Foto Masuk</span>
                    </div>
                @endif
            </div>
        @endfor
        @for($i=0;$i<2;$i++)
            <div class="reg-photo">
                <div class="reg-photo-inner">
                    <span class="reg-photo-size">{{ $i === 0 ? '2x3' : '3x4' }}</span>
                    <span class="reg-photo-label">Foto Keluar</span>
                </div>
            </div>
        @endfor
    </div>

    <div class="reg-p1-body">
        <div class="fh reg-fh">A. KETERANGAN PESERTA DIDIK</div>
        <div class="fr"><span>1.</span><span>a. Nama Lengkap</span><span class="c">:</span><span class="v">{{ $v($siswa->nama_lengkap) }}</span></div>
        <div class="fr"><span></span><span>b. Nama Panggilan</span><span class="c">:</span><span class="v">{{ $v($siswa->nama_panggilan) }}</span></div>
        <div class="fr"><span>2.</span><span>Jenis Kelamin</span><span class="c">:</span><span class="v">{{ $v($siswa->jenis_kelamin) }}</span></div>
        <div class="fr"><span>3.</span><span>a. Tempat Lahir</span><span class="c">:</span><span class="v">{{ $v($siswa->tempat_lahir) }}</span></div>
        <div class="fr"><span></span><span>b. Tanggal Lahir</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_lahir) }}</span></div>
        <div class="fr"><span>4.</span><span>Agama</span><span class="c">:</span><span class="v">{{ $v($siswa->agama) }}</span></div>
        <div class="fr"><span>5.</span><span>Kewarganegaraan</span><span class="c">:</span><span class="v">{{ $v($siswa->kewarganegaraan) }}</span></div>
        <div class="fr"><span>6.</span><span>Nomor Induk Kependudukan</span><span class="c">:</span><span class="v">{{ $v($siswa->nik) }}</span></div>
        <div class="fr"><span>7.</span><span>Anak ke</span><span class="c">:</span><span class="v">{{ $v($siswa->anak_ke) }}</span></div>
        <div class="fr"><span>8.</span><span>Jumlah Saudara Kandung</span><span class="c">:</span><span class="v">{{ $v($siswa->jumlah_saudara) }}</span></div>
        <div class="fr"><span>9.</span><span>Anak Yatim/Yatim Piatu</span><span class="c">:</span><span class="v">{{ $v($siswa->status_yatim) }}</span></div>
        <div class="fr"><span>10.</span><span>Bahasa Sehari-hari</span><span class="c">:</span><span class="v">{{ $v($siswa->bahasa_sehari_hari) }}</span></div>

        <div class="fh reg-fh">B. KETERANGAN TEMPAT TINGGAL</div>
        <div class="fr"><span>12.</span><span>Alamat</span><span class="c"></span><span></span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Jalan</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_jalan) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RT/RW</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_rt_rw) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Desa</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_desa) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kecamatan</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_kecamatan) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kabupaten</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_kabupaten) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Provinsi</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_provinsi) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kode Pos</span><span class="c">:</span><span class="v">{{ $v($siswa->alamat_kode_pos) }}</span></div>
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nomor Telepon/HP</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_hp) }}</span></div>
        <div class="fr"><span>13.</span><span>Tempat Tinggal</span><span class="c">:</span><span class="v">{{ $v($siswa->tempat_tinggal) }}</span></div>
        <div class="fr"><span>14.</span><span>Jarak ke sekolah</span><span class="c">:</span><span class="v">{{ is_numeric($siswa->jarak_ke_sekolah_km) ? str_replace('.', ',', (string)$siswa->jarak_ke_sekolah_km).' Kilometer' : $v($siswa->jarak_ke_sekolah_km) }}</span></div>

        <div class="fh reg-fh">C. KETERANGAN KESEHATAN</div>
        <div class="fr"><span>15.</span><span>Golongan Darah</span><span class="c">:</span><span class="v">{{ $v($siswa->golongan_darah) }}</span></div>
        <div class="fr"><span>16.</span><span>Penyakit yang diderita</span><span class="c">:</span><span class="v">{{ $v($siswa->penyakit) }}</span></div>
        <div class="fr"><span>17.</span><span>Kelainan Jasmani/Fisik</span><span class="c">:</span><span class="v">{{ $v($siswa->kelainan_fisik) }}</span></div>
        <div class="fr"><span>18.</span><span>a. Tinggi Badan</span><span class="c">:</span><span class="v">{{ is_numeric($siswa->tinggi_badan_cm) ? $siswa->tinggi_badan_cm.' Cm' : $v($siswa->tinggi_badan_cm) }}</span></div>
        <div class="fr"><span></span><span>b. Berat Badan</span><span class="c">:</span><span class="v">{{ is_numeric($siswa->berat_badan_kg) ? $siswa->berat_badan_kg.' Kg' : $v($siswa->berat_badan_kg) }}</span></div>
    </div>
</div>

{{-- ==================== HALAMAN 2: D, E ==================== --}}
<div class="pg dc reg">
    <div class="fh reg-fh">D. KETERANGAN PENDIDIKAN</div>
    <div class="fr"><span>19.</span><span>Pendidikan Sebelumnya</span><span class="c"></span><span></span></div>
    <div class="fr in"><span></span><span>a. Sekolah Asal</span><span class="c">:</span><span class="v">{{ $v($siswa->sekolah_asal) }}</span></div>
    <div class="fr in"><span></span><span>b. Tanggal dan Nomor Ijazah</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_ijazah_smp) }} &nbsp;&nbsp;&nbsp;&nbsp; dan &nbsp;&nbsp; {{ $v($siswa->nomor_ijazah_smp) }}</span></div>
    <div class="fr in"><span></span><span>c. Lama Belajar</span><span class="c">:</span><span class="v">{{ is_numeric($siswa->lama_belajar_tahun) ? $siswa->lama_belajar_tahun.' Tahun' : $v($siswa->lama_belajar_tahun) }}</span></div>
    <div class="fr"><span>20.</span><span>Pindahan</span><span class="c">:</span><span class="v">{{ $v($siswa->jenis_registrasi) }}</span></div>
    <div class="fr in"><span></span><span>a. Dari Sekolah</span><span class="c">:</span><span class="v">{{ $v($siswa->pindahan_dari_sekolah) }}</span></div>
    <div class="fr in"><span></span><span>b. Alasan</span><span class="c">:</span><span class="v">{{ $v($siswa->alasan_pindah) }}</span></div>
    <div class="fr"><span>21.</span><span>Diterima di sekolah ini</span><span class="c"></span><span></span></div>
    <div class="fr in"><span></span><span>a. Di kelas</span><span class="c">:</span><span class="v">{{ $v($siswa->kelas_diterima) }}</span></div>
    <div class="fr in"><span></span><span>b. Rombel Kelas ke</span><span class="c">:</span><span class="v">{{ $v(trim(($siswa->kelas_diterima ?? '').'-'.($siswa->rombel_diterima ?? ''), '-')) }}</span></div>
    <div class="fr in"><span></span><span>c. Tanggal</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_diterima) }}</span></div>

    <div class="fh reg-fh">E. KETERANGAN ORANG TUA KANDUNG</div>
    @foreach(['Ayah' => 'ayah', 'Ibu' => 'ibu'] as $label => $key)
        <div class="fr"><span>{{ $key === 'ayah' ? '22.' : '23.' }}</span><span>{{ $label }} Kandung</span><span class="c"></span><span></span></div>
        <div class="fr in"><span></span><span>a. Nama</span><span class="c">:</span><span class="v">{{ $v($siswa->{'nama_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>b. Agama</span><span class="c">:</span><span class="v">{{ $v($siswa->{'agama_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>c. Kewarganegaraan</span><span class="c">:</span><span class="v">{{ $v($siswa->{'kewarganegaraan_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>d. Pendidikan Terakhir</span><span class="c">:</span><span class="v">{{ $v($siswa->{'pendidikan_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>e. Pekerjaan</span><span class="c">:</span><span class="v">{{ $v($siswa->{'pekerjaan_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>f. Penghasilan per bulan</span><span class="c">:</span><span class="v">{{ $v($siswa->{'penghasilan_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>g. Nomor Telepon/HP</span><span class="c">:</span><span class="v">{{ $v($siswa->{'nomor_hp_'.$key}) }}</span></div>
        <div class="fr in"><span></span><span>h. Keadaan</span><span class="c">:</span><span class="v">{{ $v($siswa->{'keadaan_'.$key}) }}</span></div>
    @endforeach
    <div class="fr"><span>24.</span><span>Alamat Orang Tua</span><span class="c"></span><span></span></div>
    @foreach(['Jalan'=>'ortu_alamat_jalan','RT/RW'=>'ortu_alamat_rt_rw','Desa'=>'ortu_alamat_desa','Kecamatan'=>'ortu_alamat_kecamatan','Kabupaten'=>'ortu_alamat_kabupaten','Provinsi'=>'ortu_alamat_provinsi','Kode Pos'=>'ortu_alamat_kode_pos'] as $label=>$key)
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $label }}</span><span class="c">:</span><span class="v">{{ $v($siswa->{$key} ?: null) }}</span></div>
    @endforeach
</div>

{{-- ==================== HALAMAN 3: F, G, H, I ==================== --}}
<div class="pg dc reg">
    <div class="fh reg-fh">F. KETERANGAN WALI</div>
    <div class="fr"><span>25.</span><span>Wali</span><span class="c"></span><span></span></div>
    @foreach(['a. Nama'=>'nama_wali','b. Agama'=>'agama_wali','c. Kewarganegaraan'=>'kewarganegaraan_wali','d. Pendidikan Terakhir'=>'pendidikan_wali','e. Pekerjaan'=>'pekerjaan_wali','f. Penghasilan per bulan'=>'penghasilan_wali','g. Nomor Telepon/HP'=>'nomor_hp_wali'] as $label=>$key)
        <div class="fr in"><span></span><span>{{ $label }}</span><span class="c">:</span><span class="v">{{ $v($siswa->{$key}) }}</span></div>
    @endforeach
    <div class="fr in"><span></span><span>h. Alamat</span><span class="c"></span><span></span></div>
    @foreach(['Jalan'=>'wali_alamat_jalan','RT/RW'=>'wali_alamat_rt_rw','Desa'=>'wali_alamat_desa','Kecamatan'=>'wali_alamat_kecamatan','Kabupaten'=>'wali_alamat_kabupaten','Provinsi'=>'wali_alamat_provinsi','Kode Pos'=>'wali_alamat_kode_pos'] as $label=>$key)
        <div class="fr in"><span></span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $label }}</span><span class="c">:</span><span class="v">{{ $v($siswa->{$key}) }}</span></div>
    @endforeach

    <div class="fh reg-fh">G. MENINGGALKAN SEKOLAH</div>
    <div class="fr"><span>26.</span><span>Meninggalkan Sekolah</span><span class="c"></span><span></span></div>
    <div class="fr in"><span></span><span>a. Tanggal</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_keluar) }}</span></div>
    <div class="fr in"><span></span><span>b. Alasan</span><span class="c">:</span><span class="v">{{ $v($siswa->alasan_keluar) }}</span></div>
    <div class="fr"><span>27.</span><span>Tamat Belajar/Lulus</span><span class="c"></span><span></span></div>
    <div class="fr in"><span></span><span>a. Tanggal SKL</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_keluar) }}</span></div>
    <div class="fr in"><span></span><span>b. Nomor SKL</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_skl) }}</span></div>
    <div class="fr in"><span></span><span>c. Tanggal Ijazah</span><span class="c">:</span><span class="v">{{ $tgi($siswa->tanggal_ijazah_sma) }}</span></div>
    <div class="fr in"><span></span><span>d. Nomor Ijazah</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_ijazah_sma) }}</span></div>
    <div class="fr in"><span></span><span>e. Nomor Transkrip Nilai</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_transkrip) }}</span></div>
    <div class="fr in"><span></span><span>f. Nomor Peserta PSAJ</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_peserta_psaj) }}</span></div>
    <div class="fr in"><span></span><span>g. Tahun Lulus</span><span class="c">:</span><span class="v">{{ $v($siswa->tahun_lulus) }}</span></div>

    <div class="fh reg-fh">H. KETERANGAN SETELAH SELESAI PENDIDIKAN</div>
    <div class="fr"><span>28.</span><span>Melanjutkan Studi / Bekerja</span><span class="c">:</span><span class="v">{{ filled($siswa->kegiatan_setelah_lulus) && $siswa->kegiatan_setelah_lulus !== '-' ? trim((string)$siswa->kegiatan_setelah_lulus) : '' }}</span></div>
    <div class="fr"><span></span><span></span><span class="c"></span><span class="v">&nbsp;</span></div>

    <div class="fh reg-fh">I. CATATAN</div>
    <div class="reg-catatan-lines">
        <div class="reg-catatan-line">{{ filled($siswa->catatan) && $siswa->catatan !== '-' ? trim((string)$siswa->catatan) : '' }}&nbsp;</div>
        <div class="reg-catatan-line">&nbsp;</div>
        <div class="reg-catatan-line">&nbsp;</div>
        <div class="reg-catatan-line">&nbsp;</div>
    </div>
</div>
