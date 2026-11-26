<div class="card">
    <div class="bar" style="margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #eef2f6">
        <div>
            <h3 style="margin:0;font-size:17px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Identitas Lembaga Sekolah
            </h3>
            <p class="sub" style="margin:4px 0 0">Informasi profil ini akan otomatis tercetak pada kop resmi, sampul buku induk siswa, dan lembar rapor capaian hasil belajar.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.setting.update') }}">
        @csrf
        @method('PUT')

        {{-- Section 1: Data Lembaga --}}
        <div style="margin-bottom:20px">
            <h4 style="margin:0 0 12px;font-size:13px;color:var(--admin-primary);text-transform:uppercase;letter-spacing:0.04em">1. Data Pokok Lembaga</h4>
            <div class="fl" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
                <div style="grid-column:span 2">
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nama Resmi Sekolah *</label>
                    <input type="text" name="nama" maxlength="150" style="width:100%" value="{{ old('nama', $setting->nama ?? 'SMA Negeri 1') }}" required>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">NPSN (Nomor Pokok Sekolah Nasional)</label>
                    <input type="text" name="npsn" maxlength="20" style="width:100%" value="{{ old('npsn', $setting->npsn ?? '') }}" placeholder="mis. 20101234">
                </div>
            </div>
        </div>

        {{-- Section 2: Alamat & Wilayah --}}
        <div style="margin-bottom:20px;padding-top:16px;border-top:1px solid #f1f5f9">
            <h4 style="margin:0 0 12px;font-size:13px;color:var(--admin-primary);text-transform:uppercase;letter-spacing:0.04em">2. Lokasi &amp; Wilayah</h4>
            <div class="fl" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                <div style="grid-column:1/-1">
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Alamat Jalan &amp; Gedung</label>
                    <textarea name="alamat" maxlength="255" style="width:100%;height:65px;font:inherit;border:1.5px solid var(--admin-border);border-radius:10px;padding:9px 12px">{{ old('alamat', $setting->alamat ?? '') }}</textarea>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kelurahan / Desa</label>
                    <input type="text" name="desa" maxlength="100" style="width:100%" value="{{ old('desa', $setting->desa ?? '') }}">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kecamatan</label>
                    <input type="text" name="kecamatan" maxlength="100" style="width:100%" value="{{ old('kecamatan', $setting->kecamatan ?? '') }}">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kabupaten / Kota</label>
                    <input type="text" name="kabupaten" maxlength="100" style="width:100%" value="{{ old('kabupaten', $setting->kabupaten ?? '') }}">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Provinsi</label>
                    <input type="text" name="provinsi" maxlength="100" style="width:100%" value="{{ old('provinsi', $setting->provinsi ?? '') }}">
                </div>
            </div>
        </div>

        {{-- Section 3: Pimpinan Sekolah --}}
        <div style="margin-bottom:20px;padding-top:16px;border-top:1px solid #f1f5f9">
            <h4 style="margin:0 0 12px;font-size:13px;color:var(--admin-primary);text-transform:uppercase;letter-spacing:0.04em">3. Pimpinan Sekolah</h4>
            <div class="fl" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah" maxlength="150" style="width:100%" value="{{ old('nama_kepala_sekolah', $setting->nama_kepala_sekolah ?? '') }}" placeholder="Lengkap dengan gelar">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah" maxlength="30" style="width:100%" value="{{ old('nip_kepala_sekolah', $setting->nip_kepala_sekolah ?? '') }}" placeholder="19xxxxxxxxxxxxxxxx">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nilai Standar KKTP *</label>
                    <input type="number" name="kktp" min="0" max="100" step="0.01" style="width:100%" value="{{ old('kktp', $kktp) }}" required placeholder="75">
                    <small class="sub" style="font-size:11px;display:block;margin-top:3px">Batas Kriteria Ketercapaian Tujuan Pembelajaran.</small>
                </div>
            </div>
        </div>

        <div class="bar" style="margin-top:22px;padding-top:16px;border-top:1px solid #eef2f6">
            <span class="sub">Pastikan data sekolah akurat sebelum disimpan.</span>
            <span class="sp"></span>
            <button type="submit" class="btn a">💾 Simpan Perubahan Profil</button>
        </div>
    </form>
</div>
