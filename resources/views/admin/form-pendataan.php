@extends('layouts.app')

@section('title', ($edit ? 'Edit Data Siswa' : 'Form Pendataan · Siswa Baru') . ' — JosBook Admin')

@section('content')
<h2>Form Pendataan · Siswa {{ $edit ? 'Edit' : 'Baru' }}</h2>
@if($errors->any())
    <div class="note err" role="alert" style="margin-bottom:14px">{{ $errors->first() }}</div>
@endif
@if(!$taAktif)
    <div class="note err" role="alert" style="margin-bottom:14px">
        Tahun ajaran aktif belum diatur. Data belum dapat disimpan sampai tahun ajaran aktif tersedia.
    </div>
@endif
<p class="sub">
    @if($edit)
        Mengubah data siswa <b>{{ $edit->nama_lengkap }}</b> · NISN {{ $edit->nisn }}. Data pribadi berlaku untuk seluruh tahun ajaran.
    @else
        Halaman ini khusus input siswa baru. Untuk mengubah data siswa yang sudah ada, klik <b>Edit</b> di Data Siswa atau Alumni.
    @endif
</p>

<form action="{{ $edit ? route('admin.form-pendataan.update', $edit->id) : route('admin.form-pendataan.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($edit)
        @method('PUT')
    @endif

    <div class="card">
        {{-- Tab Navigation Sesuai Prototype (.seg) --}}
        <div class="seg" style="overflow-x:auto;white-space:nowrap;margin-bottom:14px;padding-bottom:4px">
            <span class="tab-btn on" data-target="tab-a">1. A. Peserta Didik</span>
            <span class="tab-btn" data-target="tab-b">2. B. Tempat Tinggal</span>
            <span class="tab-btn" data-target="tab-c">3. C. Kesehatan</span>
            <span class="tab-btn" data-target="tab-d">4. D. Pendidikan</span>
            <span class="tab-btn" data-target="tab-e">5. E. Orang Tua</span>
            <span class="tab-btn" data-target="tab-f">6. F. Wali</span>
            <span class="tab-btn" data-target="tab-g">7. G. Meninggalkan Sekolah</span>
            <span class="tab-btn" data-target="tab-h">8. H. Selesai Pendidikan</span>
            <span class="tab-btn" data-target="tab-i">9. I. Catatan</span>
            <span class="tab-btn" data-target="tab-ki">Kontrol Induk</span>
            <span class="tab-btn" data-target="tab-berkas">Lampiran Berkas</span>
        </div>

        {{-- TAB A: KETERANGAN PESERTA DIDIK --}}
        <div id="tab-a" class="tab-pane">
            {{-- Pas Foto Box (Prototype fotoBox) --}}
            <div class="note" style="margin-top:0;margin-bottom:14px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
                <div style="width:72px;height:96px;background:#e2e8f0;border-radius:6px;border:1px dashed #94a3b8;display:flex;align-items:center;justify-content:center;overflow:hidden">
                    @if($edit && $edit->foto)
                        <img id="preview-foto" src="{{ route('admin.data-siswa.foto', $edit->id) }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <img id="preview-foto" style="width:100%;height:100%;object-fit:cover;display:none">
                        <span id="label-foto" style="font-size:11px;color:#64748b;text-align:center">Pas foto<br>3×4</span>
                    @endif
                </div>
                <div>
                    <label style="font-weight:600">Pas foto 3×4</label>
                    <input type="file" name="foto_file" id="foto-input" accept=".jpg,.jpeg,.png,.webp" style="max-width:240px;font-size:12px">
                </div>
                <span class="sub" style="margin:0;flex:1;min-width:180px;font-size:12px">
                    Cukup satu pas foto 3x4 (JPG, PNG, atau WEBP, maksimal 2 MB).
                </span>
            </div>

            <div class="fl">
                <div>
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $edit->nama_lengkap ?? '') }}" required>
                </div>
                <div>
                    <label>Nama Panggilan</label>
                    <input type="text" name="nama_panggilan" value="{{ old('nama_panggilan', $edit->nama_panggilan ?? '') }}">
                </div>
                <div>
                    <label>NISN (10 Digit) *</label>
                    <input type="text" name="nisn" maxlength="10" value="{{ old('nisn', $edit->nisn ?? '') }}" required>
                </div>
                <div>
                    <label>Nomor Induk (NIS)</label>
                    <input type="text" name="nomor_induk" value="{{ old('nomor_induk', $edit->nomor_induk ?? '') }}">
                </div>
                <div>
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L" {{ old('jenis_kelamin', $edit->jenis_kelamin ?? 'L') == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('jenis_kelamin', $edit->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $edit->tempat_lahir ?? '') }}">
                </div>
                <div>
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $edit?->tanggal_lahir ? \Carbon\Carbon::parse($edit->tanggal_lahir)->format('Y-m-d') : '') }}">
                </div>
                <div>
                    <label>Agama</label>
                    <select name="agama">
                        @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $ag)
                            <option value="{{ $ag }}" {{ old('agama', $edit->agama ?? 'Islam') == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Kewarganegaraan</label>
                    <input type="text" name="kewarganegaraan" value="{{ old('kewarganegaraan', $edit->kewarganegaraan ?? 'Warga Negara Indonesia') }}">
                </div>
                <div>
                    <label>NIK (16 Digit)</label>
                    <input type="text" name="nik" maxlength="16" value="{{ old('nik', $edit->nik ?? '') }}">
                </div>
                <div>
                    <label>Anak Ke-</label>
                    <input type="number" name="anak_ke" value="{{ old('anak_ke', $edit->anak_ke ?? '') }}">
                </div>
                <div>
                    <label>Jumlah Saudara Kandung</label>
                    <input type="text" name="jumlah_saudara" value="{{ old('jumlah_saudara', $edit->jumlah_saudara ?? '') }}" placeholder="mis. 1 (Satu)">
                </div>
                <div>
                    <label>Status Yatim / Piatu</label>
                    <select name="status_yatim">
                        <option value="Bukan" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Bukan' ? 'selected' : '' }}>Bukan</option>
                        <option value="Yatim" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Yatim' ? 'selected' : '' }}>Yatim</option>
                        <option value="Piatu" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Piatu' ? 'selected' : '' }}>Piatu</option>
                        <option value="Yatim Piatu" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Yatim Piatu' ? 'selected' : '' }}>Yatim Piatu</option>
                    </select>
                </div>
                <div>
                    <label>Bahasa Sehari-hari</label>
                    <input type="text" name="bahasa_sehari_hari" value="{{ old('bahasa_sehari_hari', $edit->bahasa_sehari_hari ?? 'Indonesia') }}">
                </div>
                <div>
                    <label>Kelas Saat Ini *</label>
                    <select name="kelas" required>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}" {{ old('kelas', ($edit && $edit->riwayatKelas->first()) ? $edit->riwayatKelas->first()->kelas?->nama : '') == $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Status Siswa</label>
                    <select name="status">
                        @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                            <option value="{{ $st }}" {{ old('status', ($edit && $edit->riwayatKelas->first()) ? $edit->riwayatKelas->first()->status : 'Aktif') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- TAB B: TEMPAT TINGGAL --}}
        <div id="tab-b" class="tab-pane" style="display:none">
            <div class="fl">
                <div style="grid-column:1/-1">
                    <label>Alamat Jalan / Dusun</label>
                    <input type="text" name="alamat_jalan" value="{{ old('alamat_jalan', $edit->alamat_jalan ?? '') }}">
                </div>
                <div>
                    <label>RT / RW</label>
                    <input type="text" name="alamat_rt_rw" value="{{ old('alamat_rt_rw', $edit->alamat_rt_rw ?? '') }}" placeholder="001/002">
                </div>
                <div>
                    <label>Desa / Kelurahan</label>
                    <input type="text" name="alamat_desa" value="{{ old('alamat_desa', $edit->alamat_desa ?? '') }}">
                </div>
                <div>
                    <label>Kecamatan</label>
                    <input type="text" name="alamat_kecamatan" value="{{ old('alamat_kecamatan', $edit->alamat_kecamatan ?? '') }}">
                </div>
                <div>
                    <label>Kabupaten / Kota</label>
                    <input type="text" name="alamat_kabupaten" value="{{ old('alamat_kabupaten', $edit->alamat_kabupaten ?? '') }}">
                </div>
                <div>
                    <label>Provinsi</label>
                    <input type="text" name="alamat_provinsi" value="{{ old('alamat_provinsi', $edit->alamat_provinsi ?? 'Jawa Timur') }}">
                </div>
                <div>
                    <label>Kode Pos</label>
                    <input type="text" name="alamat_kode_pos" value="{{ old('alamat_kode_pos', $edit->alamat_kode_pos ?? '') }}">
                </div>
                <div>
                    <label>Nomor Telepon / HP</label>
                    <input type="text" name="nomor_hp" value="{{ old('nomor_hp', $edit->nomor_hp ?? '') }}">
                </div>
                <div>
                    <label>Tinggal Bersama</label>
                    <select name="tempat_tinggal">
                        <option value="Orang Tua" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? 'Orang Tua') == 'Orang Tua' ? 'selected' : '' }}>Orang Tua</option>
                        <option value="Wali" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Wali' ? 'selected' : '' }}>Wali</option>
                        <option value="Kost" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Kost' ? 'selected' : '' }}>Kost</option>
                        <option value="Asrama" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Asrama' ? 'selected' : '' }}>Asrama</option>
                    </select>
                </div>
                <div>
                    <label>Jarak ke Sekolah (KM)</label>
                    <input type="number" step="0.1" name="jarak_ke_sekolah_km" value="{{ old('jarak_ke_sekolah_km', $edit->jarak_ke_sekolah_km ?? '') }}">
                </div>
            </div>
        </div>

        {{-- TAB C: KESEHATAN --}}
        <div id="tab-c" class="tab-pane" style="display:none">
            <div class="fl">
                <div>
                    <label>Golongan Darah</label>
                    <select name="golongan_darah">
                        <option value="-" {{ old('golongan_darah', $edit->golongan_darah ?? '-') == '-' ? 'selected' : '' }}>-</option>
                        <option value="A" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'A' ? 'selected' : '' }}>A</option>
                        <option value="B" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'B' ? 'selected' : '' }}>B</option>
                        <option value="AB" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'AB' ? 'selected' : '' }}>AB</option>
                        <option value="O" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'O' ? 'selected' : '' }}>O</option>
                    </select>
                </div>
                <div>
                    <label>Tinggi Badan (cm)</label>
                    <input type="number" name="tinggi_badan_cm" value="{{ old('tinggi_badan_cm', $edit->tinggi_badan_cm ?? '') }}">
                </div>
                <div>
                    <label>Berat Badan (kg)</label>
                    <input type="number" name="berat_badan_kg" value="{{ old('berat_badan_kg', $edit->berat_badan_kg ?? '') }}">
                </div>
                <div style="grid-column:1/-1">
                    <label>Penyakit yang Pernah Diderita</label>
                    <input type="text" name="penyakit" value="{{ old('penyakit', $edit->penyakit ?? '') }}">
                </div>
                <div style="grid-column:1/-1">
                    <label>Kelainan Jasmani / Fisik</label>
                    <input type="text" name="kelainan_fisik" value="{{ old('kelainan_fisik', $edit->kelainan_fisik ?? '') }}">
                </div>
            </div>
        </div>

        {{-- TAB D: PENDIDIKAN SEBELUMNYA --}}
        <div id="tab-d" class="tab-pane" style="display:none">
            <div class="fl">
                <div>
                    <label>Sekolah Asal SMP</label>
                    <input type="text" name="sekolah_asal" value="{{ old('sekolah_asal', $edit->sekolah_asal ?? '') }}">
                </div>
                <div>
                    <label>Tanggal Ijazah SMP</label>
                    <input type="text" name="tanggal_ijazah_smp" value="{{ old('tanggal_ijazah_smp', $edit->tanggal_ijazah_smp ?? '') }}" placeholder="contoh: 09 Juni 2023">
                </div>
                <div>
                    <label>Nomor Ijazah SMP</label>
                    <input type="text" name="nomor_ijazah_smp" value="{{ old('nomor_ijazah_smp', $edit->nomor_ijazah_smp ?? '') }}">
                </div>
                <div>
                    <label>Lama Belajar di SMP (Tahun)</label>
                    <input type="number" name="lama_belajar_tahun" value="{{ old('lama_belajar_tahun', $edit->lama_belajar_tahun ?? 3) }}">
                </div>
                <div>
                    <label>Jenis Registrasi Masuk</label>
                    <select name="jenis_registrasi">
                        <option value="Siswa Baru" {{ old('jenis_registrasi', $edit->jenis_registrasi ?? 'Siswa Baru') == 'Siswa Baru' ? 'selected' : '' }}>Siswa Baru</option>
                        <option value="Pindahan" {{ old('jenis_registrasi', $edit->jenis_registrasi ?? '') == 'Pindahan' ? 'selected' : '' }}>Pindahan</option>
                    </select>
                </div>
                <div>
                    <label>Jika Pindahan: Dari Sekolah</label>
                    <input type="text" name="pindahan_dari_sekolah" value="{{ old('pindahan_dari_sekolah', $edit->pindahan_dari_sekolah ?? '') }}">
                </div>
                <div style="grid-column:1/-1">
                    <label>Alasan Pindah</label>
                    <input type="text" name="alasan_pindah" value="{{ old('alasan_pindah', $edit->alasan_pindah ?? '') }}">
                </div>
                <div>
                    <label>Diterima di Kelas</label>
                    <input type="text" name="kelas_diterima" value="{{ old('kelas_diterima', $edit->kelas_diterima ?? 'X') }}">
                </div>
                <div>
                    <label>Rombel Kelas Ke-</label>
                    <input type="text" name="rombel_diterima" value="{{ old('rombel_diterima', $edit->rombel_diterima ?? '1') }}">
                </div>
                <div>
                    <label>Tanggal Diterima</label>
                    <input type="date" name="tanggal_diterima" value="{{ old('tanggal_diterima', $edit?->tanggal_diterima ? \Carbon\Carbon::parse($edit->tanggal_diterima)->format('Y-m-d') : '') }}">
                </div>
            </div>
        </div>

        {{-- TAB E: ORANG TUA --}}
        <div id="tab-e" class="tab-pane" style="display:none">
            <h4 style="margin:10px 0 6px;color:var(--p)">Data Ayah Kandung</h4>
            <div class="fl">
                <div>
                    <label>Nama Ayah</label>
                    <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $edit->nama_ayah ?? '') }}">
                </div>
                <div>
                    <label>Agama Ayah</label>
                    <input type="text" name="agama_ayah" value="{{ old('agama_ayah', $edit->agama_ayah ?? 'Islam') }}">
                </div>
                <div>
                    <label>Pendidikan Ayah</label>
                    <input type="text" name="pendidikan_ayah" value="{{ old('pendidikan_ayah', $edit->pendidikan_ayah ?? '') }}">
                </div>
                <div>
                    <label>Pekerjaan Ayah</label>
                    <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $edit->pekerjaan_ayah ?? '') }}">
                </div>
                <div>
                    <label>Penghasilan Ayah</label>
                    <input type="text" name="penghasilan_ayah" value="{{ old('penghasilan_ayah', $edit->penghasilan_ayah ?? '') }}" placeholder="mis. Rp 2.000.000 - Rp 5.000.000">
                </div>
                <div>
                    <label>No. HP Ayah</label>
                    <input type="text" name="nomor_hp_ayah" value="{{ old('nomor_hp_ayah', $edit->nomor_hp_ayah ?? '') }}">
                </div>
            </div>

            <h4 style="margin:16px 0 6px;color:var(--p)">Data Ibu Kandung</h4>
            <div class="fl">
                <div>
                    <label>Nama Ibu</label>
                    <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $edit->nama_ibu ?? '') }}">
                </div>
                <div>
                    <label>Agama Ibu</label>
                    <input type="text" name="agama_ibu" value="{{ old('agama_ibu', $edit->agama_ibu ?? 'Islam') }}">
                </div>
                <div>
                    <label>Pendidikan Ibu</label>
                    <input type="text" name="pendidikan_ibu" value="{{ old('pendidikan_ibu', $edit->pendidikan_ibu ?? '') }}">
                </div>
                <div>
                    <label>Pekerjaan Ibu</label>
                    <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $edit->pekerjaan_ibu ?? '') }}">
                </div>
                <div>
                    <label>Penghasilan Ibu</label>
                    <input type="text" name="penghasilan_ibu" value="{{ old('penghasilan_ibu', $edit->penghasilan_ibu ?? '') }}">
                </div>
                <div>
                    <label>No. HP Ibu</label>
                    <input type="text" name="nomor_hp_ibu" value="{{ old('nomor_hp_ibu', $edit->nomor_hp_ibu ?? '') }}">
                </div>
            </div>

            <h4 style="margin:16px 0 6px;color:var(--p)">Alamat Orang Tua</h4>
            <div class="fl">
                <div style="grid-column:1/-1">
                    <label>Jalan / Desa</label>
                    <input type="text" name="ortu_alamat_jalan" value="{{ old('ortu_alamat_jalan', $edit->ortu_alamat_jalan ?? '') }}">
                </div>
                <div>
                    <label>Kecamatan</label>
                    <input type="text" name="ortu_alamat_kecamatan" value="{{ old('ortu_alamat_kecamatan', $edit->ortu_alamat_kecamatan ?? '') }}">
                </div>
                <div>
                    <label>Kabupaten</label>
                    <input type="text" name="ortu_alamat_kabupaten" value="{{ old('ortu_alamat_kabupaten', $edit->ortu_alamat_kabupaten ?? '') }}">
                </div>
            </div>
        </div>

        {{-- TAB F: WALI --}}
        <div id="tab-f" class="tab-pane" style="display:none">
            <div class="fl">
                <div>
                    <label>Nama Wali</label>
                    <input type="text" name="nama_wali" value="{{ old('nama_wali', $edit->nama_wali ?? '') }}">
                </div>
                <div>
                    <label>Agama Wali</label>
                    <input type="text" name="agama_wali" value="{{ old('agama_wali', $edit->agama_wali ?? '') }}">
                </div>
                <div>
                    <label>Pekerjaan Wali</label>
                    <input type="text" name="pekerjaan_wali" value="{{ old('pekerjaan_wali', $edit->pekerjaan_wali ?? '') }}">
                </div>
                <div>
                    <label>No. HP Wali</label>
                    <input type="text" name="nomor_hp_wali" value="{{ old('nomor_hp_wali', $edit->nomor_hp_wali ?? '') }}">
                </div>
                <div style="grid-column:1/-1">
                    <label>Alamat Wali</label>
                    <input type="text" name="wali_alamat_jalan" value="{{ old('wali_alamat_jalan', $edit->wali_alamat_jalan ?? '') }}">
                </div>
            </div>
        </div>

        {{-- TAB G: MENINGGALKAN SEKOLAH --}}
        <div id="tab-g" class="tab-pane" style="display:none">
            <div class="fl">
                <div>
                    <label>Tanggal Keluar</label>
                    <input type="date" name="tanggal_keluar" value="{{ old('tanggal_keluar', $edit?->tanggal_keluar ? \Carbon\Carbon::parse($edit->tanggal_keluar)->format('Y-m-d') : '') }}">
                </div>
                <div>
                    <label>Alasan Keluar</label>
                    <input type="text" name="alasan_keluar" value="{{ old('alasan_keluar', $edit->alasan_keluar ?? '') }}">
                </div>
                <div>
                    <label>Keluar dari Kelas</label>
                    <input type="text" name="kelas_keluar" value="{{ old('kelas_keluar', $edit->kelas_keluar ?? '') }}">
                </div>
                <div>
                    <label>Sekolah Tujuan (Jika Pindah)</label>
                    <input type="text" name="sekolah_tujuan" value="{{ old('sekolah_tujuan', $edit->sekolah_tujuan ?? '') }}">
                </div>
                <div>
                    <label>Nomor Peserta PSAJ</label>
                    <input type="text" name="nomor_peserta_psaj" value="{{ old('nomor_peserta_psaj', $edit->nomor_peserta_psaj ?? '') }}">
                </div>
                <div>
                    <label>Nomor SKL</label>
                    <input type="text" name="nomor_skl" value="{{ old('nomor_skl', $edit->nomor_skl ?? '') }}">
                </div>
                <div>
                    <label>Nomor Ijazah Nasional SMA</label>
                    <input type="text" name="nomor_ijazah_sma" value="{{ old('nomor_ijazah_sma', $edit->nomor_ijazah_sma ?? '') }}">
                </div>
                <div>
                    <label>Tanggal Pengesahan Ijazah</label>
                    <input type="date" name="tanggal_ijazah_sma" value="{{ old('tanggal_ijazah_sma', $edit?->tanggal_ijazah_sma ? \Carbon\Carbon::parse($edit->tanggal_ijazah_sma)->format('Y-m-d') : '') }}">
                </div>
                <div>
                    <label>Nomor Transkrip Nilai</label>
                    <input type="text" name="nomor_transkrip" value="{{ old('nomor_transkrip', $edit->nomor_transkrip ?? '') }}">
                </div>
                <div>
                    <label>Tahun Lulus (4 Digit)</label>
                    <input type="text" name="tahun_lulus" maxlength="4" value="{{ old('tahun_lulus', $edit->tahun_lulus ?? '') }}" placeholder="2026">
                </div>
            </div>
        </div>

        {{-- TAB H: SELESAI PENDIDIKAN --}}
        <div id="tab-h" class="tab-pane" style="display:none">
            <div class="fl">
                <div style="grid-column:1/-1">
                    <label>Melanjutkan Studi / Bekerja</label>
                    <input type="text" name="kegiatan_setelah_lulus" value="{{ old('kegiatan_setelah_lulus', $edit->kegiatan_setelah_lulus ?? '') }}" placeholder="mis. Universitas Indonesia / Bekerja di PT...">
                </div>
            </div>
        </div>

        {{-- TAB I: CATATAN --}}
        <div id="tab-i" class="tab-pane" style="display:none">
            <label>Catatan Khusus Peserta Didik</label>
            <textarea name="catatan" rows="4" style="width:100%">{{ old('catatan', $edit->catatan ?? '') }}</textarea>
        </div>

        {{-- TAB KONTROL INDUK --}}
        <div id="tab-ki" class="tab-pane" style="display:none">
            <div class="note">Registrasi masuk dan keluar, kelulusan, SKL, dan ijazah (sama dengan Kontrol Induk). Berlaku untuk semua tahun ajaran siswa ini.</div>
            <div class="fl">
                <div>
                    <label>Nomor Induk</label>
                    <input type="text" name="nomor_induk" value="{{ old('nomor_induk', $edit->nomor_induk ?? '') }}">
                </div>
                <div>
                    <label>Nomor SKL</label>
                    <input type="text" name="nomor_skl" value="{{ old('nomor_skl', $edit->nomor_skl ?? '') }}">
                </div>
                <div>
                    <label>Nomor Ijazah Nasional</label>
                    <input type="text" name="nomor_ijazah_sma" value="{{ old('nomor_ijazah_sma', $edit->nomor_ijazah_sma ?? '') }}">
                </div>
                <div>
                    <label>Nomor Transkrip</label>
                    <input type="text" name="nomor_transkrip" value="{{ old('nomor_transkrip', $edit->nomor_transkrip ?? '') }}">
                </div>
            </div>
        </div>

        {{-- TAB LAMPIRAN BERKAS --}}
        <div id="tab-berkas" class="tab-pane" style="display:none">
            <div class="note">Centang dokumen yang sudah diserahkan / diverifikasi:</div>
            <div class="fl">
                <div>
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="kk_ada" value="1" {{ old('kk_ada', $edit->kk_ada ?? 0) ? 'checked' : '' }}>
                        Kartu Keluarga (KK)
                    </label>
                </div>
                <div>
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="akta_ada" value="1" {{ old('akta_ada', $edit->akta_ada ?? 0) ? 'checked' : '' }}>
                        Akta Kelahiran
                    </label>
                </div>
                <div>
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="ijazah_smp_ada" value="1" {{ old('ijazah_smp_ada', $edit->ijazah_smp_ada ?? 0) ? 'checked' : '' }}>
                        Ijazah SMP
                    </label>
                </div>
                <div>
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="surat_pindah_ada" value="1" {{ old('surat_pindah_ada', $edit->surat_pindah_ada ?? 0) ? 'checked' : '' }}>
                        Surat Pindah (Jika Pindahan)
                    </label>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="bar" style="margin-top:18px;border-top:1px solid #e2e8f0;padding-top:14px">
            <button type="button" class="btn o" id="btn-prev" style="display:none">‹ Sebelumnya</button>
            <span class="sp"></span>
            <a href="{{ route('admin.data-siswa') }}" class="btn o">Batal</a>
            <button type="button" class="btn" id="btn-next">Berikutnya ›</button>
            <button type="submit" class="btn a">{{ $edit ? 'Simpan Perubahan' : 'Simpan Data Siswa' }}</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function() {
    var tabs = document.querySelectorAll('.tab-btn');
    var panes = document.querySelectorAll('.tab-pane');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');
    var currentIndex = 0;

    function showTab(index) {
        if (index < 0 || index >= tabs.length) return;
        currentIndex = index;

        tabs.forEach(function(t, i) {
            if (i === currentIndex) t.classList.add('on');
            else t.classList.remove('on');
        });

        panes.forEach(function(p, i) {
            if (i === currentIndex) p.style.display = 'block';
            else p.style.display = 'none';
        });

        if (btnPrev) btnPrev.style.display = currentIndex > 0 ? 'inline-block' : 'none';
        if (btnNext) btnNext.style.display = currentIndex < tabs.length - 1 ? 'inline-block' : 'none';
    }

    tabs.forEach(function(t, i) {
        t.addEventListener('click', function() {
            showTab(i);
        });
    });

    btnPrev?.addEventListener('click', function() {
        showTab(currentIndex - 1);
    });

    btnNext?.addEventListener('click', function() {
        showTab(currentIndex + 1);
    });

    // Preview Foto Canvas
    var fotoInput = document.getElementById('foto-input');
    var previewFoto = document.getElementById('preview-foto');
    var labelFoto = document.getElementById('label-foto');

    fotoInput?.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar.');
            return;
        }

        var reader = new FileReader();
        reader.onload = function(evt) {
            if (previewFoto) {
                previewFoto.src = evt.target.result;
                previewFoto.style.display = 'block';
            }
            if (labelFoto) labelFoto.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
})();
</script>
@endpush
@endsection
