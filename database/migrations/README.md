# Database migrations

Migration dalam folder ini adalah sumber skema database JosLearn. Untuk membuat database development baru, ikuti langkah di [README utama](../../README.md), atur `.env`, lalu jalankan:

```bash
php artisan migrate --seed
```

Jangan mengimpor dump SQL untuk tabel yang juga dibuat oleh migration. Untuk database yang sudah berisi data, cadangkan dan periksa status migration terlebih dahulu; jangan menjalankan `migrate:fresh` kecuali semua tabel boleh dihapus.
