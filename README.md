# Migration Laravel untuk skema JosLearn

Paket ini mengubah `database.sql` acuan menjadi satu migration Laravel yang membuat 30 tabel dan 6 trigger, serta seeder untuk role dan settings awal.

## Penting sebelum dipasang

1. Pastikan database MySQL `joslearn_project` sudah dibuat dan `.env` menunjuk ke database tersebut.
2. Cadangkan database jika sudah berisi data.
3. Migration ini membuat tabel `users`, `password_reset_tokens`, `sessions`, dan `personal_access_tokens` sendiri sesuai skema SQL.
4. Sebelum menjalankan migration, periksa `database/migrations` proyek Laravel. Nonaktifkan/hapus migration bawaan yang juga membuat tabel-tabel tersebut agar tidak terjadi `Table already exists`. Biasanya migration `create_users_table` bawaan membuat `users`, `password_reset_tokens`, dan `sessions`. Jika `php artisan install:api` sudah dijalankan, periksa juga migration `personal_access_tokens`. Jangan menghapus migration `cache` atau `jobs` karena tidak bentrok dengan skema ini.
5. Jangan import `database.sql` ke database yang sama terlebih dahulu. Pilih migration Laravel sebagai cara membuat tabel.
6. Skema memakai fitur MySQL 8.0+ (termasuk CHECK, generated columns, JSON, dan trigger).

## Cara memasang

Salin file:
- `migrations/2026_10_29_000000_create_joslearn_schema.php` ke `database/migrations/`
- `seeders/JoslearnInitialDataSeeder.php` ke `database/seeders/`

Lalu di terminal pada folder proyek Laravel:

```bash
php artisan config:clear
php artisan migrate
php artisan db:seed --class=JoslearnInitialDataSeeder
php artisan migrate:status
```

Periksa tabel di HeidiSQL/phpMyAdmin. Migration akan mencatat prosesnya pada tabel `migrations`.

## Mengulang dari awal (HATI-HATI)

`php artisan migrate:fresh` menghapus semua tabel di database yang dikonfigurasi. Gunakan hanya pada database pengembangan yang boleh dihapus, dan pastikan migration lain yang bentrok sudah ditangani:

```bash
php artisan migrate:fresh
php artisan db:seed --class=JoslearnInitialDataSeeder
```

## Catatan

- Migration ini memakai SQL DDL mentah agar tipe kolom, enum, constraint, generated column, dan nama constraint mengikuti skema sumber sedekat mungkin.
- Akun admin tidak dibuat di seeder ini. Buat akun admin melalui seeder khusus yang menyimpan password menggunakan `Hash::make()`.
- Migration hanya membuat struktur database. Model, relasi Eloquent, autentikasi, API, dan kebijakan akses tetap perlu dibuat terpisah.
