<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buat Kata Sandi Baru — JosBook SMA Negeri 1 Rejoso</title>
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
            <div class="wic">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>

            <div class="pl">
                ● Langkah 3 dari 3: Amankan Akun
            </div>

            <h2>Buat Kata Sandi Baru</h2>
            <p class="ws">Buat kata sandi baru untuk mengamankan akun Anda.</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>Kesalahan</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <form method="POST" action="{{ route('buat-kata-sandi-baru.post') }}">
                @csrf
                <label for="n_p">Kata Sandi Baru</label>
                <div class="wf">
                    <span class="wf-icon-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="n_p" name="password" type="password" placeholder="••••••••••••••••" oninput="checkStrength(this.value)" required autofocus>
                    <span class="eye" onclick="togglePassword('n_p')" title="Tampilkan / Sembunyikan sandi">
                        <svg id="eye-icon-n_p" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                </div>

                <div class="sb2" style="margin-top:12px">
                    <span>Kekuatan Kata Sandi</span>
                    <b id="str_label" style="color:var(--edu-blue-600)">● Lemah (0%)</b>
                    <div><i id="str_bar" style="width:0%"></i></div>
                </div>

                <div class="rl" style="margin-top:12px">
                    <b>ATURAN KEAMANAN SANDI</b>
                    <span id="rule_len">Minimal 8 karakter</span>
                    <span id="rule_comb">Gunakan kombinasi huruf dan angka</span>
                    <span id="rule_strong">Jangan gunakan password yang mudah ditebak</span>
                </div>

                <label for="n_p2" style="margin-top:16px">Konfirmasi Kata Sandi Baru</label>
                <div class="wf">
                    <span class="wf-icon-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <input id="n_p2" name="password_confirmation" type="password" placeholder="••••••••••••••••" required>
                    <span class="eye" onclick="togglePassword('n_p2')" title="Tampilkan / Sembunyikan sandi">
                        <svg id="eye-icon-n_p2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                </div>

                <button type="submit" class="wb">
                    <span>SIMPAN KATA SANDI BARU &rarr;</span>
                </button>
            </form>

            <p class="wfo" style="margin-top:20px">
                <a href="{{ route('login') }}">&larr; Kembali ke Login</a>
            </p>
        </div>

        <div class="wft">
            <b>JosBook</b> &bull; SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah &nearr;</a>
        </div>
    </div>

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

        function checkStrength(p) {
            let score = 0;
            const hasLen = p.length >= 8;
            const hasLetter = /[a-zA-Z]/.test(p);
            const hasDigit = /\d/.test(p);
            const hasSpecial = /[^A-Za-z0-9]/.test(p);
            const hasUpper = /[A-Z]/.test(p);

            const ruleLen = document.getElementById('rule_len');
            const ruleComb = document.getElementById('rule_comb');
            const ruleStrong = document.getElementById('rule_strong');

            if (hasLen) {
                ruleLen.classList.add('y');
                ruleLen.textContent = 'Minimal 8 karakter (terpenuhi)';
                score += 35;
            } else {
                ruleLen.classList.remove('y');
                ruleLen.textContent = 'Minimal 8 karakter';
            }

            if (hasLetter && hasDigit) {
                ruleComb.classList.add('y');
                ruleComb.textContent = 'Gunakan kombinasi huruf dan angka (terpenuhi)';
                score += 35;
            } else {
                ruleComb.classList.remove('y');
                ruleComb.textContent = 'Gunakan kombinasi huruf dan angka';
            }

            if (p.length >= 10 && (hasSpecial || hasUpper)) {
                ruleStrong.classList.add('y');
                ruleStrong.textContent = 'Jangan gunakan password yang mudah ditebak (terpenuhi)';
                score += 30;
            } else {
                ruleStrong.classList.remove('y');
                ruleStrong.textContent = 'Jangan gunakan password yang mudah ditebak';
            }

            const bar = document.getElementById('str_bar');
            const label = document.getElementById('str_label');

            bar.style.width = score + '%';
            if (score >= 70) {
                label.innerHTML = '● Kuat (' + score + '%)';
                label.style.color = '#10b981';
                bar.style.background = '#10b981';
            } else if (score >= 40) {
                label.innerHTML = '● Sedang (' + score + '%)';
                label.style.color = '#f59e0b';
                bar.style.background = '#f59e0b';
            } else {
                label.innerHTML = '● Lemah (' + score + '%)';
                label.style.color = score > 0 ? '#ef4444' : '#64748b';
                bar.style.background = '#ef4444';
            }
        }
    </script>
</body>
</html>
