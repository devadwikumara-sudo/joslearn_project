# Panduan Deploy JosBook

## 1. Siapkan server

- Pasang PHP 8.3 beserta ekstensi Laravel, Composer, database MySQL/MariaDB, dan Node.js hanya jika aset perlu dibangun di server.
- Arahkan document root web server ke folder `public`, bukan ke root proyek.
- Buat database kosong dan akun database dengan hak minimum yang dibutuhkan aplikasi.
- Siapkan domain HTTPS sekolah, SMTP, serta antrean (queue) sesuai kebijakan sekolah.

## 2. Siapkan konfigurasi rahasia

Salin `.env.example` menjadi `.env` di server. Isi `APP_URL`, koneksi database, `CORS_ALLOWED_ORIGINS` dengan origin resmi (dipisahkan koma), SMTP, dan `ADMIN_REGISTRATION_CODE` dengan nilai acak yang kuat. Jangan mengunggah `.env` ke Git atau ke paket rilis.

Sebelum seeder produksi pertama kali, atur `ADMIN_INITIAL_USERNAME`, `ADMIN_INITIAL_EMAIL`, dan `ADMIN_INITIAL_PASSWORD` di `.env` server. Kata sandi awal minimal 12 karakter; akun admin pertama diwajibkan mengganti kata sandi saat login. Hapus `ADMIN_INITIAL_PASSWORD` setelah akun dibuat. Seeder tidak akan membuat admin kedua jika admin sudah ada.

## 3. Pasang dan inisialisasi aplikasi

```sh
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

Migrasi yang ada menyiapkan tabel master tingkat/kelas/mapel. `ProductionSeeder` hanya mengisi identitas sekolah dan admin pertama; `DatabaseSeeder` tidak menjalankan data demo di environment production. Jangan menjalankan `DemoDatabaseSeeder` atau `SiswaBiodataSeeder` pada database produksi.

## 4. Izin direktori

Pastikan user proses PHP/web server dapat menulis ke `storage` dan `bootstrap/cache`, tanpa memberi hak tulis umum ke seluruh proyek. Contoh pada Linux:

```sh
chown -R www-data:www-data storage bootstrap/cache
chmod -R u+rwX,g+rwX storage bootstrap/cache
```

Sesuaikan `www-data` dan grupnya dengan user PHP-FPM di server.

## 5. Queue worker

Jalankan worker sebagai service terkelola (misalnya Supervisor atau systemd), bukan sebagai proses terminal yang mudah berhenti:

```sh
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Atur service untuk mulai otomatis setelah server restart dan pantau log worker.

## 6. Scheduler

Tambahkan cron berikut untuk menjalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /path/ke/joslearn_project && php artisan schedule:run >> /dev/null 2>&1
```

Pastikan cron berjalan dengan user aplikasi dan pantau log scheduler.

## 7. HTTPS dan pemeriksaan akhir

- Aktifkan sertifikat TLS yang valid dan paksa HTTP dialihkan ke HTTPS.
- Isi `APP_URL` dan daftar `CORS_ALLOWED_ORIGINS` dengan origin yang benar; jangan gunakan `*`.
- Pastikan `APP_DEBUG=false`, SMTP dapat mengirim OTP, dan direktori penyimpanan privat tidak dapat diakses langsung dari web.
- Setelah deploy, periksa halaman login, login admin/guru, alur OTP, API mobile, unduh berkas, queue, dan log aplikasi.

## 8. Isi paket rilis

Paket untuk server tidak boleh menyertakan `.env`, `.claude`, `.mcp.json`, `storage/logs/*.log`, `database/*.sqlite`, atau `.phpunit.result.cache`. Berkas pengaturan lokal `.claude` dan `.mcp.json` boleh tetap berada di checkout pengembang; kecualikan keduanya saat membuat arsip rilis. Contoh dari root proyek:

```sh
tar --exclude=.git --exclude=.env --exclude=.claude --exclude=.mcp.json \
    --exclude='storage/logs/*.log' --exclude='database/*.sqlite' \
    --exclude=.phpunit.result.cache -czf josbook-release.tar.gz .
```

## Yang perlu dilakukan di server

1. Siapkan database, domain, HTTPS, SMTP, dan service queue/cron.
2. Salin `.env.example` ke `.env`, isi semua nilai khusus server, lalu buat `APP_KEY` dengan Artisan.
3. Tetapkan kata sandi admin awal dan jalankan hanya `ProductionSeeder`.
4. Jalankan migrasi, cache konfigurasi/rute, buat symbolic link storage, dan atur izin direktori.
5. Hapus `ADMIN_INITIAL_PASSWORD` dari environment setelah admin pertama berhasil dibuat; ganti kata sandi awal ketika login.
