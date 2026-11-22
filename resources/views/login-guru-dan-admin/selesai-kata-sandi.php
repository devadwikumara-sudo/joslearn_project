<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kata Sandi Berhasil Diubah — JosBook SMA Negeri 1 Rejoso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    <link rel="stylesheet" href="{{ asset('css/josbook-auth-modern.css') }}?v={{ file_exists(public_path('css/josbook-auth-modern.css')) ? filemtime(public_path('css/josbook-auth-modern.css')) : time() }}">
</head>
<body class="jb-auth-body">
    <div class="jc">
        <!-- Header Logo Instansi -->
        <div class="wh">
            <div class="wh-logo">
                <img src="{{ asset('images/josbook-logo.png') }}" alt="Logo JosBook">
            </div>
            <div class="wh-text">
                <div class="wh-title">
                    <b>JosBook</b>
                    <span class="wh-badge">PORTAL</span>
                </div>
                <small>SMA NEGERI 1 REJOSO NGANJUK</small>
            </div>
        </div>

        <div class="wcard">
            <div class="wic ok">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <div class="pl" style="background:#ecfdf5;color:#059669;border-color:#a7f3d0">
                ● Akun Berhasil Diamankan
            </div>

            <h2>Kata Sandi Berhasil Diubah</h2>
            <p class="ws">Kata sandi Anda telah berhasil diperbarui. Silakan masuk menggunakan kata sandi baru untuk mengakses Portal Guru.</p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin:16px 0;text-align:left;display:grid;gap:8px">
                <div>
                    <small style="color:#64748b;font-weight:700;font-size:10px">IDENTITAS AKUN</small>
                    <b style="color:#0f172a;font-size:13px;display:block">{{ session('identitas_nama', 'Joko Susanto') }}</b>
                    <span style="color:#64748b;font-size:11px">{{ session('identitas_id', 'NIP: 19820415 200801 1 007') }}</span>
                </div>
                <div>
                    <small style="color:#64748b;font-weight:700;font-size:10px">WAKTU PEMBARUAN</small>
                    <b style="color:#0f172a;font-size:12px;display:block">{{ now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</b>
                </div>
                <div style="color:#16a34a;font-size:11px;font-weight:600">
                    🛡 Sesi Sebelumnya Ditutup Demi Keamanan
                </div>
            </div>

            <a href="{{ route('login') }}" class="wb" style="text-decoration:none;display:block;text-align:center;box-sizing:border-box">
                KEMBALI KE HALAMAN LOGIN &rarr;
            </a>

            <p class="wfo">
                Setelah login, Anda akan langsung diarahkan ke Dashboard Sistem Akademik SMA Negeri 1 Rejoso.
            </p>
        </div>

        <div class="wft">
            <b>JosBook</b> &bull; SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah &nearr;</a>
        </div>
    </div>
</body>
</html>
