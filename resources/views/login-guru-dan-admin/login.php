<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — JosBook SMA Negeri 1 Rejoso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    <link rel="stylesheet" href="{{ asset('css/josbook-auth-modern.css') }}?v={{ file_exists(public_path('css/josbook-auth-modern.css')) ? filemtime(public_path('css/josbook-auth-modern.css')) : time() }}">
</head>
<body>

    <!-- Full-Screen Viewport Locked to 1 Page (Open Frameless Layout) -->
    <div class="jb-edu-viewport">
        <div class="jb-edu-container">

            <!-- Top Header: School Brand & Portal Badge -->
            <header class="jb-edu-header">
                <div class="jb-edu-brand">
                    <div class="jb-edu-brand-logo">
                        <img src="{{ asset('images/josbook-logo.png') }}" alt="Logo JosBook">
                    </div>
                    <div class="jb-edu-brand-info">
                        <span class="jb-edu-brand-title">JOSBOOK</span>
                        <span class="jb-edu-brand-subtitle">SMA NEGERI 1 REJOSO NGANJUK</span>
                    </div>
                </div>

                <div class="jb-edu-portal-badge">
                    <span class="dot"></span>
                    <span>PORTAL BUKU INDUK DIGITAL</span>
                </div>
            </header>

            <!-- Main Stage (Open Frameless Left Aligned) -->
            <main class="jb-edu-stage">
                <div class="jb-edu-open-panel">
                    <!-- Kicker / Tag -->
                    <div class="jb-edu-kicker">
                        <span class="dot"></span>
                        <span>PORTAL RESMI AKADEMIK</span>
                    </div>

                    <h1 class="jb-edu-title">Akses Akun Portal</h1>
                    <p class="jb-edu-subtitle">Silakan masuk menggunakan akun Guru atau Administrator resmi SMA Negeri 1 Rejoso.</p>

                    <!-- Flash Message Alerts -->
                    @if(session('error'))
                    <div class="jb-edu-alert-error">
                        <b>Login Gagal</b>
                        <span>{{ session('error') }}</span>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="jb-edu-alert-success">
                        <span>{{ session('success') }}</span>
                    </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                    <div class="jb-edu-alert-error">
                        <b>Login Gagal</b>
                        <span>{{ $errors->first() }}</span>
                    </div>
                    @endif

                    <!-- Login Form -->
                    <form class="jb-edu-form" method="POST" action="{{ route('login.post') }}">
                        @csrf

                        <!-- Field 1: E-mail / NIP / Username -->
                        <div class="jb-edu-field-group">
                            <label for="l_u" class="jb-edu-label">E-mail / NIP / Nama Pengguna</label>
                            <div class="jb-edu-input-wrap">
                                <input id="l_u" name="username" type="text" class="jb-edu-input" placeholder="Masukkan email resmi, NIP, atau nama pengguna" value="{{ old('username') }}" autocomplete="username" required autofocus>
                                <span class="jb-edu-input-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                            </div>
                        </div>

                        <!-- Field 2: Password -->
                        <div class="jb-edu-field-group">
                            <label for="l_p" class="jb-edu-label">Kata Sandi</label>
                            <div class="jb-edu-input-wrap">
                                <input id="l_p" name="password" type="password" class="jb-edu-input" placeholder="Masukkan kata sandi akun Anda" autocomplete="current-password" required>
                                <span class="jb-edu-input-icon clickable" onclick="togglePassword('l_p')" title="Tampilkan / Sembunyikan Sandi">
                                    <svg id="eye-icon-l_p" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                            </div>
                        </div>

                        <!-- Row: Remember Session & Forgot Password -->
                        <div class="jb-edu-row">
                            <label class="jb-edu-checkbox-label">
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <span>Ingat sesi masuk</span>
                            </label>
                            <a href="{{ route('reset-sandi') }}" class="jb-edu-forgot-link">Lupa kata sandi?</a>
                        </div>

                        <!-- Primary Pill Action Button (Vibrant Cyan / Sky Blue Pill) -->
                        <button type="submit" class="jb-edu-btn">
                            <span>Masuk ke Akun</span>
                        </button>
                    </form>

                    <!-- New User Link -->
                    <p class="jb-edu-footer-text">
                        Pengguna baru? <a href="{{ route('daftar') }}">Buat akun di sini</a>
                    </p>

                    <!-- Trust Bar / Indicators -->
                    <div class="jb-edu-trust-bar">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Terenkripsi SSL
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/></svg>
                            Buku Induk Digital
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Dapodik Terpadu
                        </span>
                    </div>
                </div>
            </main>

            <!-- Bottom Footer: Accreditation & Copyright -->
            <footer class="jb-edu-footer">
                <span>&copy; 2026 SMA Negeri 1 Rejoso Nganjuk &bull; Hak Cipta Dilindungi</span>
                <div class="jb-edu-footer-badges">
                    <span class="jb-edu-footer-badge">Terakreditasi A</span>
                    <span class="jb-edu-footer-badge">Kurikulum Merdeka</span>
                    <span class="jb-edu-footer-badge">Dapodik Terintegrasi</span>
                </div>
            </footer>

        </div>
    </div>

    <!-- Script Password Toggle -->
    <script>
        function togglePassword(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('eye-icon-' + id);
            if (el) {
                if (el.type === 'password') {
                    el.type = 'text';
                    if (icon) {
                        icon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>';
                    }
                } else {
                    el.type = 'password';
                    if (icon) {
                        icon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
                    }
                }
            }
        }
    </script>
</body>
</html>
