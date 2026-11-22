<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Akun — JosBook SMA Negeri 1 Rejoso</title>
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
                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                </svg>
            </div>

            <div class="pl">
                ● Langkah 2 dari 3: Verifikasi Kode Keamanan
            </div>

            <h2>Verifikasi Akun</h2>
            <p class="ws">Kami telah mengirimkan 6 digit kode keamanan ke email kedinasan Anda:</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>Verifikasi gagal</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:99px;padding:6px 14px;font-size:12px;margin-bottom:14px;display:inline-block">
                ✔ <b>{{ session('masked_email', 'bu***@sman1rejoso.sch.id') }}</b> 
                <span style="font-size:10px;background:#e2e8f0;color:#475569;border-radius:4px;padding:2px 6px;margin-left:4px">TERVALIDASI</span>
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

                <button type="submit" class="wb">
                    <span>VERIFIKASI KODE &rarr;</span>
                </button>
            </form>

            <form method="POST" action="{{ route('verifikasi-akun.kirim-ulang') }}" id="resendForm" style="display:none">
                @csrf
            </form>

            <p class="wfo">
                Belum menerima kode verifikasi?<br>
                <span id="timer_text" style="color:var(--edu-blue-600);font-weight:700">⏱ Kirim ulang dalam 00:45</span>
            </p>

            <div style="display:flex;justify-content:space-between;margin-top:16px;font-size:12px">
                <a href="{{ route('login') }}" style="color:var(--edu-blue-600);text-decoration:none;font-weight:600">&larr; Kembali ke Login</a>
                <a href="#" style="color:var(--edu-slate-500);text-decoration:none">Bantuan &nearr;</a>
            </div>
        </div>

        <div class="wft">
            <b>JosBook</b> &bull; SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah &nearr;</a>
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

        let seconds = {{ $sisaKirimUlang ?? 45 }};
        const timerEl = document.getElementById('timer_text');
        const showResendLink = () => {
            timerEl.innerHTML = '<a href="#" onclick="document.getElementById(\'resendForm\').submit();return false;" style="color:#1d4ed8;font-weight:700">Kirim Ulang Sekarang</a>';
        };
        if (seconds <= 0) {
            showResendLink();
        }
        const interval = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(interval);
                showResendLink();
            } else {
                timerEl.textContent = `⏱ Kirim ulang dalam 00:${seconds < 10 ? '0' : ''}${seconds}`;
            }
        }, 1000);
        if (seconds <= 0) clearInterval(interval);
    </script>
</body>
</html>
