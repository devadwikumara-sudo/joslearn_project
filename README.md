# Menyiapkan database JosLearn

Skema database aplikasi dikelola dengan migration Laravel di `database/migrations`. Setiap anggota tim menjalankan migration pada database lokalnya sendiri; `git pull` membagikan perubahan skema, bukan isi database lokal.

Migration skema membuat 30 tabel dan membutuhkan MySQL 8.0 atau lebih baru. Migration lain membuat tabel sesi serta data referensi kelas dan tahun ajaran. Jangan mengimpor dump SQL untuk tabel yang sama sebelum menjalankan migration.

## Setup lokal

1. Pastikan MySQL berjalan dan buat database kosong:

   ```sql
   CREATE DATABASE josbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Pasang dependency dan buat file `.env` jika belum ada:

   ```powershell
   composer install
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   ```

3. Atur koneksi database di `.env` agar sesuai dengan MySQL lokal:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=josbook
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Buat app key, jalankan migration dan data awal, lalu periksa statusnya:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan migrate:status
   ```

`--seed` menjalankan `DatabaseSeeder`, yang membuat akun demo dan memuat data siswa contoh. Gunakan hanya untuk development; jangan seed data ini ke production. Ganti kredensial demo dan pastikan data siswa contoh tidak dipakai atau dibagikan sebagai data nyata.

## Jika database sudah berisi data

- Cadangkan database sebelum mengubah skema.
- `php artisan migrate` hanya menjalankan migration yang belum tercatat. Periksa `php artisan migrate:status` terlebih dahulu jika status migration tidak diketahui.
- Jangan gunakan `php artisan migrate:fresh` pada database yang datanya ingin dipertahankan: perintah itu menghapus semua tabel.
- Jangan mengimpor dump SQL untuk skema yang sama bersamaan dengan migration. Database yang tabelnya sudah dibuat di luar migration perlu direkonsiliasi terlebih dahulu, bukan langsung dijalankan dengan migration ini.

Setelah anggota tim melakukan `git pull`, mereka cukup menyiapkan database dan `.env` masing-masing, kemudian menjalankan `php artisan migrate --seed` untuk database development yang baru.
