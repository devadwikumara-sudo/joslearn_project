<nav id="alumni-edit-tabs" class="alumni-side-tabs" aria-label="Data yang diedit">
    <button type="button" class="on" data-edit-tab="control">Kontrol Induk</button>
    <button type="button" data-edit-tab="identitas">Identitas</button>
    <button type="button" data-edit-tab="alamat">Tempat Tinggal</button>
    <button type="button" data-edit-tab="kesehatan">Kesehatan</button>
    <button type="button" data-edit-tab="pendidikan">Pendidikan Asal</button>
    <button type="button" data-edit-tab="orang-tua">Orang Tua</button>
    <button type="button" data-edit-tab="wali">Wali</button>
    <button type="button" data-edit-tab="catatan">Catatan</button>
    <button type="button" data-edit-tab="berkas">Lampiran</button>
    <button type="button" data-edit-tab="tracking">Pelacakan</button>
</nav>

<div class="alumni-modal-body">
    <section data-edit-panel="control">
        <div class="alumni-form-grid">
            <div class="alumni-form-heading">Identitas pada register</div>
            <div><label>Nomor induk sekolah</label><input name="nomor_induk" maxlength="64" data-edit-field="nomor_induk"></div>
            <div><label>Nama lengkap</label><input name="nama_lengkap" maxlength="150" required data-edit-field="nama_lengkap"></div>
            <div><label>NISN</label><input name="nisn" inputmode="numeric" maxlength="10" required data-edit-field="nisn"></div>

            <div class="alumni-form-heading">Registrasi dan data masuk</div>
            <div><label>Jenis registrasi</label><input name="jenis_registrasi" maxlength="64" data-edit-field="jenis_registrasi"></div>
            <div><label>Tanggal diterima</label><input type="date" name="tanggal_diterima" data-edit-field="tanggal_diterima"></div>
            <div><label>Kelas diterima</label><input name="kelas_diterima" maxlength="10" data-edit-field="kelas_diterima"></div>
            <div><label>Rombel diterima</label><input name="rombel_diterima" maxlength="10" data-edit-field="rombel_diterima"></div>
            <div><label>Sekolah asal SMP</label><input name="sekolah_asal" maxlength="150" data-edit-field="sekolah_asal"></div>

            <div class="alumni-form-heading">Data keluar atau kelulusan</div>
            <div><label>Keluar karena</label><input name="alasan_keluar" maxlength="255" data-edit-field="alasan_keluar"></div>
            <div><label>Tanggal keluar</label><input type="date" name="tanggal_keluar" data-edit-field="tanggal_keluar"></div>
            <div><label>Kelas keluar</label><input name="kelas_keluar" maxlength="64" data-edit-field="kelas_keluar"></div>
            <div><label>Sekolah tujuan</label><input name="sekolah_tujuan" maxlength="150" data-edit-field="sekolah_tujuan"></div>

            <div class="alumni-form-heading">Dokumen kelulusan</div>
            <div><label>Tahun lulus</label><input name="tahun_lulus" maxlength="4" inputmode="numeric" data-edit-field="tahun_lulus"></div>
            <div><label>Nomor peserta PSAJ</label><input name="nomor_peserta_psaj" maxlength="64" data-edit-field="nomor_peserta_psaj"></div>
            <div><label>Nomor SKL</label><input name="nomor_skl" maxlength="64" data-edit-field="nomor_skl"></div>
            <div><label>Nomor ijazah nasional</label><input name="nomor_ijazah_sma" maxlength="64" data-edit-field="nomor_ijazah_sma"></div>
            <div><label>Tanggal pengesahan ijazah</label><input type="date" name="tanggal_ijazah_sma" data-edit-field="tanggal_ijazah_sma"></div>
            <div><label>Nomor transkrip nilai</label><input name="nomor_transkrip" maxlength="64" data-edit-field="nomor_transkrip"></div>

            <div class="alumni-form-heading">Ringkasan register (otomatis)</div>
            <div><label>Status alumni</label><input readonly data-edit-field="status_alumni"></div>
            <div><label>Kelengkapan Kontrol Induk</label><input readonly data-edit-field="status_kontrol_induk"></div>
            <div><label>Status Pelacakan</label><input readonly data-edit-field="status_pelacakan"></div>
            <div class="alumni-form-summary">
                <label>Melanjutkan studi / bekerja</label>
                <input readonly data-edit-field="kegiatan_setelah_lulus">
                <small>Ringkasan mengikuti data pada tab Pelacakan.</small>
            </div>
        </div>
    </section>

    <section data-edit-panel="identitas" hidden>
        <div class="alumni-detail-section">
            <h4>Identitas dan data pribadi</h4>
            <div class="alumni-form-grid">
                <div class="alumni-form-summary">
                    <label for="alumni-edit-photo">Pas foto 3x4</label>
                    <img id="alumni-edit-photo-preview" alt="Pas foto siswa" hidden style="display:none;width:84px;height:112px;object-fit:cover;border-radius:8px;margin-bottom:8px">
                    <input id="alumni-edit-photo" type="file" name="foto_file" accept=".jpg,.jpeg,.png,.webp">
                    <small>JPG, PNG, atau WEBP, maksimal 2 MB. Foto lama tetap digunakan jika tidak memilih file baru.</small>
                </div>
                <div><label>Nama panggilan</label><input name="nama_panggilan" maxlength="64"></div>
                <div>
                    <label for="alumni-edit-gender">Jenis kelamin</label>
                    <select id="alumni-edit-gender" name="jenis_kelamin">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div><label>NIK siswa</label><input name="nik" inputmode="numeric" maxlength="16"></div>
                <div><label>Tempat lahir</label><input name="tempat_lahir" maxlength="100"></div>
                <div><label>Tanggal lahir</label><input type="date" name="tanggal_lahir"></div>
                <div><label>Agama</label><input name="agama" maxlength="64"></div>
                <div><label>Kewarganegaraan</label><input name="kewarganegaraan" maxlength="64"></div>
                <div><label>Anak ke</label><input type="number" name="anak_ke" min="0" max="255"></div>
                <div><label>Jumlah saudara kandung</label><input name="jumlah_saudara" maxlength="64"></div>
                <div><label>Status yatim / piatu</label><input name="status_yatim" maxlength="64"></div>
                <div><label>Bahasa sehari-hari</label><input name="bahasa_sehari_hari" maxlength="64"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="alamat" hidden>
        <div class="alumni-detail-section">
            <h4>Alamat dan kontak siswa</h4>
            <div class="alumni-form-grid">
                <div class="alumni-form-summary"><label>Alamat jalan / dusun</label><input name="alamat_jalan" maxlength="255"></div>
                <div><label>RT / RW</label><input name="alamat_rt_rw" maxlength="64"></div>
                <div><label>Desa / kelurahan</label><input name="alamat_desa" maxlength="100"></div>
                <div><label>Kecamatan</label><input name="alamat_kecamatan" maxlength="100"></div>
                <div><label>Kabupaten / kota</label><input name="alamat_kabupaten" maxlength="100"></div>
                <div><label>Provinsi</label><input name="alamat_provinsi" maxlength="100"></div>
                <div><label>Kode pos</label><input name="alamat_kode_pos" maxlength="10"></div>
                <div><label>Nomor HP / WhatsApp</label><input name="nomor_hp" maxlength="64"></div>
                <div><label>Tinggal bersama</label><input name="tempat_tinggal" maxlength="64"></div>
                <div><label>Jarak ke sekolah (km)</label><input type="number" step="0.01" name="jarak_ke_sekolah_km" min="0"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="kesehatan" hidden>
        <div class="alumni-detail-section">
            <h4>Data kesehatan siswa</h4>
            <div class="alumni-form-grid">
                <div><label>Golongan darah</label><input name="golongan_darah" maxlength="5"></div>
                <div><label>Tinggi badan (cm)</label><input type="number" name="tinggi_badan_cm" min="0" max="65535"></div>
                <div><label>Berat badan (kg)</label><input type="number" name="berat_badan_kg" min="0" max="65535"></div>
                <div class="alumni-form-summary"><label>Penyakit yang pernah diderita</label><input name="penyakit" maxlength="255"></div>
                <div class="alumni-form-summary"><label>Kelainan jasmani / fisik</label><input name="kelainan_fisik" maxlength="255"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="pendidikan" hidden>
        <div class="alumni-detail-section">
            <h4>Pendidikan sebelum masuk SMA</h4>
            <div class="alumni-form-grid">
                <div><label>Nomor ijazah SMP / MTs</label><input name="nomor_ijazah_smp" maxlength="64"></div>
                <div><label>Tanggal ijazah SMP / MTs</label><input name="tanggal_ijazah_smp" maxlength="64"></div>
                <div><label>Lama belajar di SMP (tahun)</label><input type="number" name="lama_belajar_tahun" min="0" max="255"></div>
                <div><label>Pindahan dari sekolah</label><input name="pindahan_dari_sekolah" maxlength="150"></div>
                <div class="alumni-form-summary"><label>Alasan pindah</label><input name="alasan_pindah" maxlength="255"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="orang-tua" hidden>
        <div class="alumni-detail-section">
            <h4>Data ayah</h4>
            <div class="alumni-form-grid">
                <div><label>Nama ayah</label><input name="nama_ayah" maxlength="150"></div>
                <div><label>Agama ayah</label><input name="agama_ayah" maxlength="64"></div>
                <div><label>Kewarganegaraan ayah</label><input name="kewarganegaraan_ayah" maxlength="64"></div>
                <div><label>Pendidikan ayah</label><input name="pendidikan_ayah" maxlength="64"></div>
                <div><label>Pekerjaan ayah</label><input name="pekerjaan_ayah" maxlength="100"></div>
                <div><label>Penghasilan ayah</label><input name="penghasilan_ayah" maxlength="64"></div>
                <div><label>Nomor HP ayah</label><input name="nomor_hp_ayah" maxlength="64"></div>
                <div><label>Keadaan ayah</label><input name="keadaan_ayah" maxlength="64"></div>
            </div>
        </div>
        <div class="alumni-detail-section">
            <h4>Data ibu</h4>
            <div class="alumni-form-grid">
                <div><label>Nama ibu</label><input name="nama_ibu" maxlength="150"></div>
                <div><label>Agama ibu</label><input name="agama_ibu" maxlength="64"></div>
                <div><label>Kewarganegaraan ibu</label><input name="kewarganegaraan_ibu" maxlength="64"></div>
                <div><label>Pendidikan ibu</label><input name="pendidikan_ibu" maxlength="64"></div>
                <div><label>Pekerjaan ibu</label><input name="pekerjaan_ibu" maxlength="100"></div>
                <div><label>Penghasilan ibu</label><input name="penghasilan_ibu" maxlength="64"></div>
                <div><label>Nomor HP ibu</label><input name="nomor_hp_ibu" maxlength="64"></div>
                <div><label>Keadaan ibu</label><input name="keadaan_ibu" maxlength="64"></div>
            </div>
        </div>
        <div class="alumni-detail-section">
            <h4>Alamat orang tua</h4>
            <div class="alumni-form-grid">
                <div class="alumni-form-summary"><label>Jalan / dusun</label><input name="ortu_alamat_jalan" maxlength="255"></div>
                <div><label>RT / RW</label><input name="ortu_alamat_rt_rw" maxlength="64"></div>
                <div><label>Desa / kelurahan</label><input name="ortu_alamat_desa" maxlength="100"></div>
                <div><label>Kecamatan</label><input name="ortu_alamat_kecamatan" maxlength="100"></div>
                <div><label>Kabupaten / kota</label><input name="ortu_alamat_kabupaten" maxlength="100"></div>
                <div><label>Provinsi</label><input name="ortu_alamat_provinsi" maxlength="100"></div>
                <div><label>Kode pos</label><input name="ortu_alamat_kode_pos" maxlength="10"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="wali" hidden>
        <div class="alumni-detail-section">
            <h4>Data wali siswa</h4>
            <div class="alumni-form-grid">
                <div><label>Nama wali</label><input name="nama_wali" maxlength="150"></div>
                <div><label>Agama wali</label><input name="agama_wali" maxlength="64"></div>
                <div><label>Kewarganegaraan wali</label><input name="kewarganegaraan_wali" maxlength="64"></div>
                <div><label>Pendidikan wali</label><input name="pendidikan_wali" maxlength="64"></div>
                <div><label>Pekerjaan wali</label><input name="pekerjaan_wali" maxlength="100"></div>
                <div><label>Penghasilan wali</label><input name="penghasilan_wali" maxlength="64"></div>
                <div><label>Nomor HP wali</label><input name="nomor_hp_wali" maxlength="64"></div>
                <div class="alumni-form-summary"><label>Alamat jalan / dusun wali</label><input name="wali_alamat_jalan" maxlength="255"></div>
                <div><label>RT / RW</label><input name="wali_alamat_rt_rw" maxlength="64"></div>
                <div><label>Desa / kelurahan</label><input name="wali_alamat_desa" maxlength="100"></div>
                <div><label>Kecamatan</label><input name="wali_alamat_kecamatan" maxlength="100"></div>
                <div><label>Kabupaten / kota</label><input name="wali_alamat_kabupaten" maxlength="100"></div>
                <div><label>Provinsi</label><input name="wali_alamat_provinsi" maxlength="100"></div>
                <div><label>Kode pos</label><input name="wali_alamat_kode_pos" maxlength="10"></div>
            </div>
        </div>
    </section>

    <section data-edit-panel="catatan" hidden>
        <div class="alumni-detail-section">
            <h4>Catatan siswa</h4>
            <label for="alumni-edit-catatan" style="display:block;margin:0 0 5px;color:var(--mu);font-size:11px;font-weight:700">Catatan bimbingan / prestasi</label>
            <textarea id="alumni-edit-catatan" name="catatan" rows="7" maxlength="16000" style="width:100%"></textarea>
        </div>
    </section>

    <section data-edit-panel="berkas" hidden>
        <div class="alumni-detail-section">
            <h4>Pas foto siswa</h4>
            <div id="alumni-edit-photo-file"></div>
        </div>
        <div class="alumni-detail-section">
            <h4>Lampiran dokumen</h4>
            <p class="student-profile-empty">Format PDF, JPG, JPEG, atau PNG, maksimal 5 MB per file. File lama tetap digunakan jika tidak memilih file pengganti.</p>
            <div class="alumni-form-grid" style="margin-top:12px">
                <div>
                    <label>Kartu Keluarga (KK)</label>
                    <div data-existing-attachment="kk"></div>
                    <input type="file" name="berkas[kk]" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="alumni-form-flag"><input type="checkbox" name="kk_ada" value="1"> Dokumen sudah diserahkan / diverifikasi</label>
                </div>
                <div>
                    <label>Akta kelahiran</label>
                    <div data-existing-attachment="akta"></div>
                    <input type="file" name="berkas[akta]" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="alumni-form-flag"><input type="checkbox" name="akta_ada" value="1"> Dokumen sudah diserahkan / diverifikasi</label>
                </div>
                <div>
                    <label>Ijazah SMP / MTs</label>
                    <div data-existing-attachment="ijazah_smp"></div>
                    <input type="file" name="berkas[ijazah_smp]" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="alumni-form-flag"><input type="checkbox" name="ijazah_smp_ada" value="1"> Dokumen sudah diserahkan / diverifikasi</label>
                </div>
                <div>
                    <label>Surat pindah</label>
                    <div data-existing-attachment="surat_pindah"></div>
                    <input type="file" name="berkas[surat_pindah]" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="alumni-form-flag"><input type="checkbox" name="surat_pindah_ada" value="1"> Dokumen sudah diserahkan / diverifikasi</label>
                </div>
            </div>
        </div>
    </section>

    <section data-edit-panel="tracking" hidden>
        <div class="alumni-form-grid">
            <div>
                <label for="edit-tracking-kind">Kegiatan setelah lulus</label>
                <select id="edit-tracking-kind" name="tracking_jenis">
                    <option value="">Pilih kegiatan</option>
                    <option value="Kuliah">Kuliah</option>
                    <option value="Bekerja">Bekerja</option>
                    <option value="Wirausaha">Wirausaha</option>
                    <option value="Mencari kerja">Mencari kerja</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div><label for="edit-tracking-place">Kampus / instansi / perusahaan</label><input id="edit-tracking-place" name="tracking_nama_tempat" maxlength="150"></div>
            <div><label for="edit-tracking-year">Tahun</label><input id="edit-tracking-year" type="number" min="1900" max="2099" name="tracking_tahun"></div>
            <div><label for="edit-tracking-note">Catatan pelacakan</label><input id="edit-tracking-note" name="tracking_keterangan" maxlength="255"></div>
        </div>
        <section class="alumni-detail-section">
            <h4>Riwayat pelacakan</h4>
            <div id="alumni-edit-tracking-history"></div>
        </section>
    </section>
</div>
