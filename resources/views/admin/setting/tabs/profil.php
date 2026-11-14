<div class="card">
    <h3>🏫 Identitas Sekolah</h3>
    <p class="sub">Data ini akan muncul pada sampul buku induk dan laporan capaian hasil belajar.</p>
    <form method="POST" action="{{ route('admin.setting.update') }}">
        @csrf
        @method('PUT')
        <div style="display:grid;gap:12px">
            <div>
                <label>Nama Sekolah</label>
                <input type="text" name="nama" maxlength="150" style="width:100%" value="{{ old('nama', $setting->nama ?? 'SMA Negeri 1') }}" required>
            </div>
            <div>
                <label>NPSN</label>
                <input type="text" name="npsn" maxlength="20" style="width:100%" value="{{ old('npsn', $setting->npsn ?? '') }}">
            </div>
            <div>
                <label>Alamat Sekolah</label>
                <textarea name="alamat" maxlength="255" style="width:100%;height:65px;font:inherit;border:1px solid var(--ln);border-radius:10px;padding:8px">{{ old('alamat', $setting->alamat ?? '') }}</textarea>
            </div>
            <div>
                <label>Kelurahan / Desa</label>
                <input type="text" name="desa" maxlength="100" style="width:100%" value="{{ old('desa', $setting->desa ?? '') }}">
            </div>
            <div>
                <label>Kecamatan</label>
                <input type="text" name="kecamatan" maxlength="100" style="width:100%" value="{{ old('kecamatan', $setting->kecamatan ?? '') }}">
            </div>
            <div>
                <label>Kabupaten / Kota</label>
                <input type="text" name="kabupaten" maxlength="100" style="width:100%" value="{{ old('kabupaten', $setting->kabupaten ?? '') }}">
            </div>
            <div>
                <label>Provinsi</label>
                <input type="text" name="provinsi" maxlength="100" style="width:100%" value="{{ old('provinsi', $setting->provinsi ?? '') }}">
            </div>
            <div>
                <label>Nama Kepala Sekolah</label>
                <input type="text" name="nama_kepala_sekolah" maxlength="150" style="width:100%" value="{{ old('nama_kepala_sekolah', $setting->nama_kepala_sekolah ?? '') }}">
            </div>
            <div>
                <label>NIP Kepala Sekolah</label>
                <input type="text" name="nip_kepala_sekolah" maxlength="30" style="width:100%" value="{{ old('nip_kepala_sekolah', $setting->nip_kepala_sekolah ?? '') }}">
            </div>
            <div>
                <label>Semester Berjalan</label>
                <select name="semester_berjalan" style="width:100%" required>
                    <option value="Ganjil" {{ old('semester_berjalan', $semesterBerjalan) === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ old('semester_berjalan', $semesterBerjalan) === 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>
                <small class="sub">Menentukan semester berjalan untuk impor dan akses data siswa di aplikasi mobile.</small>
            </div>
            <div>
                <label>Nilai KKTP</label>
                <input type="number" name="kktp" min="0" max="100" step="0.01" style="width:100%" value="{{ old('kktp', $kktp) }}" required>
            </div>
        </div>
        <div style="margin-top:14px">
            <button type="submit" class="btn a">💾 Simpan Perubahan</button>
        </div>
    </form>
</div>
