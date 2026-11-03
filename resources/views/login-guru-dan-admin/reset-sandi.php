<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Kata Sandi — JosBook</title>
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
            <span class="wic">⟲</span>
            <span class="pl">Langkah 1 dari 3: Identifikasi Akun Guru</span>
            <h2>Reset Kata Sandi</h2>
            <p class="ws">Masukkan NIP atau email resmi yang terdaftar pada akun Dapodik Anda.</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>◎ Kesalahan</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <form method="POST" action="{{ route('reset-sandi.post') }}">
                @csrf
                <label for="r_e">Email *</label>
                <div class="wf">
                    <i>▣</i>
                    <input id="r_e" name="email" type="text" placeholder="nama@sekolah.sch.id" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="wi">
                    ⓘ Petunjuk: Kode verifikasi 6 digit akan dikirimkan ke email guru yang terdaftar di Dapodik.
                </div>

                <button type="submit" class="wb">KIRIM KODE VERIFIKASI →</button>
            </form>

            <p class="wfo"><a href="{{ route('login') }}">← Kembali ke Login</a></p>
        </div>

        <div class="wft">
            <b>JosBook</b> · SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah ↗</a>
        </div>
    </div>
</body>
</html>
