<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="no-referrer">
    <title>Buat Kata Sandi Baru — JosBook SMA Negeri 1 Rejoso</title>
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    <link rel="stylesheet" href="{{ asset('css/josbook-auth-modern.css') }}">
</head>
<body class="jb-auth-body">
    <div class="jc">
        <div class="wh">
            <div class="wh-logo">
                <img src="{{ asset('images/josbook-logo.png') }}" alt="Logo JosBook">
            </div>
            <div class="wh-text">
                <div class="wh-title"><b>JosBook</b> <span class="wh-badge">SISWA</span></div>
                <small>SMA NEGERI 1 REJOSO NGANJUK</small>
            </div>
        </div>

        <div class="wcard">
            @if ($status === 'form')
                <h2>Buat Kata Sandi Baru</h2>
                <p class="ws">Masukkan kata sandi baru untuk akun JosBook Anda, lalu kembali ke aplikasi dan login.</p>

                @if (isset($errors) && $errors->any())
                    <div class="mban">
                        <b>Kesalahan</b>
                        <small>{{ $errors->first() }}</small>
                    </div>
                @endif

                <form method="POST" action="{{ route('siswa.reset-sandi.simpan') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <label for="n_p">Kata Sandi Baru</label>
                    <div class="wf">
                        <input id="n_p" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" required autofocus>
                    </div>

                    <label for="n_p2" style="margin-top:16px">Konfirmasi Kata Sandi Baru</label>
                    <div class="wf">
                        <input id="n_p2" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Ulangi kata sandi baru" required>
                    </div>

                    <button type="submit" class="wb">
                        <span>SIMPAN KATA SANDI BARU &rarr;</span>
                    </button>
                </form>
            @elseif ($status === 'selesai')
                <h2>Kata Sandi Berhasil Diganti</h2>
                <p class="ws">Kata sandi akun Anda sudah diperbarui dan semua perangkat telah dikeluarkan. Silakan buka aplikasi JosBook dan login dengan kata sandi baru.</p>
            @else
                <h2>Tautan Tidak Berlaku</h2>
                <p class="ws">Tautan ini sudah kedaluwarsa, sudah dipakai, atau tidak valid. Buka aplikasi JosBook, pilih Lupa Kata Sandi, lalu minta tautan baru.</p>
            @endif
        </div>

        <div class="wft">
            <b>JosBook</b> &bull; SMA Negeri 1 Rejoso Nganjuk
        </div>
    </div>
</body>
</html>
