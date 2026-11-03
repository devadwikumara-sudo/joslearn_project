<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Akun — JosBook</title>
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
            <span class="wic">✉</span>
            <span class="pl">Langkah 2 dari 3: Verifikasi Kode Keamanan</span>
            <h2>Verifikasi Akun</h2>
            <p class="ws">Kami telah mengirimkan 6 digit kode keamanan ke email kedinasan Anda.</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>◎ Verifikasi gagal</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <div class="mp">
                ✔ <b>{{ session('masked_email', 'guru@sman1rejoso.sch.id') }}</b> <em>TERVALIDASI</em>
            </div>

            <form method="POST" action="{{ route('verifikasi-akun.post') }}" id="otpForm">
                @csrf
                <input type="hidden" name="kode" id="kode_input">

                <div class="otps">
                    <input class="otp" id="o0" maxlength="1" inputmode="numeric" autofocus pattern="[0-9]*">
                    <input class="otp" id="o1" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input class="otp" id="o2" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input class="otp" id="o3" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input class="otp" id="o4" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input class="otp" id="o5" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                </div>

                <button type="submit" class="wb">VERIFIKASI KODE →</button>
            </form>

            <p class="wfo">
                Belum menerima kode verifikasi?<br>
                Kirim ulang kode: <em class="tm" id="timer_text">⏱ Kirim ulang dalam 00:45</em><br>
                <small style="color:#0256cf;font-weight:600">Kode simulasi demo: 123456</small>
            </p>

            <div class="wlk">
                <a href="{{ route('login') }}">← Kembali ke Login</a>
                <a href="#">ⓘ Bantuan Guru</a>
            </div>
        </div>

        <div class="wft">
            <b>JosBook</b> · SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah ↗</a>
        </div>
    </div>

    <script>
        const inputs = [0, 1, 2, 3, 4, 5].map(i => document.getElementById('o' + i));

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < 5) {
                    inputs[index + 1].focus();
                }
                updateCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                if (/^\d{6}$/.test(pasteData)) {
                    pasteData.split('').forEach((char, i) => {
                        if (inputs[i]) inputs[i].value = char;
                    });
                    inputs[5].focus();
                    updateCode();
                }
            });
        });

        function updateCode() {
            const code = inputs.map(i => i.value).join('');
            document.getElementById('kode_input').value = code;
        }

        document.getElementById('otpForm').addEventListener('submit', (e) => {
            updateCode();
        });

        // Resend countdown timer
        let seconds = 45;
        const timerEl = document.getElementById('timer_text');
        const interval = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(interval);
                timerEl.innerHTML = '<a href="#" onclick="location.reload();return false;" style="color:#0256cf;font-weight:700">Kirim Ulang Sekarang</a>';
            } else {
                timerEl.textContent = `⏱ Kirim ulang dalam 00:${seconds < 10 ? '0' : ''}${seconds}`;
            }
        }, 1000);
    </script>
</body>
</html>
