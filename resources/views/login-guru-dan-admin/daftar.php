<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Akun — JosBook SMA Negeri 1 Rejoso</title>
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
                <div class="jb-edu-open-panel wide">

                    <!-- Kicker / Tag -->
                    <div class="jb-edu-kicker">
                        <span class="dot"></span>
                        <span>REGISTRASI PENGGUNA BARU</span>
                    </div>

                    <h1 class="jb-edu-title">Daftar Akun Baru</h1>
                    <p class="jb-edu-subtitle">Pilih jenis akun dan lengkapi data diri Anda. Akun guru langsung aktif setelah mendaftar, lalu administrator mengatur penugasan kelas dan mata pelajaran.</p>

                    @if(isset($errors) && $errors->any())
                    <div class="jb-edu-alert-error">
                        <b>Terjadi kesalahan</b>
                        <span>{{ $errors->first() }}</span>
                    </div>
                    @endif

                    <form class="jb-edu-form" method="POST" action="{{ route('daftar.post') }}" autocomplete="off">
                        @csrf
                        <!-- Hidden traps to intercept aggressive browser autofill -->
                        <input type="text" name="fake_usernamestop" style="display:none" tabindex="-1" autocomplete="off">
                        <input type="password" name="fake_passwordstop" style="display:none" tabindex="-1" autocomplete="off">

                        <input type="hidden" name="peran" id="role_input" value="{{ old('peran', 'admin') }}">

                        <!-- Role Switcher Tabs (Admin vs Guru Pengajar) -->
                        <div class="jb-edu-role-selector">
                            <button type="button" id="tab_admin" class="jb-edu-role-tab {{ old('peran', 'admin') === 'admin' ? 'active' : '' }}" onclick="selectRole('admin')">
                                Admin
                            </button>
                            <button type="button" id="tab_guru" class="jb-edu-role-tab {{ old('peran') === 'guru' ? 'active' : '' }}" onclick="selectRole('guru')">
                                Guru Pengajar
                            </button>
                        </div>

                        <!-- 2-Column Responsive Form Fields -->
                        <div class="jb-edu-form-grid">
                            <!-- Nama Lengkap -->
                            <div class="jb-edu-field-group">
                                <label for="d_n" class="jb-edu-label">Nama Lengkap &amp; Gelar</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="d_n" name="nama" type="text" class="jb-edu-input" placeholder="Masukkan nama lengkap dan gelar" value="{{ old('nama') }}" autocomplete="off" required>
                                    <span class="jb-edu-input-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Email Resmi -->
                            <div class="jb-edu-field-group">
                                <label for="d_e" class="jb-edu-label">Email Resmi Sekolah</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="d_e" name="email" type="email" class="jb-edu-input" placeholder="Masukkan alamat email resmi sekolah" value="{{ old('email') }}" autocomplete="off" required>
                                    <span class="jb-edu-input-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Identitas / NIP -->
                            <div class="jb-edu-field-group" id="group_identitas">
                                <label for="d_i" class="jb-edu-label" id="label_identitas">Nomor Identitas / NIP Admin</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="d_i" name="identitas" type="text" class="jb-edu-input" placeholder="Masukkan NIP Admin terdaftar" value="{{ old('identitas') }}" maxlength="{{ old('peran', 'admin') === 'guru' ? 30 : 50 }}" autocomplete="off" required>
                                    <span class="jb-edu-input-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h6M7 16h8"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Kode Admin (Conditional) -->
                            <div class="jb-edu-field-group" id="admin_code_group" style="{{ old('peran', 'admin') === 'admin' ? '' : 'display:none' }}">
                                <label for="admin_registration_code" class="jb-edu-label">Kode Otorisasi Admin</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="admin_registration_code" name="admin_registration_code" type="password" class="jb-edu-input" placeholder="Masukkan kode otorisasi dari kepala sekolah" autocomplete="new-password" {{ old('peran', 'admin') === 'admin' ? 'required' : '' }}>
                                    <span class="jb-edu-input-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="16.5" r="5.5"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Kata Sandi -->
                            <div class="jb-edu-field-group">
                                <label for="d_p" class="jb-edu-label">Kata Sandi</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="d_p" name="password" type="password" class="jb-edu-input" placeholder="Masukkan kata sandi minimal 8 karakter" autocomplete="new-password" required>
                                    <span class="jb-edu-input-icon clickable" onclick="togglePassword('d_p')" title="Tampilkan / Sembunyikan Sandi">
                                        <svg id="eye-icon-d_p" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Konfirmasi Kata Sandi -->
                            <div class="jb-edu-field-group">
                                <label for="d_p2" class="jb-edu-label">Konfirmasi Kata Sandi</label>
                                <div class="jb-edu-input-wrap">
                                    <input id="d_p2" name="password_confirmation" type="password" class="jb-edu-input" placeholder="Ulangi kata sandi yang telah dibuat" autocomplete="new-password" required>
                                    <span class="jb-edu-input-icon clickable" onclick="togglePassword('d_p2')" title="Tampilkan / Sembunyikan Sandi">
                                        <svg id="eye-icon-d_p2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="jb-edu-btn" style="margin-top:6px">
                            <span>Daftar Akun Sekarang</span>
                        </button>
                    </form>

                    <p class="jb-edu-footer-text">
                        Sudah memiliki akun terdaftar? <a href="{{ route('login') }}">Masuk di sini</a>
                    </p>

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

    <script>
        function selectRole(role) {
            document.getElementById('role_input').value = role;
            const tabAdmin = document.getElementById('tab_admin');
            const tabGuru = document.getElementById('tab_guru');
            const labelIdentitas = document.getElementById('label_identitas');
            const inputIdentitas = document.getElementById('d_i');
            const adminCodeGroup = document.getElementById('admin_code_group');
            const adminCode = document.getElementById('admin_registration_code');

            if (role === 'admin') {
                tabAdmin.classList.add('active');
                tabGuru.classList.remove('active');
                adminCodeGroup.style.display = '';
                adminCode.required = true;
                labelIdentitas.innerText = 'Nomor Identitas / NIP Admin';
                inputIdentitas.placeholder = 'Masukkan NIP Admin terdaftar';
                inputIdentitas.maxLength = 50;
            } else {
                tabGuru.classList.add('active');
                tabAdmin.classList.remove('active');
                adminCodeGroup.style.display = 'none';
                adminCode.required = false;
                adminCode.value = '';
                labelIdentitas.innerText = 'Nomor Identitas / NIP Guru';
                inputIdentitas.placeholder = 'Masukkan NIP Anda';
                inputIdentitas.maxLength = 30;
            }
        }

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
