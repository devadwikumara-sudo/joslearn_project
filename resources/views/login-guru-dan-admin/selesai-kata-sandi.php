<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kata Sandi Berhasil Diubah — JosBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
</head>
<body style="margin:0;padding:0;background:#f6f9fe;font-family:'Plus Jakarta Sans',Inter,sans-serif">
    <div class="jc">
        <div class="wbg"></div>
        <div class="wh">
            <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo">
            <div>
                <b>JosBook <u>PORTAL</u></b>
                <small>SMA NEGERI 1 REJOSO NGANJUK</small>
            </div>
        </div>

        <div class="wcard">
            <span class="wic ok">✔</span>
            <span class="pl">Akun Berhasil Diamankan</span>
            <h2>Kata Sandi Berhasil Diubah</h2>
            <p class="ws">Kata sandi Anda telah berhasil diperbarui. Silakan masuk menggunakan kata sandi baru untuk mengakses Portal JosBook.</p>

            <div class="wsm">
                <div>
                    <small>IDENTITAS AKUN</small>
                    <b>{{ session('identitas_nama', 'Pengguna Terdaftar') }}</b>
                    <small>{{ session('identitas_id', 'SMA Negeri 1 Rejoso') }}</small>
                </div>
                <div>
                    <small>WAKTU PEMBARUAN</small>
                    <b>{{ now()->setTimezone('Asia/Jakarta')->format('d M Y - H:i') }} WIB</b>
                </div>
                <div class="g">✔ Sesi Sebelumnya Ditutup Demi Keamanan</div>
            </div>

            <a href="{{ route('login') }}" class="wb" style="text-decoration:none;display:block;text-align:center;box-sizing:border-box">KEMBALI KE LOGIN →</a>

            <p class="wfo">ⓘ Setelah login, Anda akan langsung diarahkan ke Dashboard SMA Negeri 1 Rejoso.</p>
        </div>

        <div class="wft">
            <b>JosBook</b> · SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah ↗</a>
        </div>
    </div>
</body>
</html>

