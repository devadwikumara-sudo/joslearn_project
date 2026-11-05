<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Akun — JosBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
</head>
<body style="margin:0;padding:0;background:#f6f9fe;font-family:'Plus Jakarta Sans',Inter,sans-serif">
    <div class="jc">
        <div class="wbg"></div>

        <!-- Header Logo sesuai desain -->
        <div class="wh">
            <div class="wh-logo">
                <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo">
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
            <h2>Daftar Akun</h2>
            <p class="ws">Pilih jenis akun yang akan dibuat</p>

            @if(isset($errors) && $errors->any())
            <div class="mban">
                <b>Terjadi kesalahan</b>
                <small>{{ $errors->first() }}</small>
            </div>
            @endif

            <form method="POST" action="{{ route('daftar.post') }}">
                @csrf
                <input type="hidden" name="peran" id="role_input" value="{{ old('peran', 'admin') }}">

                <!-- Tab Pemilihan Peran: ADMIN dan GURU sesuai Desain -->
                <div class="jt">
                    <span id="tab_admin" class="{{ old('peran', 'admin') === 'admin' ? 'on' : '' }}" onclick="selectRole('admin')">
                        <b>ADMIN</b>
                        <small>Pengelola sistem</small>
                    </span>
                    <span id="tab_guru" class="{{ old('peran') === 'guru' ? 'on' : '' }}" onclick="selectRole('guru')">
                        <b>GURU</b>
                        <small>Tenaga Pendidik</small>
                    </span>
                </div>

                <label for="d_n">Nama Lengkap</label>
                <div class="wf">
                    <input id="d_n" class="no-icon" name="nama" type="text" placeholder="Joko Susanto" value="{{ old('nama') }}" required>
                </div>

                <label for="d_e">Email</label>
                <div class="wf">
                    <input id="d_e" class="no-icon" name="email" type="email" placeholder="198204152008011007@sman1rejoso.sch.id" value="{{ old('email') }}" required>
                </div>

                <label for="d_i" id="label_identitas">Nomor Identitas / NIP</label>
                <div class="wf">
                    <input id="d_i" class="no-icon" name="identitas" type="text" placeholder="19820415" value="{{ old('identitas') }}" required>
                </div>

                <label for="d_p">Password</label>
                <div class="wf">
                    <input id="d_p" class="no-icon" name="password" type="password" placeholder="••••••••••••" required>
                    <span class="eye" onclick="togglePassword('d_p')">👁</span>
                </div>

                <label for="d_p2">Konfirmasi Password</label>
                <div class="wf">
                    <input id="d_p2" class="no-icon" name="password_confirmation" type="password" placeholder="••••••••••••" required>
                    <span class="eye" onclick="togglePassword('d_p2')">👁</span>
                </div>

                <button type="submit" class="wb">DAFTAR AKUN →</button>
            </form>

            <p class="wfo">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>

        <div class="wft">
            <b>JosBook</b> · SMA Negeri 1 Rejoso Nganjuk<br>
            Butuh bantuan cepat? <a href="#">Hubungi Operator Dapodik Sekolah ↗</a>
        </div>
    </div>

    <script>
        function selectRole(role) {
            document.getElementById('role_input').value = role;
            const tabAdmin = document.getElementById('tab_admin');
            const tabGuru = document.getElementById('tab_guru');
            const labelIdentitas = document.getElementById('label_identitas');
            const inputIdentitas = document.getElementById('d_i');

            if (role === 'admin') {
                tabAdmin.classList.add('on');
                tabGuru.classList.remove('on');
                labelIdentitas.innerText = 'Nomor Identitas / NIP';
                inputIdentitas.placeholder = '19820415';
            } else {
                tabGuru.classList.add('on');
                tabAdmin.classList.remove('on');
                labelIdentitas.innerText = 'Nomor Identitas / NIP Guru';
                inputIdentitas.placeholder = 'Masukkan NIP Guru';
            }
        }

        function togglePassword(id) {
            const el = document.getElementById(id);
            if (el) {
                el.type = el.type === 'password' ? 'text' : 'password';
            }
        }
    </script>
</body>
</html>
