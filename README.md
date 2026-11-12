# JosBook Web

JosBook adalah sistem Buku Induk digital SMA Negeri 1 Rejoso Nganjuk. Aplikasi web menggunakan Laravel; aplikasi siswa menggunakan API pada `/api/v1/*`.

## Development lokal

1. Siapkan PHP 8.3, Composer, dan MySQL 8.0 atau lebih baru. Buat database `josbook`.
2. Pasang dependency dan salin contoh konfigurasi bila `.env` belum ada:

   ```powershell
   composer install
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   ```

3. Untuk development lokal, atur `.env` ke `APP_ENV=local`, `APP_DEBUG=true`, serta sesuaikan koneksi database.
4. Buat app key dan jalankan migration serta data development:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   ```

`DatabaseSeeder` membuat data demo hanya di luar environment production. Data siswa demo bersifat sintetis. Jangan gunakan seeder demo pada database produksi.

## Tes

```powershell
php artisan test
```

## Deploy

Ikuti [README_DEPLOY.md](README_DEPLOY.md). `.env` berisi rahasia khusus lingkungan dan tidak boleh dimasukkan ke ZIP atau paket rilis.
