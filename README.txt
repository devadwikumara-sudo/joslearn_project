JOSLEARN — MIGRATION BARU DARI database (1).sql

Isi: migration Laravel yang membuat 31 tabel, 8 trigger, dan pengaturan awal dari file SQL terbaru. Membutuhkan MySQL 8.0+.

PENTING: php artisan migrate:fresh menghapus SEMUA tabel dan data dalam database yang dipilih di .env. Backup dahulu. Jangan jalankan pada database produksi/berisi data penting.

CARA PASANG
1. Salin 2026_10_29_000000_create_joslearn_schema.php ke joslearn_project/database/migrations/, menggantikan file migration lama dengan nama yang sama.
2. Pastikan tidak ada migration lain yang membuat tabel yang sama. Umumnya pindahkan sementara keluar dari database/migrations file bawaan yang membuat users, password_reset_tokens, sessions, dan personal_access_tokens. Jangan pindahkan migration cache/jobs jika tabelnya masih diperlukan dan tidak dibuat oleh migration baru. Periksa semua migration proyek.
3. Pastikan .env mengarah ke database development yang benar (DB_DATABASE=joslearn_project), lalu jalankan php artisan config:clear.
4. Backup data terlebih dahulu. Jalankan php artisan migrate:fresh.
5. Jalankan php artisan migrate:status untuk memeriksa.

CATATAN
- Migration ini menggantikan skema lama; semua nama tabel/kolom domain mengikuti file SQL baru (misalnya siswa, guru, kelas, absensi, nilai). Model, controller, request, dan query aplikasi yang masih menggunakan nama lama harus disesuaikan.
- Jangan menjalankan file SQL ini secara terpisah setelah migration dijalankan.
- Admin tidak dibuat oleh migration; file SQL sumber juga menyatakan akun admin dibuat melalui seeder dengan password Laravel yang di-hash.
