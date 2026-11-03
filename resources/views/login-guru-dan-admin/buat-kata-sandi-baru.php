<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buat Kata Sandi Baru — JosBook</title>
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
            <span class="wic">🔒</span>
            <span class="pl">Langkah 3 dari 3: Amankan Akun Guru</span>
            <h2>Buat Kata Sandi Baru</h2>
            <p class="ws">Buat kata sandi baru untuk mengamankan akun Anda.</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>◎ Kesalahan</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <form method="POST" action="{{ route('buat-kata-sandi-baru.post') }}">
                @csrf
                <label for="n_p">Kata Sandi Baru</label>
                <div class="wf">
                    <i>🔒</i>
                    <input id="n_p" name="password" type="password" placeholder="Minimal 8 karakter" oninput="checkStrength(this.value)" required autofocus>
                    <span class="eye" onclick="togglePassword('n_p')">👁</span>
                </div>

                <div class="sb2">
                    <span>Kekuatan Kata Sandi</span>
                    <b id="str_label">● Lemah (0%)</b>
                    <div><i id="str_bar" style="width:0%"></i></div>
                </div>

                <div class="rl">
                    <b>ATURAN KEAMANAN SANDI</b>
                    <span id="rule_len">Minimal 8 karakter</span>
                    <span id="rule_comb">Gunakan kombinasi huruf dan angka</span>
                    <span id="rule_strong">Jangan gunakan password yang mudah ditebak</span>
                </div>

                <label for="n_p2">Konfirmasi Kata Sandi</label>
                <div class="wf">
                    <i>⟲</i>
                    <input id="n_p2" name="password_confirmation" type="password" placeholder="Ulangi kata sandi baru" required>
                    <span class="eye" onclick="togglePassword('n_p2')">👁</span>
                </div>

                <button type="submit" class="wb">SIMPAN KATA SANDI →</button>
            </form>

            <p class="wfo"><a href="{{ route('login') }}">← Kembali ke Login</a></p>
        </div>

        <div class="wft">
            <b>JosBook</b> · SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah ↗</a>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const el = document.getElementById(id);
            if (el) {
                el.type = el.type === 'password' ? 'text' : 'password';
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
                score += 35;
            } else {
                ruleLen.classList.remove('y');
            }

            if (hasLetter && hasDigit) {
                ruleComb.classList.add('y');
                score += 35;
            } else {
                ruleComb.classList.remove('y');
            }

            if (p.length >= 10 && (hasSpecial || hasUpper)) {
                ruleStrong.classList.add('y');
                score += 30;
            } else {
                ruleStrong.classList.remove('y');
            }

            const bar = document.getElementById('str_bar');
            const label = document.getElementById('str_label');

            bar.style.width = score + '%';
            if (score >= 70) {
                label.innerHTML = '● Kuat (' + score + '%)';
                label.style.color = '#1f8a5b';
                bar.style.background = '#1f8a5b';
            } else if (score >= 40) {
                label.innerHTML = '● Sedang (' + score + '%)';
                label.style.color = '#e8a317';
                bar.style.background = '#e8a317';
            } else {
                label.innerHTML = '● Lemah (' + score + '%)';
                label.style.color = score > 0 ? '#c4412f' : '#0256cf';
                bar.style.background = '#0a57c9';
            }
        }
    </script>
</body>
</html>
