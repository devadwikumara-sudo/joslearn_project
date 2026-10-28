<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Dibuat berdasarkan database (1).sql terbaru. Membutuhkan MySQL 8.0+.
        DB::unprepared(<<<'SQL'
CREATE TABLE users (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  peran             ENUM('admin','guru','siswa') NOT NULL,
  name              VARCHAR(150) NOT NULL,                -- nama tampilan
  username          VARCHAR(60)  NOT NULL UNIQUE,         -- NIP / NISN / username admin
  email             VARCHAR(150) NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,                -- hash Laravel
  -- menunggu = siswa baru aktivasi mandiri, belum diverifikasi admin
  status            ENUM('menunggu','aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 1,
  mfa_aktif         TINYINT(1) NOT NULL DEFAULT 0,        -- layar Backup & Keamanan
  login_terakhir    TIMESTAMP NULL,
  remember_token    VARCHAR(100) NULL,
  dibuat_oleh       BIGINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  deleted_at        TIMESTAMP NULL,
  CONSTRAINT fk_users_pembuat FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_users_peran (peran, status)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE password_reset_tokens (
  email       VARCHAR(150) PRIMARY KEY,
  token       VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP NULL
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE sessions (
  id            VARCHAR(255) PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    TEXT NULL,
  payload       LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX idx_sessions_user (user_id),
  INDEX idx_sessions_last (last_activity)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE personal_access_tokens (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id   BIGINT UNSIGNED NOT NULL,
  name           VARCHAR(255) NOT NULL,
  token          VARCHAR(64) NOT NULL UNIQUE,
  abilities      TEXT NULL,
  last_used_at   TIMESTAMP NULL,
  expires_at     TIMESTAMP NULL,
  created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE kode_otp (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  tujuan          ENUM('reset_sandi','login_mfa') NOT NULL,
  kode_hash       VARCHAR(255) NOT NULL,
  percobaan       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  kedaluwarsa_pada DATETIME NOT NULL,
  dipakai_pada    DATETIME NULL,
  created_at      TIMESTAMP NULL,
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_otp_user (user_id, tujuan)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE tahun_ajaran (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nama        VARCHAR(20) NOT NULL UNIQUE,              -- 2026/2027
  tanggal_mulai   DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  aktif       TINYINT(1) NOT NULL DEFAULT 0,            -- hanya 1 yang aktif (dijaga aplikasi)
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE semester (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tahun_ajaran_id  BIGINT UNSIGNED NOT NULL,
  nama             ENUM('Ganjil','Genap') NOT NULL,
  tanggal_mulai    DATE NOT NULL,
  tanggal_selesai  DATE NOT NULL,
  batas_nilai      DATE NULL,                           -- batas unggah & validasi rapor (layar daftar siswa guru)
  catatan          TEXT NULL,                           -- "Catatan akademik" (layar Tahun Ajaran & Semester)
  aktif            TINYINT(1) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_semester (tahun_ajaran_id, nama),
  CONSTRAINT fk_semester_tahun FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE guru (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id        BIGINT UNSIGNED NOT NULL UNIQUE,
  nip            VARCHAR(30) NULL UNIQUE,
  nama_lengkap   VARCHAR(150) NOT NULL,
  jenis_kelamin  ENUM('L','P') NULL,
  no_hp          VARCHAR(20) NULL,
  alamat         TEXT NULL,
  foto           VARCHAR(255) NULL,
  created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  deleted_at     TIMESTAMP NULL,
  CONSTRAINT fk_guru_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE kelas (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tahun_ajaran_id  BIGINT UNSIGNED NOT NULL,
  nama             VARCHAR(30) NOT NULL,                -- XI IPA 1
  tingkat          TINYINT UNSIGNED NOT NULL,           -- 10, 11, 12 (SMA)
  jurusan          VARCHAR(30) NULL,                    -- IPA / IPS / MIPA / RPL
  created_at       TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  UNIQUE KEY uq_kelas (tahun_ajaran_id, nama),
  CONSTRAINT fk_kelas_tahun FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE siswa (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id        BIGINT UNSIGNED NULL UNIQUE,
  nisn           VARCHAR(20) NOT NULL UNIQUE,           -- identitas utama (dipakai di layar daftar/login)
  nis            VARCHAR(30) NULL UNIQUE,
  nama_lengkap   VARCHAR(150) NOT NULL,
  jenis_kelamin  ENUM('L','P') NULL,
  tempat_lahir   VARCHAR(100) NULL,
  tanggal_lahir  DATE NULL,
  alamat         TEXT NULL,
  nama_orang_tua VARCHAR(150) NULL,
  no_hp          VARCHAR(20) NULL,
  foto           VARCHAR(255) NULL,
  created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  deleted_at     TIMESTAMP NULL,
  CONSTRAINT fk_siswa_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE kelas_siswa (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  kelas_id    BIGINT UNSIGNED NOT NULL,
  siswa_id    BIGINT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_kelas_siswa (kelas_id, siswa_id),
  CONSTRAINT fk_ks_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id),
  CONSTRAINT fk_ks_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id),
  INDEX idx_ks_siswa (siswa_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE mapel (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(100) NOT NULL,
  kkm         DECIMAL(5,2) NOT NULL DEFAULT 75.00,      -- contoh, sesuaikan kebijakan sekolah
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  deleted_at  TIMESTAMP NULL
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE penugasan (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  guru_id     BIGINT UNSIGNED NOT NULL,
  kelas_id    BIGINT UNSIGNED NOT NULL,
  mapel_id    BIGINT UNSIGNED NULL,
  jenis       ENUM('mapel','wali_kelas') NOT NULL,
  ditugaskan_oleh BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  -- Satu kelas hanya 1 wali kelas; satu mapel di satu kelas hanya 1 guru
  kunci_wali  INT UNSIGNED GENERATED ALWAYS AS (IF(jenis='wali_kelas', kelas_id, NULL)) STORED,
  kunci_mapel VARCHAR(30)  GENERATED ALWAYS AS (IF(jenis='mapel', CONCAT(kelas_id,'-',mapel_id), NULL)) STORED,
  UNIQUE KEY uq_wali  (kunci_wali),
  UNIQUE KEY uq_mapel (kunci_mapel),
  CONSTRAINT fk_pen_guru  FOREIGN KEY (guru_id)  REFERENCES guru(id),
  CONSTRAINT fk_pen_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id),
  CONSTRAINT fk_pen_mapel FOREIGN KEY (mapel_id) REFERENCES mapel(id),
  CONSTRAINT fk_pen_oleh  FOREIGN KEY (ditugaskan_oleh) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_pen_jenis CHECK (
    (jenis='mapel' AND mapel_id IS NOT NULL) OR (jenis='wali_kelas' AND mapel_id IS NULL)
  ),
  INDEX idx_pen_guru (guru_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE jadwal (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  penugasan_id  BIGINT UNSIGNED NOT NULL,
  hari          TINYINT UNSIGNED NOT NULL,              -- 1=Senin ... 7=Minggu
  jam_ke        TINYINT UNSIGNED NOT NULL,
  jam_mulai     TIME NOT NULL,
  jam_selesai   TIME NOT NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_jadwal (kelas_id, hari, jam_ke),
  CONSTRAINT fk_jadwal_kelas     FOREIGN KEY (kelas_id)     REFERENCES kelas(id),
  CONSTRAINT fk_jadwal_penugasan FOREIGN KEY (penugasan_id) REFERENCES penugasan(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE sesi_absen (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  jadwal_id     BIGINT UNSIGNED NULL,
  guru_id       BIGINT UNSIGNED NOT NULL,               -- pembuat QR
  jenis         ENUM('masuk','pulang') NOT NULL,
  tanggal       DATE NOT NULL,
  rahasia       VARCHAR(64) NOT NULL,                   -- dasar token QR dinamis
  token_detik   SMALLINT UNSIGNED NOT NULL DEFAULT 30,  -- masa berlaku token QR berputar
  berlaku_dari  DATETIME NOT NULL,
  berlaku_sampai DATETIME NOT NULL,                     -- countdown "Masa berlaku 05:00" di layar guru
  status        ENUM('aktif','ditutup') NOT NULL DEFAULT 'aktif',
  ditutup_pada  DATETIME NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_sesi (kelas_id, tanggal, jenis),
  CONSTRAINT fk_sesi_kelas  FOREIGN KEY (kelas_id)  REFERENCES kelas(id),
  CONSTRAINT fk_sesi_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON DELETE SET NULL,
  CONSTRAINT fk_sesi_guru   FOREIGN KEY (guru_id)   REFERENCES guru(id),
  INDEX idx_sesi_tanggal (tanggal)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE absensi (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sesi_id       BIGINT UNSIGNED NOT NULL,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  jenis         ENUM('masuk','pulang') NOT NULL,        -- salinan dari sesi, untuk unique & query cepat
  tanggal       DATE NOT NULL,
  waktu_scan    DATETIME NOT NULL,
  ketepatan     ENUM('tepat_waktu','terlambat') NOT NULL DEFAULT 'tepat_waktu', -- dihitung dari batas terlambat (pengaturan)
  sumber        ENUM('qr','manual') NOT NULL DEFAULT 'qr',
  latitude      DECIMAL(10,7) NULL,
  longitude     DECIMAL(10,7) NULL,
  jarak_meter   SMALLINT UNSIGNED NULL,                 -- jarak ke titik sekolah saat scan
  info_perangkat VARCHAR(255) NULL,
  dicatat_oleh  BIGINT UNSIGNED NULL,                   -- diisi jika sumber = manual
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_absensi (siswa_id, tanggal, jenis),     -- cegah scan ganda
  CONSTRAINT fk_abs_sesi  FOREIGN KEY (sesi_id)      REFERENCES sesi_absen(id),
  CONSTRAINT fk_abs_siswa FOREIGN KEY (siswa_id)     REFERENCES siswa(id),
  CONSTRAINT fk_abs_oleh  FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_abs_sesi (sesi_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE scan_gagal (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id    BIGINT UNSIGNED NULL,
  sesi_id     BIGINT UNSIGNED NULL,
  alasan      ENUM('kedaluwarsa','salah_kelas','duplikat','token_tidak_valid','sesi_ditutup','di_luar_radius','lainnya') NOT NULL,
  keterangan  VARCHAR(255) NULL,
  latitude    DECIMAL(10,7) NULL,
  longitude   DECIMAL(10,7) NULL,
  jarak_meter SMALLINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL,
  INDEX idx_sg_siswa (siswa_id),
  INDEX idx_sg_sesi (sesi_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE koreksi_absensi (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  tanggal       DATE NOT NULL,
  jenis         ENUM('masuk','pulang','status_harian') NOT NULL,
  nilai_lama    VARCHAR(60) NULL,
  nilai_baru    VARCHAR(60) NULL,
  alasan        TEXT NOT NULL,
  dikoreksi_oleh BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  CONSTRAINT fk_ka_siswa FOREIGN KEY (siswa_id)       REFERENCES siswa(id),
  CONSTRAINT fk_ka_oleh  FOREIGN KEY (dikoreksi_oleh) REFERENCES users(id),
  INDEX idx_ka_siswa (siswa_id, tanggal)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE izin (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id        BIGINT UNSIGNED NOT NULL,
  jenis           ENUM('izin','sakit') NOT NULL,
  tanggal_mulai   DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,                        -- form mobile 1 tanggal => sama dengan tanggal_mulai
  alasan          TEXT NOT NULL,
  lampiran        VARCHAR(255) NULL,
  status          ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  diputuskan_oleh BIGINT UNSIGNED NULL,
  diputuskan_pada DATETIME NULL,
  catatan_keputusan TEXT NULL,
  created_at      TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_izin_siswa FOREIGN KEY (siswa_id)        REFERENCES siswa(id),
  CONSTRAINT fk_izin_oleh  FOREIGN KEY (diputuskan_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_izin_status (status),
  INDEX idx_izin_siswa (siswa_id, tanggal_mulai)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE absensi_harian (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  tanggal       DATE NOT NULL,
  absen_masuk_id  BIGINT UNSIGNED NULL,
  absen_pulang_id BIGINT UNSIGNED NULL,
  kelengkapan   ENUM('belum_tercatat','masuk_tercatat','pulang_tercatat','lengkap') NOT NULL DEFAULT 'belum_tercatat',
  status_akhir  ENUM('hadir','terlambat','izin','sakit','alpa','belum_ditentukan') NOT NULL DEFAULT 'belum_ditentukan',
  izin_id       BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_harian (siswa_id, tanggal),
  CONSTRAINT fk_ah_siswa  FOREIGN KEY (siswa_id)        REFERENCES siswa(id),
  CONSTRAINT fk_ah_kelas  FOREIGN KEY (kelas_id)        REFERENCES kelas(id),
  CONSTRAINT fk_ah_masuk  FOREIGN KEY (absen_masuk_id)  REFERENCES absensi(id) ON DELETE SET NULL,
  CONSTRAINT fk_ah_pulang FOREIGN KEY (absen_pulang_id) REFERENCES absensi(id) ON DELETE SET NULL,
  CONSTRAINT fk_ah_izin   FOREIGN KEY (izin_id)         REFERENCES izin(id)    ON DELETE SET NULL,
  INDEX idx_ah_kelas_tanggal (kelas_id, tanggal)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE komponen_nilai (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  semester_id BIGINT UNSIGNED NOT NULL,
  mapel_id    BIGINT UNSIGNED NOT NULL,
  tingkat     TINYINT UNSIGNED NOT NULL,
  nama        VARCHAR(60) NOT NULL,
  bobot       DECIMAL(5,2) NOT NULL,                    -- total bobot per mapel = 100 (dicek aplikasi)
  wajib       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_komponen (semester_id, mapel_id, tingkat, nama),
  CONSTRAINT fk_kn_semester FOREIGN KEY (semester_id) REFERENCES semester(id),
  CONSTRAINT fk_kn_mapel    FOREIGN KEY (mapel_id)    REFERENCES mapel(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE pengiriman_nilai (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  mapel_id      BIGINT UNSIGNED NOT NULL,
  semester_id   BIGINT UNSIGNED NOT NULL,
  guru_id       BIGINT UNSIGNED NOT NULL,
  status        ENUM('draf','dikirim','dalam_pemeriksaan','perlu_perbaikan','lengkap') NOT NULL DEFAULT 'draf',
  dikirim_pada  DATETIME NULL,
  catatan_pemeriksa TEXT NULL,
  diperiksa_oleh BIGINT UNSIGNED NULL,
  diperiksa_pada DATETIME NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_pengiriman (kelas_id, mapel_id, semester_id),
  CONSTRAINT fk_pn_kelas    FOREIGN KEY (kelas_id)    REFERENCES kelas(id),
  CONSTRAINT fk_pn_mapel    FOREIGN KEY (mapel_id)    REFERENCES mapel(id),
  CONSTRAINT fk_pn_semester FOREIGN KEY (semester_id) REFERENCES semester(id),
  CONSTRAINT fk_pn_guru     FOREIGN KEY (guru_id)     REFERENCES guru(id),
  CONSTRAINT fk_pn_periksa  FOREIGN KEY (diperiksa_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE nilai (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  mapel_id      BIGINT UNSIGNED NOT NULL,
  semester_id   BIGINT UNSIGNED NOT NULL,
  komponen_id   BIGINT UNSIGNED NOT NULL,
  angka         DECIMAL(5,2) NULL,
  diinput_oleh  BIGINT UNSIGNED NOT NULL,               -- user penginput terakhir
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_nilai (siswa_id, mapel_id, semester_id, komponen_id),
  CONSTRAINT fk_nilai_siswa    FOREIGN KEY (siswa_id)     REFERENCES siswa(id),
  CONSTRAINT fk_nilai_kelas    FOREIGN KEY (kelas_id)     REFERENCES kelas(id),
  CONSTRAINT fk_nilai_mapel    FOREIGN KEY (mapel_id)     REFERENCES mapel(id),
  CONSTRAINT fk_nilai_semester FOREIGN KEY (semester_id)  REFERENCES semester(id),
  CONSTRAINT fk_nilai_komponen FOREIGN KEY (komponen_id)  REFERENCES komponen_nilai(id),
  CONSTRAINT fk_nilai_oleh     FOREIGN KEY (diinput_oleh) REFERENCES users(id),
  CONSTRAINT chk_nilai_angka CHECK (angka IS NULL OR (angka >= 0 AND angka <= 100)),
  INDEX idx_nilai_kelas_sem (kelas_id, semester_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE riwayat_nilai (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nilai_id      BIGINT UNSIGNED NULL,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  mapel_id      BIGINT UNSIGNED NOT NULL,
  semester_id   BIGINT UNSIGNED NOT NULL,
  komponen_id   BIGINT UNSIGNED NOT NULL,
  angka_lama    DECIMAL(5,2) NULL,
  angka_baru    DECIMAL(5,2) NULL,
  diubah_oleh   BIGINT UNSIGNED NOT NULL,
  sebagai       ENUM('guru_mapel','wali_kelas') NOT NULL,   -- admin sengaja tidak termasuk
  alasan        TEXT NOT NULL,
  ip_address    VARCHAR(45) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rn_nilai    FOREIGN KEY (nilai_id)    REFERENCES nilai(id) ON DELETE SET NULL,
  CONSTRAINT fk_rn_siswa    FOREIGN KEY (siswa_id)    REFERENCES siswa(id),
  CONSTRAINT fk_rn_kelas    FOREIGN KEY (kelas_id)    REFERENCES kelas(id),
  CONSTRAINT fk_rn_mapel    FOREIGN KEY (mapel_id)    REFERENCES mapel(id),
  CONSTRAINT fk_rn_semester FOREIGN KEY (semester_id) REFERENCES semester(id),
  CONSTRAINT fk_rn_komponen FOREIGN KEY (komponen_id) REFERENCES komponen_nilai(id),
  CONSTRAINT fk_rn_oleh     FOREIGN KEY (diubah_oleh) REFERENCES users(id),
  INDEX idx_rn_siswa (siswa_id, semester_id),
  INDEX idx_rn_waktu (created_at)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE rapor (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  siswa_id      BIGINT UNSIGNED NOT NULL,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  semester_id   BIGINT UNSIGNED NOT NULL,
  status        ENUM('belum_lengkap','siap_diperiksa','diterbitkan') NOT NULL DEFAULT 'belum_lengkap',
  versi_terbit  SMALLINT UNSIGNED NOT NULL DEFAULT 0,   -- 0 = belum pernah terbit
  catatan_wali  TEXT NULL,
  diterbitkan_oleh BIGINT UNSIGNED NULL,
  diterbitkan_pada DATETIME NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_rapor (siswa_id, semester_id),
  CONSTRAINT fk_rapor_siswa    FOREIGN KEY (siswa_id)    REFERENCES siswa(id),
  CONSTRAINT fk_rapor_kelas    FOREIGN KEY (kelas_id)    REFERENCES kelas(id),
  CONSTRAINT fk_rapor_semester FOREIGN KEY (semester_id) REFERENCES semester(id),
  CONSTRAINT fk_rapor_oleh     FOREIGN KEY (diterbitkan_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_rapor_kelas_sem (kelas_id, semester_id, status)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE versi_rapor (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  rapor_id      BIGINT UNSIGNED NOT NULL,
  versi         SMALLINT UNSIGNED NOT NULL,
  snapshot      JSON NOT NULL,                          -- identitas, nilai, absensi saat terbit
  file_pdf      VARCHAR(255) NULL,
  diterbitkan_oleh BIGINT UNSIGNED NOT NULL,
  diterbitkan_pada DATETIME NOT NULL,
  alasan_revisi TEXT NULL,                              -- wajib untuk versi > 1
  created_at    TIMESTAMP NULL,
  UNIQUE KEY uq_versi (rapor_id, versi),
  CONSTRAINT fk_vr_rapor FOREIGN KEY (rapor_id)         REFERENCES rapor(id),
  CONSTRAINT fk_vr_oleh  FOREIGN KEY (diterbitkan_oleh) REFERENCES users(id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE isi_rapor (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  versi_rapor_id BIGINT UNSIGNED NOT NULL,
  mapel_id      BIGINT UNSIGNED NOT NULL,
  nama_mapel    VARCHAR(100) NOT NULL,                  -- disalin agar dokumen terbit tetap utuh
  nilai_akhir   DECIMAL(5,2) NOT NULL,
  predikat      VARCHAR(5) NULL,
  deskripsi     TEXT NULL,
  CONSTRAINT fk_ir_versi FOREIGN KEY (versi_rapor_id) REFERENCES versi_rapor(id),
  CONSTRAINT fk_ir_mapel FOREIGN KEY (mapel_id)       REFERENCES mapel(id),
  INDEX idx_ir_versi (versi_rapor_id)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE notifikasi (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  tipe        VARCHAR(50) NOT NULL,
  judul       VARCHAR(150) NOT NULL,
  isi         TEXT NULL,
  data        JSON NULL,
  dibaca_pada TIMESTAMP NULL,
  created_at  TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_notif_user (user_id, dibaca_pada)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE log_aktivitas (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NULL,
  aksi        VARCHAR(60) NOT NULL,                     -- login, buat_akun, putuskan_izin, terbitkan_rapor, ...
  objek_tipe  VARCHAR(60) NULL,
  objek_id    BIGINT UNSIGNED NULL,
  data_lama   JSON NULL,
  data_baru   JSON NULL,
  ip_address  VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_log_objek (objek_tipe, objek_id),
  INDEX idx_log_waktu (created_at)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE pengaturan (
  `kunci`     VARCHAR(80) PRIMARY KEY,
  `nilai`     TEXT NULL,
  keterangan  VARCHAR(255) NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE cadangan (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  jenis           ENUM('otomatis','manual') NOT NULL,
  nama_file       VARCHAR(255) NULL,
  ukuran_mb       DECIMAL(10,2) NULL,
  status          ENUM('berjalan','berhasil','gagal') NOT NULL DEFAULT 'berjalan',
  dibuat_oleh     BIGINT UNSIGNED NULL,                 -- NULL = dijadwalkan sistem
  diverifikasi_pada DATETIME NULL,                      -- tombol "Verifikasi"
  created_at      TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_cad_oleh FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_cad_waktu (created_at)
) ENGINE=InnoDB;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_rn_no_update BEFORE UPDATE ON riwayat_nilai
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'riwayat_nilai bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_rn_no_delete BEFORE DELETE ON riwayat_nilai
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'riwayat_nilai bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_log_no_update BEFORE UPDATE ON log_aktivitas
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'log_aktivitas bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_log_no_delete BEFORE DELETE ON log_aktivitas
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'log_aktivitas bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_ka_no_update BEFORE UPDATE ON koreksi_absensi
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'koreksi_absensi bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_ka_no_delete BEFORE DELETE ON koreksi_absensi
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'koreksi_absensi bersifat append-only';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_vr_no_update BEFORE UPDATE ON versi_rapor
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Versi rapor terbit tidak boleh diubah; buat versi baru';
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_vr_no_delete BEFORE DELETE ON versi_rapor
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Versi rapor terbit tidak boleh dihapus';
END
SQL);

        // Pengaturan awal dari file SQL sumber.
        DB::unprepared(<<<'SQL'
INSERT INTO pengaturan (`kunci`, `nilai`, keterangan, updated_at) VALUES
  ('sekolah.nama',                 'SMA Negeri 1 Rejoso', 'Nama sekolah (header portal & rapor)', NOW()),
  ('sekolah.latitude',             NULL,    'ISI: latitude titik pusat sekolah untuk validasi lokasi absensi', NOW()),
  ('sekolah.longitude',            NULL,    'ISI: longitude titik pusat sekolah', NOW()),
  ('sekolah.radius_meter',         '100',   'Radius area sekolah untuk absensi (contoh, sesuaikan)', NOW()),
  ('absensi.qr_token_detik',       '30',    'Masa berlaku token QR yang berputar (detik)', NOW()),
  ('absensi.qr_sesi_menit',        '5',     'Masa berlaku sesi QR sebelum ditutup (menit)', NOW()),
  ('absensi.masuk_buka',           '06:00', 'Jam QR masuk mulai bisa dipindai', NOW()),
  ('absensi.batas_terlambat',      '07:15', 'Scan masuk setelah jam ini = terlambat (sesuaikan kebijakan sekolah)', NOW()),
  ('absensi.izinkan_koreksi',      '1',     'Izinkan koreksi absensi manual oleh guru', NOW()),
  ('nilai.minimum',                '0',     'Nilai minimum', NOW()),
  ('nilai.maksimum',               '100',   'Nilai maksimum', NOW()),
  ('rapor.wajib_semua_mapel',      '1',     'Rapor tidak bisa terbit jika ada nilai wajib kosong', NOW()),
  ('keamanan.wajib_mfa_admin',     '1',     'Wajibkan kode verifikasi untuk login admin', NOW()),
  ('keamanan.timeout_sesi_menit',  '30',    'Keluar otomatis setelah tidak aktif (menit)', NOW()),
  ('keamanan.catat_data_sensitif', '1',     'Catat perubahan nilai, absensi, hak akses, rapor ke log aktivitas', NOW()),
  ('cadangan.retensi_hari',        '30',    'Lama backup disimpan (hari)', NOW());
SQL);
    }

    public function down(): void
    {
        foreach ([
            'trg_rn_no_update',
            'trg_rn_no_delete',
            'trg_log_no_update',
            'trg_log_no_delete',
            'trg_ka_no_update',
            'trg_ka_no_delete',
            'trg_vr_no_update',
            'trg_vr_no_delete',
        ] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS `' . $trigger . '`');
        }

        // Urutan dibalik agar foreign key tidak menghalangi penghapusan.
        foreach ([
            'cadangan',
            'pengaturan',
            'log_aktivitas',
            'notifikasi',
            'isi_rapor',
            'versi_rapor',
            'rapor',
            'riwayat_nilai',
            'nilai',
            'pengiriman_nilai',
            'komponen_nilai',
            'absensi_harian',
            'izin',
            'koreksi_absensi',
            'scan_gagal',
            'absensi',
            'sesi_absen',
            'jadwal',
            'penugasan',
            'mapel',
            'kelas_siswa',
            'siswa',
            'kelas',
            'guru',
            'semester',
            'tahun_ajaran',
            'kode_otp',
            'personal_access_tokens',
            'sessions',
            'password_reset_tokens',
            'users',
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
