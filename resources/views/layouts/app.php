<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(Auth::check())
        <meta name="batch-import-url" content="{{ Auth::user()->peran === 'guru' ? route('guru.import.save-batch') : route('admin.import.save-batch') }}">
    @endif
    <meta name="app-base-url" content="{{ url('/') }}">
    <title>@yield('title', 'JosBook — Portal Akademik')</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
    @stack('styles')
</head>
<body>

<div class="app">
    {{-- SIDEBAR JOSBOOK --}}
    <aside class="josbook-sidebar">
        <!-- Bagian Atas: Logo & Navigasi -->
        <div class="josbook-sidebar-content">
            <!-- Logo & Header -->
            <div class="josbook-sidebar-brand">
                <div class="josbook-sidebar-logo">
                    <img src="{{ asset('images/josbook-logo.png') }}" alt="Logo JosBook" width="48" height="48">
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight leading-tight">JosBook</h1>
                    <p class="text-[10px] tracking-wider text-blue-200 uppercase font-medium">Portal Akademik</p>
                </div>
            </div>

            <!-- Daftar Menu Navigasi -->
            @if(Auth::check() && Auth::user()->peran === 'admin')
            {{-- ── MENU ADMIN ── --}}
            <nav class="josbook-sidebar-nav" aria-label="Menu admin">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}" data-menu="dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.data-siswa') }}" class="{{ (request()->routeIs('admin.data-siswa*') || request()->routeIs('admin.import.siswa*')) ? 'on' : '' }}" data-menu="siswa">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    <span>Data Siswa</span>
                </a>

                <a href="{{ route('admin.form-pendataan') }}" class="{{ request()->routeIs('admin.form-pendataan*') ? 'on' : '' }}" data-menu="pendataan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Form Pendataan</span>
                </a>

                <a href="{{ route('admin.kenaikan-kelas') }}" class="{{ request()->routeIs('admin.kenaikan-kelas*') ? 'on' : '' }}" data-menu="kenaikan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Kenaikan Kelas</span>
                </a>

                <a href="{{ route('admin.mutasi-siswa') }}" class="{{ request()->routeIs('admin.mutasi-siswa*') ? 'on' : '' }}" data-menu="mutasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span>Mutasi Siswa</span>
                </a>

                <a href="{{ route('admin.data-guru') }}" class="{{ request()->routeIs('admin.data-guru*') ? 'on' : '' }}" data-menu="guru">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Data Guru</span>
                </a>

                <a href="{{ route('admin.alumni') }}" class="{{ request()->routeIs('admin.alumni*') ? 'on' : '' }}" data-menu="alumni">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    <span>Alumni &amp; Kontrol Induk</span>
                </a>

                <a href="{{ route('admin.pelacakan-alumni') }}" class="{{ request()->routeIs('admin.pelacakan-alumni*') ? 'on' : '' }}" data-menu="pelacakan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-4.35-4.35"></path></svg>
                    <span>Pelacakan Alumni</span>
                </a>


                <a href="{{ route('admin.import.nilai') }}" class="{{ request()->routeIs('admin.import.nilai*') ? 'on' : '' }}" data-menu="imp-nilai">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Import Nilai</span>
                </a>

                <a href="{{ route('admin.import.kehadiran') }}" class="{{ request()->routeIs('admin.import.kehadiran*') ? 'on' : '' }}" data-menu="imp-hadir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Import Kehadiran</span>
                </a>

                <a href="{{ route('admin.import.ekskul') }}" class="{{ request()->routeIs('admin.import.ekskul*') ? 'on' : '' }}" data-menu="imp-ekskul">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                    <span>Import Ekskul</span>
                </a>

                <a href="{{ route('admin.laporan') }}" class="{{ request()->routeIs('admin.laporan*') ? 'on' : '' }}" data-menu="laporan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Laporan</span>
                </a>

                <a href="{{ route('admin.log-aktivitas') }}" class="{{ request()->routeIs('admin.log-aktivitas*') ? 'on' : '' }}" data-menu="log">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Log Aktivitas</span>
                </a>

                <a href="{{ route('admin.master-kelas') }}" class="{{ request()->routeIs('admin.master-kelas*') ? 'on' : '' }}" data-menu="master">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Master Kelas &amp; Mapel</span>
                </a>

                <a href="{{ route('admin.cek-kualitas-data') }}" class="{{ request()->routeIs('admin.cek-kualitas-data*') ? 'on' : '' }}" data-menu="cek">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Cek Kualitas Data</span>
                </a>

                <a href="{{ route('admin.sampah') }}" class="{{ request()->routeIs('admin.sampah*') ? 'on' : '' }}" data-menu="sampah">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-11 0 1 14h12l1-14M10 11v5m4-5v5"></path></svg>
                    <span>Tempat Sampah</span>
                </a>

                <a href="{{ route('admin.setting') }}" class="{{ request()->routeIs('admin.setting*') ? 'on' : '' }}" data-menu="setting">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Setting</span>
                </a>
            </nav>
            @else
            {{-- ── MENU GURU ── --}}
            <nav class="josbook-sidebar-nav" aria-label="Menu guru">
                <a href="{{ route('guru.beranda') }}" class="{{ request()->routeIs('guru.beranda') ? 'on' : '' }}" data-menu="beranda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Beranda</span>
                </a>

                <a href="{{ route('guru.data-siswa') }}" class="{{ request()->routeIs('guru.data-siswa*') ? 'on' : '' }}" data-menu="siswa">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    <span>Data Siswa</span>
                </a>

                <a href="{{ route('guru.import-nilai') }}" class="{{ request()->routeIs('guru.import-nilai*') ? 'on' : '' }}" data-menu="imp-nilai">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Import Nilai</span>
                </a>

                <a href="{{ route('guru.import-ketidakhadiran') }}" class="{{ request()->routeIs('guru.import-ketidakhadiran*') ? 'on' : '' }}" data-menu="imp-hadir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Import Kehadiran</span>
                </a>

                <a href="{{ route('guru.import-ekskul') }}" class="{{ request()->routeIs('guru.import-ekskul*') ? 'on' : '' }}" data-menu="imp-ekskul">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                    <span>Import Ekskul</span>
                </a>

                <a href="{{ route('guru.input-nilai-mapel') }}" class="{{ request()->routeIs('guru.input-nilai-mapel*') ? 'on' : '' }}" data-menu="input-nilai">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Input Nilai Mapel</span>
                </a>
            </nav>
            @endif
        </div>

        @php
            $userName = Auth::user()->nama ?? Auth::user()->username ?? 'Pengguna';
            $words = explode(' ', trim($userName));
            $initials = count($words) >= 2 
                ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) 
                : strtoupper(substr($userName, 0, 2));
            $userRole = Auth::user()->peran ?? 'admin';
            $roleLabel = $userRole === 'admin' ? 'Akses penuh' : 'Guru Mapel';
        @endphp

        <!-- Bagian Bawah: Profile User -->
        <div class="josbook-sidebar-footer">
            <button type="button" class="josbook-sidebar-user" id="user-tombol" aria-haspopup="true" aria-expanded="false" onclick="toggleUserMenu()">
                <span class="josbook-sidebar-avatar" data-sidebar-initials>{{ $initials }}</span>
                <span class="josbook-sidebar-user-text">
                    <strong data-sidebar-name>{{ $userName }}</strong>
                    <small data-sidebar-role>{{ $roleLabel }}</small>
                </span>
                <svg class="josbook-sidebar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div class="user-menu" id="user-menu" hidden>
                <a href="{{ route('ganti-password') }}">
                    <svg style="width:16px;height:16px;opacity:0.7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    Ganti password
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" id="btn-keluar">
                        <svg style="width:16px;height:16px;opacity:0.7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="mn">
        @if(session('success'))
            <div class="note" style="background:#e1f4ea;border-color:#a3d9b8;color:#1f5c3a;margin-bottom:14px">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="note" style="background:#fbe5e1;border-color:#efc6bf;color:#8b2a1e;margin-bottom:14px">❌ {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="note" style="margin-bottom:14px">ℹ️ {{ session('info') }}</div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Toast Container --}}
<div id="ts"></div>

<script>
function toggleUserMenu(){
    var btn = document.getElementById('user-tombol');
    var menu = document.getElementById('user-menu');
    if (!menu) return;
    var isHidden = menu.hasAttribute('hidden');
    if (isHidden) {
        menu.removeAttribute('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    } else {
        menu.setAttribute('hidden', '');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
}

document.addEventListener('click', function(e){
    var menu = document.getElementById('user-menu');
    var btn = document.getElementById('user-tombol');
    if (menu && !menu.hasAttribute('hidden') && !menu.contains(e.target) && btn && !btn.contains(e.target)) {
        menu.setAttribute('hidden', '');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
});

</script>

<script src="{{ asset('js/xlsx.full.min.js') }}"></script>
<script src="{{ asset('js/josbook-excel.js') }}?v={{ file_exists(public_path('js/josbook-excel.js')) ? filemtime(public_path('js/josbook-excel.js')) : time() }}"></script>
@stack('scripts')
<script>
function toast(message, duration) {
    const container = document.getElementById('ts');
    if (!container) return;

    container.textContent = message;
    container.style.display = 'block';
    window.setTimeout(function() {
        container.style.display = 'none';
    }, duration || 2500);
}
</script>
</body>
</html>
