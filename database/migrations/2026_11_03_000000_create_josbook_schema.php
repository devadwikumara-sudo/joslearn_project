<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET FOREIGN_KEY_CHECKS=0");

        DB::unprepared(<<<'JOSBOOK_SQL_1'
CREATE TABLE sekolah_identitas (
  id                  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  nama                VARCHAR(150) NOT NULL,
  npsn                VARCHAR(20)  NULL,
  alamat              VARCHAR(255) NULL,
  desa                VARCHAR(100) NULL,
  kecamatan           VARCHAR(100) NULL,
  kabupaten           VARCHAR(100) NULL,
  provinsi            VARCHAR(100) NULL,
  kode_pos            VARCHAR(10)  NULL,
  telepon             VARCHAR(30)  NULL,
  email               VARCHAR(120) NULL,
  nama_kepala_sekolah VARCHAR(150) NULL,
  nip_kepala_sekolah  VARCHAR(30)  NULL,
  logo                VARCHAR(255) NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  PRIMARY KEY (id),
  CONSTRAINT cek_identitas_hanya_satu_baris CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Identitas sekolah (nama, NPSN, alamat, kepala sekolah). Hanya 1 baris. Dipakai di kop PDF dan menu Pengaturan.'
JOSBOOK_SQL_1
        );

        DB::unprepared(<<<'JOSBOOK_SQL_2'
CREATE TABLE sekolah_pengaturan (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunci      VARCHAR(60)  NOT NULL,
  isi        VARCHAR(255) NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_pengaturan_kunci (kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pengaturan umum berbentuk kunci dan isi, misalnya semester yang sedang berjalan. Menu Pengaturan.'
JOSBOOK_SQL_2
        );

        DB::unprepared(<<<'JOSBOOK_SQL_3'
CREATE TABLE sekolah_tahun_ajaran (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       CHAR(9) NOT NULL,
  sedang_berjalan   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = tahun ajaran yang sedang berjalan',

  penanda_berjalan  TINYINT AS (IF(sedang_berjalan = 1, 1, NULL)) STORED COMMENT 'diisi otomatis, jangan diubah',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_tahun_ajaran_nama (nama),
  UNIQUE KEY unik_hanya_satu_berjalan (penanda_berjalan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daftar tahun ajaran (contoh 2025/2026) dan penanda mana yang sedang berjalan.'
JOSBOOK_SQL_3
        );

        DB::unprepared(<<<'JOSBOOK_SQL_4'
CREATE TABLE sekolah_kelas (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       VARCHAR(30) NOT NULL,
  tingkat    ENUM('X','XI','XII') NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_kelas_nama (nama),
  KEY index_kelas_tingkat (tingkat),

  CONSTRAINT cek_kelas_format CHECK (nama LIKE CONCAT(tingkat, '-%'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daftar kelas atau rombel (contoh X-1, XII-9). Menu Master Kelas.'
JOSBOOK_SQL_4
        );

        DB::unprepared(<<<'JOSBOOK_SQL_5'
CREATE TABLE sekolah_mapel (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode       CHAR(3) NOT NULL COMMENT 'kode 3 huruf, contoh mtk',
  nama       VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_mapel_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daftar mata pelajaran dengan kode 3 huruf. Menu Master Mapel.'
JOSBOOK_SQL_5
        );

        DB::unprepared(<<<'JOSBOOK_SQL_6'
CREATE TABLE sekolah_mapel_per_tingkat (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tingkat    ENUM('X','XI','XII') NOT NULL,
  mapel_id   INT UNSIGNED NOT NULL,
  kelompok_mapel   VARCHAR(60) NOT NULL COMMENT 'contoh: I. Mata Pelajaran Wajib',
  urutan     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY unik_kurikulum (tingkat, mapel_id),
  CONSTRAINT fk_kurikulum_mapel FOREIGN KEY (mapel_id) REFERENCES sekolah_mapel (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mapel apa saja yang dipelajari di kelas X, XI, dan XII. Menentukan baris pada tabel nilai di PDF.'
JOSBOOK_SQL_6
        );

        DB::unprepared(<<<'JOSBOOK_SQL_7'
CREATE TABLE catatan_tempat_sampah (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  keterangan     VARCHAR(255) NOT NULL COMMENT 'contoh: Hapus 312 siswa tahun ajaran 2025/2026',
  jenis          ENUM('siswa','guru','riwayat_kelas') NOT NULL,
  dihapus_oleh   BIGINT UNSIGNED NULL COMMENT 'pengguna_id (tanpa FK agar tidak saling mengunci)',
  dipulihkan_at  TIMESTAMP NULL,
  created_at     TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catatan penghapusan data agar bisa dipulihkan sekaligus. Menu Tempat Sampah.'
JOSBOOK_SQL_7
        );

        DB::unprepared(<<<'JOSBOOK_SQL_8'
CREATE TABLE guru_biodata (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama                VARCHAR(150) NOT NULL COMMENT 'sudah termasuk gelar',
  nip                 VARCHAR(30)  NULL COMMENT 'tanpa spasi; guru honorer boleh kosong',
  nuptk               VARCHAR(20)  NULL,
  nik                 CHAR(16)     NULL,
  jenis_kelamin       ENUM('L','P') NULL,
  tempat_lahir        VARCHAR(100) NULL,
  tanggal_lahir       DATE NULL,
  pendidikan_terakhir VARCHAR(30) NULL,
  status_kepegawaian  VARCHAR(30) NULL COMMENT 'PNS, PPPK, GTT, Honorer',
  mulai_bertugas      SMALLINT UNSIGNED NULL COMMENT 'tahun, contoh 2008',
  nomor_hp            VARCHAR(30) NULL,
  email               VARCHAR(120) NULL,
  alamat              VARCHAR(255) NULL,
  foto                VARCHAR(255) NULL,
  tempat_sampah_id    BIGINT UNSIGNED NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  deleted_at          TIMESTAMP NULL,

  nip_aktif           VARCHAR(30) AS (IF(deleted_at IS NULL, nip, NULL)) STORED COMMENT 'otomatis: NIP jika belum dihapus',
  nuptk_aktif         VARCHAR(20) AS (IF(deleted_at IS NULL, nuptk, NULL)) STORED COMMENT 'otomatis: NUPTK jika belum dihapus',
  PRIMARY KEY (id),
  UNIQUE KEY unik_guru_nip_aktif (nip_aktif),
  UNIQUE KEY unik_guru_nuptk_aktif (nuptk_aktif),
  KEY index_guru_nip (nip),
  KEY index_guru_nama (nama),
  CONSTRAINT fk_guru_sampah FOREIGN KEY (tempat_sampah_id) REFERENCES catatan_tempat_sampah (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Biodata guru. Menu Data Guru dan Import Guru.'
JOSBOOK_SQL_8
        );

        DB::unprepared(<<<'JOSBOOK_SQL_9'
CREATE TABLE guru_wali_kelas (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tahun_ajaran_id INT UNSIGNED NOT NULL,
  kelas_id        INT UNSIGNED NOT NULL,
  guru_id         BIGINT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_wali_per_kelas (tahun_ajaran_id, kelas_id),
  UNIQUE KEY unik_wali_per_guru  (tahun_ajaran_id, guru_id),
  CONSTRAINT fk_wali_tahun FOREIGN KEY (tahun_ajaran_id) REFERENCES sekolah_tahun_ajaran (id) ON DELETE CASCADE,
  CONSTRAINT fk_wali_kelas FOREIGN KEY (kelas_id)        REFERENCES sekolah_kelas (id)        ON DELETE CASCADE,
  CONSTRAINT fk_wali_guru  FOREIGN KEY (guru_id)         REFERENCES guru_biodata (id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Siapa wali kelas tiap kelas pada tiap tahun ajaran. Satu kelas satu wali, satu guru satu kelas.'
JOSBOOK_SQL_9
        );

        DB::unprepared(<<<'JOSBOOK_SQL_10'
CREATE TABLE guru_mengajar (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tahun_ajaran_id INT UNSIGNED NOT NULL,
  guru_id         BIGINT UNSIGNED NOT NULL,
  kelas_id        INT UNSIGNED NOT NULL,
  mapel_id        INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_satu_guru_mapel_kelas_tahun (tahun_ajaran_id, kelas_id, mapel_id),
  KEY index_mengajar_guru (guru_id),
  KEY index_mengajar_kelas_mapel (tahun_ajaran_id, kelas_id, mapel_id),
  CONSTRAINT fk_mengajar_tahun FOREIGN KEY (tahun_ajaran_id) REFERENCES sekolah_tahun_ajaran (id) ON DELETE CASCADE,
  CONSTRAINT fk_mengajar_guru  FOREIGN KEY (guru_id)         REFERENCES guru_biodata (id)         ON DELETE CASCADE,
  CONSTRAINT fk_mengajar_kelas FOREIGN KEY (kelas_id)        REFERENCES sekolah_kelas (id)        ON DELETE CASCADE,
  CONSTRAINT fk_mengajar_mapel FOREIGN KEY (mapel_id)        REFERENCES sekolah_mapel (id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Guru mengajar mapel apa di kelas mana pada tahun ajaran tertentu. Dipakai untuk membatasi hak input nilai guru.'
JOSBOOK_SQL_10
        );

        DB::unprepared(<<<'JOSBOOK_SQL_11'
CREATE TABLE akun_pengguna (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  peran                 ENUM('admin','guru','siswa') NOT NULL COMMENT 'admin, guru, atau siswa',
  username              VARCHAR(50)  NOT NULL COMMENT 'admin: bebas, guru: NIP, siswa: NISN',
  email                 VARCHAR(120) NULL,
  email_diverifikasi_at TIMESTAMP NULL,
  password              VARCHAR(255) NOT NULL COMMENT 'kata sandi (hash). Nama kolom bawaan Laravel',
  wajib_ganti_password  TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = harus ganti kata sandi saat login pertama',
  aktif                 TINYINT(1) NOT NULL DEFAULT 1,
  guru_id               BIGINT UNSIGNED NULL COMMENT 'terisi jika peran = guru',
  siswa_id              BIGINT UNSIGNED NULL COMMENT 'terisi jika peran = siswa',
  terakhir_login_at     TIMESTAMP NULL,
  remember_token        VARCHAR(100) NULL,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_pengguna_username (username),
  UNIQUE KEY unik_pengguna_email (email),
  UNIQUE KEY unik_pengguna_guru (guru_id),
  UNIQUE KEY unik_pengguna_siswa (siswa_id),
  CONSTRAINT fk_pengguna_guru FOREIGN KEY (guru_id) REFERENCES guru_biodata (id) ON DELETE CASCADE,
  CONSTRAINT cek_pengguna_relasi_peran CHECK (
    (peran = 'admin' AND guru_id IS NULL AND siswa_id IS NULL) OR
    (peran = 'guru' AND guru_id IS NOT NULL AND siswa_id IS NULL) OR
    (peran = 'siswa' AND siswa_id IS NOT NULL AND guru_id IS NULL)
  )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Semua akun login: admin, guru, dan siswa. Guru memakai NIP, siswa memakai NISN sebagai username.'
JOSBOOK_SQL_11
        );

        DB::unprepared(<<<'JOSBOOK_SQL_12'
CREATE TABLE akun_kode_otp (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(120) NOT NULL,
  tujuan        ENUM('daftar','lupa_password') NOT NULL,
  kode_hash     VARCHAR(255) NOT NULL,
  percobaan     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  berlaku_sampai TIMESTAMP NOT NULL COMMENT 'setelah waktu ini kode tidak berlaku',
  dipakai_at    TIMESTAMP NULL,
  created_at    TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_otp_email (email, tujuan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kode OTP lewat email untuk daftar akun dan lupa kata sandi. Kode disimpan dalam bentuk hash.'
JOSBOOK_SQL_12
        );

        DB::unprepared(<<<'JOSBOOK_SQL_13'
CREATE TABLE akun_perangkat_login (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id       BIGINT UNSIGNED NOT NULL,
  token_id          BIGINT UNSIGNED NULL COMMENT 'id token di tabel bawaan personal_access_tokens',
  nama_perangkat    VARCHAR(120) NULL,
  alamat_ip         VARCHAR(45)  NULL,
  terakhir_aktif_at TIMESTAMP NULL,
  created_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_sesi_pengguna (pengguna_id),
  CONSTRAINT fk_sesi_pengguna FOREIGN KEY (pengguna_id) REFERENCES akun_pengguna (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Perangkat yang sedang login di aplikasi mobile. Layar Sesi Aktif.'
JOSBOOK_SQL_13
        );

        DB::unprepared(<<<'JOSBOOK_SQL_14'
CREATE TABLE akun_notifikasi (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id BIGINT UNSIGNED NOT NULL,
  judul       VARCHAR(150) NOT NULL,
  isi         VARCHAR(500) NULL,
  jenis       ENUM('info','peringatan') NOT NULL DEFAULT 'info',
  dibaca_at   TIMESTAMP NULL,
  created_at  TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_notifikasi_pengguna (pengguna_id, dibaca_at),
  CONSTRAINT fk_notifikasi_pengguna FOREIGN KEY (pengguna_id) REFERENCES akun_pengguna (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pemberitahuan untuk siswa di aplikasi mobile (contoh PDF berhasil diunduh).'
JOSBOOK_SQL_14
        );

        DB::unprepared(<<<'JOSBOOK_SQL_15'
CREATE TABLE siswa_biodata (
  id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  nama_lengkap              VARCHAR(150) NOT NULL,
  nama_panggilan            VARCHAR(64)  NULL,
  nisn                      VARCHAR(10)  NOT NULL COMMENT '[V2] tepat 10 digit (dijaga CHECK), unik di antara siswa yang tidak dihapus, simpan sebagai teks agar nol di depan tidak hilang',
  nomor_induk                       VARCHAR(64)  NULL COMMENT 'nomor induk sekolah (NIS), di Excel sering berjudul Induk atau NIPD',
  jenis_kelamin             ENUM('L','P') NULL,
  tempat_lahir              VARCHAR(100) NULL,
  tanggal_lahir             DATE NULL,
  agama                     VARCHAR(64)  NULL,
  kewarganegaraan           VARCHAR(64)  NULL,
  nik                       CHAR(16)     NULL COMMENT '16 digit',
  anak_ke                   TINYINT UNSIGNED NULL,
  jumlah_saudara            VARCHAR(64)  NULL COMMENT 'contoh: 1 (Satu)',
  status_yatim              VARCHAR(64)  NULL,
  bahasa_sehari_hari        VARCHAR(64)  NULL,

  alamat_jalan              VARCHAR(255) NULL,
  alamat_rt_rw              VARCHAR(64)  NULL,
  alamat_desa               VARCHAR(100) NULL,
  alamat_kecamatan          VARCHAR(100) NULL,
  alamat_kabupaten          VARCHAR(100) NULL,
  alamat_provinsi           VARCHAR(100) NULL,
  alamat_kode_pos           VARCHAR(10)  NULL,
  nomor_hp                  VARCHAR(64)  NULL,
  tempat_tinggal            VARCHAR(64)  NULL,
  jarak_ke_sekolah_km       DECIMAL(6,2) NULL,

  golongan_darah            VARCHAR(5)   NULL,
  penyakit                  VARCHAR(255) NULL,
  kelainan_fisik            VARCHAR(255) NULL,
  tinggi_badan_cm           SMALLINT UNSIGNED NULL,
  berat_badan_kg            SMALLINT UNSIGNED NULL,

  sekolah_asal              VARCHAR(150) NULL,
  tanggal_ijazah_smp        VARCHAR(64)  NULL COMMENT 'teks agar tampil persis seperti di PDF (contoh 09 Juni 2023)',
  nomor_ijazah_smp          VARCHAR(64)  NULL,
  lama_belajar_tahun        TINYINT UNSIGNED NULL,
  jenis_registrasi          VARCHAR(64)  NULL COMMENT 'Siswa Baru / Pindahan',
  pindahan_dari_sekolah     VARCHAR(150) NULL,
  alasan_pindah             VARCHAR(255) NULL,
  kelas_diterima            VARCHAR(10)  NULL COMMENT 'contoh: X',
  rombel_diterima           VARCHAR(10)  NULL,
  tanggal_diterima          DATE NULL,

  nama_ayah                 VARCHAR(150) NULL,
  agama_ayah                VARCHAR(64)  NULL,
  kewarganegaraan_ayah      VARCHAR(64)  NULL,
  pendidikan_ayah           VARCHAR(64)  NULL,
  pekerjaan_ayah            VARCHAR(100) NULL,
  penghasilan_ayah          VARCHAR(64)  NULL COMMENT 'pilihan rentang penghasilan, bukan angka',
  nomor_hp_ayah             VARCHAR(64)  NULL,
  keadaan_ayah              VARCHAR(64)  NULL,

  nama_ibu                  VARCHAR(150) NULL,
  agama_ibu                 VARCHAR(64)  NULL,
  kewarganegaraan_ibu       VARCHAR(64)  NULL,
  pendidikan_ibu            VARCHAR(64)  NULL,
  pekerjaan_ibu             VARCHAR(100) NULL,
  penghasilan_ibu           VARCHAR(64)  NULL COMMENT 'pilihan rentang penghasilan, bukan angka',
  nomor_hp_ibu              VARCHAR(64)  NULL,
  keadaan_ibu               VARCHAR(64)  NULL,

  ortu_alamat_jalan         VARCHAR(255) NULL,
  ortu_alamat_rt_rw         VARCHAR(64)  NULL,
  ortu_alamat_desa          VARCHAR(100) NULL,
  ortu_alamat_kecamatan     VARCHAR(100) NULL,
  ortu_alamat_kabupaten     VARCHAR(100) NULL,
  ortu_alamat_provinsi      VARCHAR(100) NULL,
  ortu_alamat_kode_pos      VARCHAR(10)  NULL,

  nama_wali                 VARCHAR(150) NULL,
  agama_wali                VARCHAR(64)  NULL,
  kewarganegaraan_wali      VARCHAR(64)  NULL,
  pendidikan_wali           VARCHAR(64)  NULL,
  pekerjaan_wali            VARCHAR(100) NULL,
  penghasilan_wali          VARCHAR(64)  NULL COMMENT 'pilihan rentang penghasilan, bukan angka',
  nomor_hp_wali             VARCHAR(64)  NULL,
  wali_alamat_jalan         VARCHAR(255) NULL,
  wali_alamat_rt_rw         VARCHAR(64)  NULL,
  wali_alamat_desa          VARCHAR(100) NULL,
  wali_alamat_kecamatan     VARCHAR(100) NULL,
  wali_alamat_kabupaten     VARCHAR(100) NULL,
  wali_alamat_provinsi      VARCHAR(100) NULL,
  wali_alamat_kode_pos      VARCHAR(10)  NULL,

  tanggal_keluar            DATE NULL,
  kelas_keluar              VARCHAR(64)  NULL,
  alasan_keluar             VARCHAR(255) NULL,
  sekolah_tujuan            VARCHAR(150) NULL,
  nomor_peserta_psaj        VARCHAR(64)  NULL,
  nomor_skl                 VARCHAR(64)  NULL,
  nomor_ijazah_sma          VARCHAR(64)  NULL,
  tanggal_ijazah_sma        DATE NULL,
  nomor_transkrip           VARCHAR(64)  NULL,
  tahun_lulus               CHAR(4)      NULL,
  kegiatan_setelah_lulus    VARCHAR(255) NULL COMMENT 'terisi otomatis dari siswa_pelacakan_alumni yang terbaru',
  catatan                   TEXT NULL,

  kk_ada                    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = dokumen sudah ada',
  akta_ada                  TINYINT(1) NOT NULL DEFAULT 0,
  ijazah_smp_ada            TINYINT(1) NOT NULL DEFAULT 0,
  foto_ada                  TINYINT(1) NOT NULL DEFAULT 0,
  surat_pindah_ada          TINYINT(1) NOT NULL DEFAULT 0,
  foto                      VARCHAR(255) NULL COMMENT 'alamat file pas foto 3x4',

  tempat_sampah_id          BIGINT UNSIGNED NULL,
  created_at                TIMESTAMP NULL,
  updated_at                TIMESTAMP NULL,
  deleted_at                TIMESTAMP NULL,


  nisn_aktif                VARCHAR(10) AS (IF(deleted_at IS NULL, nisn, NULL)) STORED COMMENT 'otomatis: NISN jika belum dihapus',
  nomor_induk_aktif         VARCHAR(64) AS (IF(deleted_at IS NULL, nomor_induk, NULL)) STORED COMMENT 'otomatis: nomor induk jika belum dihapus',

  PRIMARY KEY (id),
  UNIQUE KEY unik_siswa_nisn_aktif (nisn_aktif),
  UNIQUE KEY unik_siswa_nomor_induk_aktif (nomor_induk_aktif),
  KEY index_siswa_nisn (nisn),
  KEY index_siswa_nomor_induk (nomor_induk),
  KEY index_siswa_nama (nama_lengkap),
  KEY index_siswa_nik (nik),

  CONSTRAINT cek_siswa_nisn  CHECK (nisn REGEXP '^[0-9]{10}$'),
  CONSTRAINT cek_siswa_nik   CHECK (nik IS NULL OR nik REGEXP '^[0-9]{16}$'),
  CONSTRAINT cek_siswa_tahun_lulus CHECK (tahun_lulus IS NULL OR tahun_lulus REGEXP '^[0-9]{4}$'),
  CONSTRAINT cek_siswa_tanggal_keluar CHECK (tanggal_keluar IS NULL OR tanggal_diterima IS NULL OR tanggal_keluar >= tanggal_diterima),
  CONSTRAINT fk_siswa_sampah FOREIGN KEY (tempat_sampah_id) REFERENCES catatan_tempat_sampah (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Biodata siswa: satu baris satu orang, berlaku seumur hidup. Isi register A sampai I di PDF. Menu Data Siswa.'
JOSBOOK_SQL_15
        );

        DB::unprepared(<<<'JOSBOOK_SQL_16'
ALTER TABLE akun_pengguna
  ADD CONSTRAINT fk_pengguna_siswa FOREIGN KEY (siswa_id) REFERENCES siswa_biodata (id) ON DELETE CASCADE
JOSBOOK_SQL_16
        );

        DB::unprepared(<<<'JOSBOOK_SQL_17'
CREATE TABLE siswa_berkas (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id    BIGINT UNSIGNED NOT NULL,
  jenis       ENUM('kk','akta','ijazah_smp','foto','surat_pindah') NOT NULL,
  nama_file   VARCHAR(255) NOT NULL,
  lokasi      VARCHAR(255) NOT NULL COMMENT 'alamat file di penyimpanan',
  tipe_file   VARCHAR(80)  NULL,
  ukuran_byte INT UNSIGNED NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_berkas_jenis (siswa_id, jenis),
  CONSTRAINT fk_berkas_siswa FOREIGN KEY (siswa_id) REFERENCES siswa_biodata (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='File lampiran siswa: KK, akta, ijazah SMP, pas foto, surat pindah.'
JOSBOOK_SQL_17
        );

        DB::unprepared(<<<'JOSBOOK_SQL_18'
CREATE TABLE siswa_riwayat_kelas (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id          BIGINT UNSIGNED NOT NULL,
  tahun_ajaran_id   INT UNSIGNED NOT NULL,
  kelas_id          INT UNSIGNED NOT NULL,
  status            ENUM('Aktif','Pindah keluar','Keluar','Lulus') NOT NULL DEFAULT 'Aktif' COMMENT 'status siswa pada tahun ajaran ini',

  keputusan_kenaikan         ENUM('NAIK','TIDAK NAIK','LULUS','TIDAK LULUS') NULL COMMENT 'keputusan di akhir tahun',
  kelas_tujuan_id   INT UNSIGNED NULL COMMENT 'naik ke kelas apa',
  tanggal_keputusan_kenaikan DATE NULL,
  wali_guru_id      BIGINT UNSIGNED NULL,
  nama_wali_kelas   VARCHAR(150) NULL COMMENT 'disalin agar PDF tahun lama tidak berubah saat wali kelas berganti',
  nip_wali_kelas    VARCHAR(30)  NULL,
  tempat_sampah_id  BIGINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  deleted_at        TIMESTAMP NULL,

  siswa_tahun_aktif VARCHAR(30) AS (IF(deleted_at IS NULL, CONCAT(siswa_id, '-', tahun_ajaran_id), NULL)) VIRTUAL COMMENT 'otomatis: penanda unik siswa dan tahun ajaran',
  PRIMARY KEY (id),
  UNIQUE KEY unik_siswa_per_tahun_aktif (siswa_tahun_aktif),
  KEY index_riwayat_siswa (siswa_id, tahun_ajaran_id),
  KEY index_riwayat_kelas (tahun_ajaran_id, kelas_id),
  KEY index_riwayat_status (status),
  CONSTRAINT fk_riwayat_siswa  FOREIGN KEY (siswa_id)         REFERENCES siswa_biodata (id)        ON DELETE CASCADE,
  CONSTRAINT fk_riwayat_tahun  FOREIGN KEY (tahun_ajaran_id)  REFERENCES sekolah_tahun_ajaran (id) ON DELETE RESTRICT,
  CONSTRAINT fk_riwayat_kelas  FOREIGN KEY (kelas_id)         REFERENCES sekolah_kelas (id)        ON DELETE RESTRICT,
  CONSTRAINT fk_riwayat_tujuan FOREIGN KEY (kelas_tujuan_id)  REFERENCES sekolah_kelas (id)        ON DELETE SET NULL,
  CONSTRAINT fk_riwayat_wali   FOREIGN KEY (wali_guru_id)     REFERENCES guru_biodata (id)         ON DELETE SET NULL,
  CONSTRAINT fk_riwayat_sampah FOREIGN KEY (tempat_sampah_id) REFERENCES catatan_tempat_sampah (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Siswa ada di kelas apa pada tahun ajaran apa, statusnya, dan keputusan kenaikan kelasnya. Satu baris per siswa per tahun.'
JOSBOOK_SQL_18
        );

        DB::unprepared(<<<'JOSBOOK_SQL_19'
CREATE TABLE siswa_riwayat_mutasi (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id          BIGINT UNSIGNED NOT NULL,
  riwayat_kelas_id  BIGINT UNSIGNED NULL,
  jenis_mutasi      ENUM('masuk','keluar','pindah_kelas') NOT NULL,
  tanggal_mutasi    DATE NOT NULL,
  sekolah_asal      VARCHAR(150) NULL,
  sekolah_tujuan    VARCHAR(150) NULL,
  kelas_asal_id     INT UNSIGNED NULL,
  kelas_tujuan_id   INT UNSIGNED NULL,
  nomor_surat       VARCHAR(80) NULL,
  keterangan        VARCHAR(500) NULL,
  dicatat_oleh      BIGINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_mutasi_siswa_tanggal (siswa_id, tanggal_mutasi),
  KEY index_mutasi_jenis_tanggal (jenis_mutasi, tanggal_mutasi),
  CONSTRAINT fk_mutasi_siswa FOREIGN KEY (siswa_id) REFERENCES siswa_biodata (id) ON DELETE CASCADE,
  CONSTRAINT fk_mutasi_riwayat FOREIGN KEY (riwayat_kelas_id) REFERENCES siswa_riwayat_kelas (id) ON DELETE SET NULL,
  CONSTRAINT fk_mutasi_kelas_asal FOREIGN KEY (kelas_asal_id) REFERENCES sekolah_kelas (id) ON DELETE SET NULL,
  CONSTRAINT fk_mutasi_kelas_tujuan FOREIGN KEY (kelas_tujuan_id) REFERENCES sekolah_kelas (id) ON DELETE SET NULL,
  CONSTRAINT fk_mutasi_pencatat FOREIGN KEY (dicatat_oleh) REFERENCES akun_pengguna (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Riwayat mutasi siswa secara kronologis: masuk, keluar, atau pindah kelas.'
JOSBOOK_SQL_19
        );

        DB::unprepared(<<<'JOSBOOK_SQL_20'
CREATE TABLE siswa_nilai (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  riwayat_kelas_id BIGINT UNSIGNED NOT NULL,
  semester         ENUM('Ganjil','Genap') NOT NULL COMMENT 'Ganjil atau Genap',
  mapel_id         INT UNSIGNED NOT NULL,
  nilai_angka      DECIMAL(5,2) NOT NULL COMMENT '0 sampai 100',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_nilai (riwayat_kelas_id, semester, mapel_id),
  KEY index_nilai_mapel (mapel_id),
  CONSTRAINT cek_nilai_0_sampai_100 CHECK (nilai_angka >= 0 AND nilai_angka <= 100),
  CONSTRAINT fk_nilai_riwayat FOREIGN KEY (riwayat_kelas_id) REFERENCES siswa_riwayat_kelas (id) ON DELETE CASCADE,
  CONSTRAINT fk_nilai_mapel   FOREIGN KEY (mapel_id)         REFERENCES sekolah_mapel (id)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nilai siswa per tahun ajaran, per semester, per mapel. Mapel kosong tidak dibuat barisnya.'
JOSBOOK_SQL_20
        );

        DB::unprepared(<<<'JOSBOOK_SQL_21'
CREATE TABLE siswa_kehadiran (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  riwayat_kelas_id BIGINT UNSIGNED NOT NULL,
  semester         ENUM('Ganjil','Genap') NOT NULL,
  sakit            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  izin             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  alpa             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  kokurikuler      TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_kehadiran (riwayat_kelas_id, semester),
  CONSTRAINT fk_kehadiran_riwayat FOREIGN KEY (riwayat_kelas_id) REFERENCES siswa_riwayat_kelas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sakit, izin, alpa, dan catatan kokurikuler siswa per semester.'
JOSBOOK_SQL_21
        );

        DB::unprepared(<<<'JOSBOOK_SQL_22'
CREATE TABLE siswa_ekskul (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  riwayat_kelas_id BIGINT UNSIGNED NOT NULL,
  semester         ENUM('Ganjil','Genap') NOT NULL,
  nama_kegiatan    VARCHAR(100) NOT NULL,
  predikat         VARCHAR(30)  NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_ekskul (riwayat_kelas_id, semester, nama_kegiatan),
  CONSTRAINT fk_ekskul_riwayat FOREIGN KEY (riwayat_kelas_id) REFERENCES siswa_riwayat_kelas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kegiatan ekstrakurikuler siswa dan predikatnya per semester.'
JOSBOOK_SQL_22
        );

        DB::unprepared(<<<'JOSBOOK_SQL_23'
CREATE TABLE siswa_pelacakan_alumni (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id    BIGINT UNSIGNED NOT NULL,
  jenis       ENUM('Kuliah','Bekerja','Wirausaha','Mencari kerja','Lainnya') NOT NULL,
  nama_tempat VARCHAR(150) NULL COMMENT 'kampus / perusahaan / usaha',
  keterangan  VARCHAR(255) NULL,
  tahun       CHAR(4) NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_pelacakan_siswa (siswa_id),
  CONSTRAINT fk_pelacakan_siswa FOREIGN KEY (siswa_id) REFERENCES siswa_biodata (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kegiatan alumni setelah lulus (kuliah, kerja, wirausaha). Menu Pelacakan Alumni.'
JOSBOOK_SQL_23
        );

        DB::unprepared(<<<'JOSBOOK_SQL_24'
CREATE TABLE catatan_kenaikan_kelas (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id             BIGINT UNSIGNED NULL,
  kelas_asal_id           INT UNSIGNED NOT NULL,
  tahun_ajaran_asal_id    INT UNSIGNED NOT NULL,
  tahun_ajaran_tujuan_id  INT UNSIGNED NULL,
  jumlah_siswa            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  riwayat_baru            LONGTEXT NULL COMMENT 'JSON: id siswa_riwayat_kelas yang dibuat oleh proses ini',
  data_sebelumnya         LONGTEXT NULL COMMENT 'JSON: isi lama yang diubah, untuk dikembalikan saat dibatalkan',
  dibatalkan_at           TIMESTAMP NULL,
  created_at              TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_kenaikan_kelas (kelas_asal_id, tahun_ajaran_asal_id),
  CONSTRAINT fk_kenaikan_kelas FOREIGN KEY (kelas_asal_id) REFERENCES sekolah_kelas (id) ON DELETE CASCADE,
  CONSTRAINT fk_kenaikan_tahun FOREIGN KEY (tahun_ajaran_asal_id) REFERENCES sekolah_tahun_ajaran (id) ON DELETE CASCADE,

  CONSTRAINT fk_kenaikan_tahun_tujuan FOREIGN KEY (tahun_ajaran_tujuan_id) REFERENCES sekolah_tahun_ajaran (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catatan setiap proses kenaikan kelas. Dipakai untuk tombol Batalkan kenaikan terakhir.'
JOSBOOK_SQL_24
        );

        DB::unprepared(<<<'JOSBOOK_SQL_25'
CREATE TABLE catatan_aktivitas (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id BIGINT UNSIGNED NULL,
  peran       VARCHAR(10)  NULL,
  aksi        VARCHAR(60)  NOT NULL COMMENT 'contoh: tambah_siswa, import_nilai',
  keterangan  VARCHAR(500) NULL,
  objek_tipe  VARCHAR(40)  NULL COMMENT 'contoh: siswa',
  objek_id    BIGINT UNSIGNED NULL,
  alamat_ip   VARCHAR(45)  NULL,
  created_at  TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_log_pengguna (pengguna_id),
  KEY index_log_waktu (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catatan siapa melakukan apa dan kapan. Menu Log Aktivitas.'
JOSBOOK_SQL_25
        );

        DB::unprepared(<<<'JOSBOOK_SQL_26'
CREATE TABLE catatan_cadangan (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_file   VARCHAR(255) NOT NULL,
  lokasi      VARCHAR(255) NOT NULL,
  ukuran_byte BIGINT UNSIGNED NULL,
  dibuat_oleh BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Riwayat file cadangan data. Menu Cadangan.'
JOSBOOK_SQL_26
        );

        DB::unprepared(<<<'JOSBOOK_SQL_27'
CREATE TABLE import_format_tersimpan (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama              VARCHAR(100) NOT NULL,
  jenis             ENUM('siswa','nilai','kehadiran','ekskul','guru') NOT NULL,
  kode_struktur    CHAR(64) NOT NULL COMMENT 'hash dari daftar judul kolom dan urutannya',
  nama_sheet        VARCHAR(100) NULL,
  baris_judul       SMALLINT UNSIGNED NULL,
  pemetaan_kolom          LONGTEXT NOT NULL COMMENT 'JSON: kolom Excel -> kolom tujuan',
  dipakai_kali      INT UNSIGNED NOT NULL DEFAULT 0,
  terakhir_dipakai_at TIMESTAMP NULL,
  dibuat_oleh       BIGINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_format_sidik (jenis, kode_struktur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Format file Excel yang pernah dipetakan, supaya file serupa langsung terbaca tanpa dipetakan ulang.'
JOSBOOK_SQL_27
        );

        DB::unprepared(<<<'JOSBOOK_SQL_28'
CREATE TABLE import_unggahan (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id        BIGINT UNSIGNED NOT NULL,
  jenis              ENUM('siswa','nilai','kehadiran','ekskul','guru') NOT NULL,
  nama_file          VARCHAR(255) NOT NULL,
  lokasi             VARCHAR(255) NOT NULL,
  status             ENUM('diunggah','dibaca','dipetakan','siap_simpan','selesai','gagal','dibatalkan') NOT NULL DEFAULT 'diunggah' COMMENT 'diunggah, dibaca, dipetakan, siap_simpan, selesai, gagal, dibatalkan',
  format_tersimpan_id          BIGINT UNSIGNED NULL,
  tahun_ajaran_id    INT UNSIGNED NULL,
  semester           ENUM('Ganjil','Genap') NULL,
  kelas_id           INT UNSIGNED NULL,
  jika_data_sudah_ada ENUM('lewati','timpa') NOT NULL DEFAULT 'lewati' COMMENT 'lewati = biarkan data lama, timpa = ganti dengan isi file',
  pemetaan_kolom           LONGTEXT NULL COMMENT 'JSON: hasil pemetaan kolom Excel ke kolom sistem',
  jumlah_baris       INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_siap        INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_peringatan  INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_error       INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_tersimpan   INT UNSIGNED NOT NULL DEFAULT 0,
  pesan_gagal        VARCHAR(500) NULL,
  created_at         TIMESTAMP NULL,
  selesai_at         TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY index_import_pengguna (pengguna_id),
  KEY index_import_status (status),
  CONSTRAINT fk_import_pengguna FOREIGN KEY (pengguna_id)     REFERENCES akun_pengguna (id)     ON DELETE CASCADE,
  CONSTRAINT fk_import_format   FOREIGN KEY (format_tersimpan_id)       REFERENCES import_format_tersimpan (id) ON DELETE SET NULL,
  CONSTRAINT fk_import_tahun    FOREIGN KEY (tahun_ajaran_id) REFERENCES sekolah_tahun_ajaran (id) ON DELETE SET NULL,
  CONSTRAINT fk_import_kelas    FOREIGN KEY (kelas_id)        REFERENCES sekolah_kelas (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Setiap kali ada file Excel diunggah: siapa, jenisnya, statusnya, dan ringkasan hasilnya.'
JOSBOOK_SQL_28
        );

        DB::unprepared(<<<'JOSBOOK_SQL_29'
CREATE TABLE import_baris (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  unggahan_id    BIGINT UNSIGNED NOT NULL COMMENT '[V2] dulu bernama import_file_id; menunjuk ke import_unggahan',
  nama_sheet     VARCHAR(100) NULL,
  nomor_baris_excel    INT UNSIGNED NOT NULL COMMENT 'nomor baris di file Excel asli',
  data_asli      LONGTEXT NULL COMMENT 'JSON: isi sel apa adanya',
  data_bersih    LONGTEXT NULL COMMENT 'JSON: isi setelah dirapikan',
  status         ENUM('siap','peringatan','error','dilewati','tersimpan') NOT NULL DEFAULT 'siap',
  pesan          LONGTEXT NULL COMMENT 'JSON: daftar masalah pada baris ini',
  PRIMARY KEY (id),
  KEY index_baris_unggahan (unggahan_id, status),
  CONSTRAINT fk_baris_unggahan FOREIGN KEY (unggahan_id) REFERENCES import_unggahan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Isi setiap baris Excel yang diunggah (asli dan setelah dirapikan), untuk pratinjau dan laporan masalah.'
JOSBOOK_SQL_29
        );

        DB::unprepared(<<<'JOSBOOK_SQL_30'
CREATE TABLE import_kamus_kolom (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis          ENUM('siswa','nilai','kehadiran','ekskul','guru') NOT NULL,
  milik_siapa       VARCHAR(20) NOT NULL DEFAULT 'umum' COMMENT 'umum, ayah, ibu, wali, alamat_ortu, alamat_wali. Untuk judul kembar seperti Agama',
  judul_di_excel  VARCHAR(120) NOT NULL COMMENT 'huruf kecil, spasi berlebih dibuang',
  kolom_tujuan   VARCHAR(60)  NOT NULL COMMENT 'nama kolom di sistem, contoh nomor_induk',
  asal           ENUM('bawaan','admin','otomatis') NOT NULL DEFAULT 'bawaan' COMMENT 'bawaan = dari seeder, admin = ditambah admin, otomatis = dipelajari sistem',
  aktif          TINYINT(1) NOT NULL DEFAULT 1,
  dibuat_oleh    BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_kamus_kolom (jenis, milik_siapa, judul_di_excel),
  KEY index_nama_kolom_tujuan (kolom_tujuan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kamus judul kolom Excel ke kolom sistem (contoh NIPD menjadi nomor_induk). Admin bisa menambah sendiri.'
JOSBOOK_SQL_30
        );

        DB::unprepared(<<<'JOSBOOK_SQL_31'
CREATE TABLE import_kamus_mapel (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mapel_id      INT UNSIGNED NOT NULL,
  nama_di_excel VARCHAR(150) NOT NULL,
  created_at    TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unik_nama_mapel (nama_di_excel),
  CONSTRAINT fk_nama_mapel FOREIGN KEY (mapel_id) REFERENCES sekolah_mapel (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kamus nama mapel di Excel ke mapel sistem (contoh PAI menjadi Pendidikan Agama).'
JOSBOOK_SQL_31
        );

        DB::unprepared(<<<'JOSBOOK_SQL_32'
INSERT INTO sekolah_mapel (kode, nama) VALUES
 ('agm','Pendidikan Agama dan Budi Pekerti'),('pnc','Pendidikan Pancasila'),
 ('ind','Bahasa Indonesia'),('mtk','Matematika'),
 ('ipa','Ilmu Pengetahuan Alam (IPA)'),('ips','Ilmu Pengetahuan Sosial (IPS)'),
 ('big','Bahasa Inggris'),('pjk','Pendidikan Jasmani Olahraga dan Kesehatan'),
 ('sej','Sejarah'),('sbd','Seni Budaya dan Prakarya/Seni dan Budaya'),
 ('inf','Informatika'),('mtl','Matematika Tingkat Lanjut'),
 ('fis','Fisika'),('kim','Kimia'),('bio','Biologi'),('sos','Sosiologi'),
 ('eko','Ekonomi'),('geo','Geografi'),('sjl','Sejarah Tingkat Lanjut'),
 ('bil','Bahasa Inggris Tingkat Lanjut'),('pkw','Prakarya dan Kewirausahaan'),
 ('jaw','Bahasa Jawa')
JOSBOOK_SQL_32
        );

        DB::unprepared(<<<'JOSBOOK_SQL_33'
INSERT INTO sekolah_mapel_per_tingkat (tingkat, mapel_id, kelompok_mapel, urutan)
SELECT 'X', m.id, 'I. Mata Pelajaran Wajib', FIELD(m.kode,'agm','pnc','ind','mtk','ipa','ips','big','pjk','sbd','inf')
FROM sekolah_mapel m WHERE m.kode IN ('agm','pnc','ind','mtk','ipa','ips','big','pjk','sbd','inf')
JOSBOOK_SQL_33
        );

        DB::unprepared(<<<'JOSBOOK_SQL_34'
INSERT INTO sekolah_mapel_per_tingkat (tingkat, mapel_id, kelompok_mapel, urutan)
SELECT 'X', m.id, 'II. Muatan Lokal', 1 FROM sekolah_mapel m WHERE m.kode = 'jaw'
JOSBOOK_SQL_34
        );

        DB::unprepared(<<<'JOSBOOK_SQL_35'
INSERT INTO sekolah_mapel_per_tingkat (tingkat, mapel_id, kelompok_mapel, urutan)
SELECT t.tingkat, m.id, 'I. Mata Pelajaran Wajib', FIELD(m.kode,'agm','pnc','ind','mtk','ipa','ips','big','pjk','sej','sbd')
FROM sekolah_mapel m JOIN (SELECT 'XI' AS tingkat UNION SELECT 'XII') t
WHERE m.kode IN ('agm','pnc','ind','mtk','ipa','ips','big','pjk','sej','sbd')
JOSBOOK_SQL_35
        );

        DB::unprepared(<<<'JOSBOOK_SQL_36'
INSERT INTO sekolah_mapel_per_tingkat (tingkat, mapel_id, kelompok_mapel, urutan)
SELECT t.tingkat, m.id, 'II. Mata Pelajaran Pilihan', FIELD(m.kode,'mtl','fis','kim','bio','sos','eko','geo','sjl','bil','inf','pkw')
FROM sekolah_mapel m JOIN (SELECT 'XI' AS tingkat UNION SELECT 'XII') t
WHERE m.kode IN ('mtl','fis','kim','bio','sos','eko','geo','sjl','bil','inf','pkw')
JOSBOOK_SQL_36
        );

        DB::unprepared(<<<'JOSBOOK_SQL_37'
INSERT INTO sekolah_mapel_per_tingkat (tingkat, mapel_id, kelompok_mapel, urutan)
SELECT t.tingkat, m.id, 'III. Muatan Lokal', 1
FROM sekolah_mapel m JOIN (SELECT 'XI' AS tingkat UNION SELECT 'XII') t WHERE m.kode = 'jaw'
JOSBOOK_SQL_37
        );

        DB::unprepared(<<<'JOSBOOK_SQL_38'
INSERT INTO import_kamus_kolom (jenis, milik_siapa, judul_di_excel, kolom_tujuan) VALUES
 ('siswa','umum','nama lengkap','nama_lengkap'),('siswa','umum','nama','nama_lengkap'),
 ('siswa','umum','nama peserta didik','nama_lengkap'),
 ('siswa','umum','nisn','nisn'),
 ('siswa','umum','induk','nomor_induk'),('siswa','umum','nipd','nomor_induk'),('siswa','umum','nis','nomor_induk'),
 ('siswa','umum','nomor induk','nomor_induk'),('siswa','umum','no induk','nomor_induk'),
 ('siswa','umum','jenis kelamin','jenis_kelamin'),('siswa','umum','jk','jenis_kelamin'),
 ('siswa','umum','l/p','jenis_kelamin'),
 ('siswa','umum','tempat lahir','tempat_lahir'),('siswa','umum','tanggal lahir','tanggal_lahir'),
 ('siswa','umum','tgl lahir','tanggal_lahir'),
 ('siswa','umum','agama','agama'),('siswa','ayah','agama','agama_ayah'),
 ('siswa','ibu','agama','agama_ibu'),('siswa','wali','agama','agama_wali'),
 ('siswa','umum','nik','nik'),
 ('siswa','umum','tanggal','tanggal_diterima'),('siswa','umum','pindahan','jenis_registrasi'),
 ('siswa','umum','alasan','alasan_pindah'),
 ('siswa','umum','nama ayah kandung','nama_ayah'),('siswa','umum','nama ibu kandung','nama_ibu'),
 ('siswa','umum','nama wali','nama_wali'),
 ('siswa','umum','nomor peserta psaj','nomor_peserta_psaj'),('siswa','umum','nopes psaj','nomor_peserta_psaj')
JOSBOOK_SQL_38
        );

        DB::unprepared(<<<'JOSBOOK_SQL_39'
INSERT INTO import_kamus_mapel (mapel_id, nama_di_excel)
SELECT id, kode FROM sekolah_mapel
JOSBOOK_SQL_39
        );

        DB::unprepared(<<<'JOSBOOK_SQL_40'
INSERT INTO import_kamus_mapel (mapel_id, nama_di_excel)
SELECT id, LOWER(nama) FROM sekolah_mapel
JOSBOOK_SQL_40
        );

        DB::unprepared(<<<'JOSBOOK_SQL_41'
INSERT INTO import_kamus_mapel (mapel_id, nama_di_excel)
SELECT id, x FROM sekolah_mapel JOIN (
  SELECT 'agm' k,'pai' x UNION SELECT 'agm','pabp' UNION SELECT 'agm','pendidikan agama'
  UNION SELECT 'pnc','ppkn' UNION SELECT 'pnc','pkn' UNION SELECT 'big','b. inggris'
  UNION SELECT 'ind','b. indonesia' UNION SELECT 'pjk','pjok' UNION SELECT 'jaw','b. jawa'
) a ON a.k = sekolah_mapel.kode
JOSBOOK_SQL_41
        );

        DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }

    public function down(): void
    {
        DB::statement("SET FOREIGN_KEY_CHECKS=0");

        DB::statement("DROP TABLE IF EXISTS `import_kamus_mapel`");
        DB::statement("DROP TABLE IF EXISTS `import_kamus_kolom`");
        DB::statement("DROP TABLE IF EXISTS `import_baris`");
        DB::statement("DROP TABLE IF EXISTS `import_unggahan`");
        DB::statement("DROP TABLE IF EXISTS `import_format_tersimpan`");
        DB::statement("DROP TABLE IF EXISTS `catatan_cadangan`");
        DB::statement("DROP TABLE IF EXISTS `catatan_aktivitas`");
        DB::statement("DROP TABLE IF EXISTS `catatan_kenaikan_kelas`");
        DB::statement("DROP TABLE IF EXISTS `siswa_pelacakan_alumni`");
        DB::statement("DROP TABLE IF EXISTS `siswa_ekskul`");
        DB::statement("DROP TABLE IF EXISTS `siswa_kehadiran`");
        DB::statement("DROP TABLE IF EXISTS `siswa_nilai`");
        DB::statement("DROP TABLE IF EXISTS `siswa_riwayat_mutasi`");
        DB::statement("DROP TABLE IF EXISTS `siswa_riwayat_kelas`");
        DB::statement("DROP TABLE IF EXISTS `siswa_berkas`");
        DB::statement("DROP TABLE IF EXISTS `siswa_biodata`");
        DB::statement("DROP TABLE IF EXISTS `akun_notifikasi`");
        DB::statement("DROP TABLE IF EXISTS `akun_perangkat_login`");
        DB::statement("DROP TABLE IF EXISTS `akun_kode_otp`");
        DB::statement("DROP TABLE IF EXISTS `akun_pengguna`");
        DB::statement("DROP TABLE IF EXISTS `guru_mengajar`");
        DB::statement("DROP TABLE IF EXISTS `guru_wali_kelas`");
        DB::statement("DROP TABLE IF EXISTS `guru_biodata`");
        DB::statement("DROP TABLE IF EXISTS `catatan_tempat_sampah`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_mapel_per_tingkat`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_mapel`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_kelas`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_tahun_ajaran`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_pengaturan`");
        DB::statement("DROP TABLE IF EXISTS `sekolah_identitas`");

        DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
};
