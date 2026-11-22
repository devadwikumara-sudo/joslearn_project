<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Kata Sandi — JosBook SMA Negeri 1 Rejoso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    <link rel="stylesheet" href="{{ asset('css/josbook-auth-modern.css') }}?v={{ file_exists(public_path('css/josbook-auth-modern.css')) ? filemtime(public_path('css/josbook-auth-modern.css')) : time() }}">
</head>
<body>

    <div class="jb-edu-viewport">
        <div class="jb-edu-container" style="justify-content:center;align-items:center;padding:40px 20px">
            
            <div class="jb-edu-glass-card" style="width:100%;max-width:440px">
                <!-- Brand Header -->
                <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:18px">
                    <img src="{{ asset('images/josbook-logo.png') }}" alt="Logo JosBook" style="width:36px;height:36px;object-fit:contain">
                    <span style="font-size:18px;font-weight:800;letter-spacing:0.5px">JOSBOOK PORTAL</span>
                </div>

                <h2>Reset Kata Sandi</h2>
                <p class="subtitle">Masukkan email resmi yang terdaftar pada sistem Dapodik sekolah untuk menerima kode verifikasi.</p>

                @if(isset($errors) && $errors->any())
                <div class="jb-edu-alert-error">
                    <b>Permintaan gagal</b>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form class="jb-edu-form" method="POST" action="{{ route('reset-sandi.post') }}">
                    @csrf

                    <div class="jb-edu-field-group">
                        <label for="r_e" class="jb-edu-label">Email Resmi Sekolah *</label>
                        <div class="jb-edu-input-wrap">
                            <input id="r_e" name="email" type="text" class="jb-edu-input" placeholder="19820415...@sman1rejoso.sch.id" value="{{ old('email') }}" required autofocus>
                            <span class="jb-edu-input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                        </div>
                    </div>

                    <div style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:12px;padding:12px;font-size:12px;color:rgba(255,255,255,0.85);line-height:1.5;display:flex;gap:10px;align-items:flex-start">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <span>Kode verifikasi 6 digit akan dikirimkan langsung ke email dinas yang tertaut di sistem Dapodik SMA Negeri 1 Rejoso.</span>
                    </div>

                    <button type="submit" class="jb-edu-btn">
                        <span>Kirim Kode Verifikasi</span>
                    </button>
                </form>

                <p class="jb-edu-footer-text">
                    <a href="{{ route('login') }}">&larr; Kembali ke Halaman Login</a>
                </p>
            </div>

        </div>
    </div>

</body>
</html>
