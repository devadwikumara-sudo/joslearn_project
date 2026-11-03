<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'JosBook')</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    @stack('styles')
</head>
<body>

{{-- Top strip --}}
<div class="strip">
    <span>🏫 SMA Negeri 1 Contoh &nbsp;·&nbsp; Tahun Ajaran 2024/2025</span>
    <span style="margin-left:auto">
        <b>{{ Auth::user()->nama ?? Auth::user()->username }}</b>
        &nbsp;|&nbsp;
        <span style="text-transform:capitalize">{{ Auth::user()->peran }}</span>
    </span>
    <div id="gr">
        <div onclick="document.getElementById('gr').classList.toggle('on')">
            <b>{{ Auth::user()->nama ?? Auth::user()->username }}</b>
            <small>{{ strtoupper(Auth::user()->peran) }}</small>
        </div>
        <div onclick="document.getElementById('md-profil').classList.add('on')">👤 Lihat Profil</div>
        <div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" style="background:none;border:none;cursor:pointer;color:inherit;font:inherit;padding:0;width:100%;text-align:left">🚪 Keluar</button>
            </form>
        </div>
    </div>
    <span onclick="document.getElementById('gr').classList.toggle('on')" style="cursor:pointer">▾</span>
</div>

<div class="app">
    {{-- Sidebar --}}
    <nav class="sb">
        {{-- Logo --}}
        <div class="logo">
            <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook Logo">
            <div>
                <b>JosBook</b>
                <small>Learn · Study · Grow</small>
            </div>
        </div>

        @if(Auth::user()->peran === 'admin')
        {{-- ─── MENU ADMIN ─── --}}
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/dashboard.svg') }}" alt=""> Dashboard
        </a>
        <a href="{{ route('admin.data-siswa') }}" class="{{ request()->routeIs('admin.data-siswa') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/siswa.svg') }}" alt=""> Data Siswa
        </a>
        <a href="{{ route('admin.data-guru') }}" class="{{ request()->routeIs('admin.data-guru') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/guru.svg') }}" alt=""> Data Guru
        </a>

        <div style="font-size:10px;color:#ffffff50;padding:10px 14px 4px;text-transform:uppercase;letter-spacing:.8px;font-weight:700">Import Data</div>
        <a href="{{ route('admin.import.nilai') }}" class="{{ request()->routeIs('admin.import.nilai') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/import.svg') }}" alt=""> Import Nilai
        </a>
        <a href="{{ route('admin.import.ekskul') }}" class="{{ request()->routeIs('admin.import.ekskul') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/ekskul.svg') }}" alt=""> Import Ekskul
        </a>
        <a href="{{ route('admin.import.kehadiran') }}" class="{{ request()->routeIs('admin.import.kehadiran') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/kehadiran.svg') }}" alt=""> Import Kehadiran
        </a>

        <div style="font-size:10px;color:#ffffff50;padding:10px 14px 4px;text-transform:uppercase;letter-spacing:.8px;font-weight:700">Manajemen</div>
        <a href="{{ route('admin.kenaikan-kelas') }}" class="{{ request()->routeIs('admin.kenaikan-kelas') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/kelas.svg') }}" alt=""> Kenaikan Kelas
        </a>
        <a href="{{ route('admin.mutasi-siswa') }}" class="{{ request()->routeIs('admin.mutasi-siswa') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/mutasi.svg') }}" alt=""> Mutasi Siswa
        </a>
        <a href="{{ route('admin.alumni') }}" class="{{ request()->routeIs('admin.alumni') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/alumni.svg') }}" alt=""> Alumni & Kontrol
        </a>
        <a href="{{ route('admin.laporan') }}" class="{{ request()->routeIs('admin.laporan') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/laporan.svg') }}" alt=""> Laporan
        </a>
        <a href="{{ route('admin.cek-kualitas-data') }}" class="{{ request()->routeIs('admin.cek-kualitas-data') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/kualitas.svg') }}" alt=""> Cek Kualitas Data
        </a>
        <a href="{{ route('admin.log-aktivitas') }}" class="{{ request()->routeIs('admin.log-aktivitas') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/log.svg') }}" alt=""> Log Aktivitas
        </a>
        <a href="{{ route('admin.setting') }}" class="{{ request()->routeIs('admin.setting') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/setting.svg') }}" alt=""> Setting
        </a>

        @else
        {{-- ─── MENU GURU ─── --}}
        <a href="{{ route('guru.beranda') }}" class="{{ request()->routeIs('guru.beranda') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/dashboard.svg') }}" alt=""> Beranda
        </a>
        <a href="{{ route('guru.data-siswa') }}" class="{{ request()->routeIs('guru.data-siswa') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/siswa.svg') }}" alt=""> Data Siswa
        </a>
        <a href="{{ route('guru.import-nilai') }}" class="{{ request()->routeIs('guru.import-nilai') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/import.svg') }}" alt=""> Import Nilai
        </a>
        <a href="{{ route('guru.import-ekskul') }}" class="{{ request()->routeIs('guru.import-ekskul') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/ekskul.svg') }}" alt=""> Import Ekskul
        </a>
        <a href="{{ route('guru.import-ketidakhadiran') }}" class="{{ request()->routeIs('guru.import-ketidakhadiran') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/kehadiran.svg') }}" alt=""> Import Ketidakhadiran
        </a>
        <a href="{{ route('guru.input-nilai-mapel') }}" class="{{ request()->routeIs('guru.input-nilai-mapel') ? 'on' : '' }}">
            <img class="ic" src="{{ asset('icons/nilai.svg') }}" alt=""> Input Nilai Mapel
        </a>
        @endif

        {{-- Role badge bottom --}}
        <div class="role" data-i="{{ strtoupper(substr(Auth::user()->peran, 0, 1)) }}">
            <div>
                <b style="font-size:12px;color:#fff">{{ Auth::user()->nama ?? Auth::user()->username }}</b>
                <div style="font-size:10px;opacity:.75;text-transform:capitalize">{{ Auth::user()->peran }}</div>
            </div>
        </div>
    </nav>

    {{-- Main content --}}
    <main class="mn">
        @if(session('success'))
            <div class="note" style="background:#e1f4ea;border-color:#a3d9b8;color:#1f5c3a">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="note" style="background:#fbe5e1;border-color:#efc6bf;color:#8b2a1e">❌ {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="note">ℹ️ {{ session('info') }}</div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Toast --}}
<div id="ts"></div>

{{-- Profile dropdown overlay close --}}
<script>
document.addEventListener('click', function(e){
    var gr = document.getElementById('gr');
    if(gr && !gr.contains(e.target) && !e.target.closest('[onclick*="gr"]')) {
        gr.classList.remove('on');
    }
});
</script>

<script src="{{ asset('js/josbook.js') }}"></script>
@stack('scripts')
</body>
</html>
