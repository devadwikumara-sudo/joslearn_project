<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — JosBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
</head>
<body style="margin:0;padding:0;background:#fff;font-family:'Plus Jakarta Sans',Inter,sans-serif">
    <div class="jl">
        <!-- Panel Kiri Sesuai Desain Login.png -->
        <div class="jlf">
            <div class="jb">
                <div class="jb-logo">
                    <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo">
                </div>
                <b>JOSBOOK</b>
            </div>

            <div class="jlc">
                <div class="jlc-logo">
                    <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo">
                </div>
                <h1>JosBook</h1>
                <p>Absensi &amp; Nilai Raport Real Time</p>
                <ul>
                    <li>Pemantauan kehadiran siswa otomatis &amp; akurat</li>
                    <li>Pengolahan nilai raport terpadu &amp; transparan</li>
                </ul>
            </div>

            <small>© 2026 JosLearn. Hak Cipta Dilindungi.</small>
        </div>

        <!-- Panel Kanan Form Login Sesuai Desain Login.png -->
        <div class="jr">
            <div class="jm">
                <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo" style="width:90px;height:90px;object-fit:contain">
            </div>

            <div class="jfm">
                <h1>Selamat Datang</h1>
                <h4>Masuk ke JosBook</h4>
                <small class="jh">Gunakan akun Admin atau Guru yang terdaftar</small>

                @if(session('error'))
                <div class="mban">
                    <b>◎ Login gagal</b>
                    <small>{{ session('error') }}</small>
                </div>
                @endif

                @if(session('success'))
                <div style="background:#e8f8f0;border:1px solid #a3e6c0;color:#1f8a5b;border-radius:10px;padding:9px 12px;font-size:12px;margin-bottom:12px">
                    <b>✔ Berhasil</b>
                    <small style="display:block;margin-top:2px">{{ session('success') }}</small>
                </div>
                @endif

                @if(isset($errors) && $errors->any())
                <div class="mban">
                    <b>◎ Login gagal</b>
                    <small>{{ $errors->first() }}</small>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <label for="l_u">NIS/NIP, Email</label>
                    <div class="wf">
                        <i>👤</i>
                        <input id="l_u" name="username" type="text" placeholder="Masukkan Nama Pengguna atau NIS/NIP" value="{{ old('username') }}" autocomplete="username" required autofocus>
                    </div>

                    <label for="l_p">Kata Sandi</label>
                    <div class="wf">
                        <i>🔒</i>
                        <input id="l_p" name="password" type="password" placeholder="Masukkan kata sandi Anda" autocomplete="current-password" required>
                        <span class="eye" onclick="togglePassword('l_p')">👁</span>
                    </div>

                    <div class="jrow">
                        <label style="display:flex;gap:6px;margin:0;align-items:center;cursor:pointer">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Ingat saya
                        </label>
                        <a href="{{ route('reset-sandi') }}">Lupa Kata Sandi?</a>
                    </div>

                    <button type="submit" class="jbtn">Masuk →</button>
                </form>

                <p class="jfoot">Belum punya akun? <a href="{{ route('daftar') }}">Daftar akun</a></p>

                <div class="lgh" style="margin-top:16px">
                    <b>Masuk cepat (demo)</b><br>
                    Admin: <code>admin</code> / <code>admin123</code><br>
                    Guru: <code>198501152010011002</code> / <code>guru123</code>
                </div>
            </div>

            <small class="jbt">Memerlukan bantuan akses? Hubungi administrator sekolah</small>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const el = document.getElementById(id);
            if (el) {
                el.type = el.type === 'password' ? 'text' : 'password';
            }
        }
    </script>
</body>
</html>
