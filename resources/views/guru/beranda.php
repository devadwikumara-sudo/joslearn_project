@extends('layouts.app')

@section('title', 'Beranda Guru — JosBook')

@section('content')
{{-- Modern Teacher Hero Banner --}}
<div class="dashboard-hero">
    <div class="dashboard-hero-glow"></div>
    <div class="dashboard-hero-content">
        <div class="dashboard-hero-pill">
            <span class="dashboard-hero-pulse"></span>
            <span>Tahun Ajaran {{ $tahunAjaran?->nama ?? '2024/2025' }} · Semester {{ $semesterBerjalan ?? 'Ganjil' }}</span>
        </div>
        <h2 class="dashboard-hero-title">Selamat Datang, {{ $namaGuru }}</h2>
        <p class="dashboard-hero-desc">Portal Akademik Pendidik — Kelola pengisian nilai mapel, kenaikan kelas, dan pantau perkembangan siswa secara real-time.</p>
        
        <div class="st-mini-badges" style="margin-top:14px">
            @if($mapelDiajar->isNotEmpty())
                <span class="st-mini-badge boy">📚 Pengampu {{ $mapelDiajar->pluck('nama')->join(', ') }}</span>
            @endif
            @foreach($kelasWali as $kw)
                <span class="st-mini-badge girl">🎓 Wali Kelas {{ $kw->nama }}</span>
            @endforeach
            <span class="st-mini-badge" style="background:rgba(255,255,255,0.2);color:#fff">📅 {{ $tanggalHariIni }}</span>
        </div>
    </div>
    <div class="dashboard-hero-badge">
        <div class="dashboard-hero-icon">
            <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook" class="dashboard-hero-logo">
        </div>
    </div>
</div>

@unless($adaPenugasan)
    <div class="note" style="margin-bottom:18px">
        ℹ️ Akun Anda belum memiliki penugasan mata pelajaran atau perwalian kelas. Silakan hubungi Administrator sekolah.
    </div>
@endunless

{{-- Role & Assignment Cards Grid --}}
<div class="dashboard-main-grid">
    {{-- Left Column: Assignments & Main Tasks --}}
    <div class="dashboard-col">
        @if($mapelDiajar->isNotEmpty())
            <div class="card">
                <div class="card-header-modern">
                    <div class="card-title-group">
                        <span class="card-title-icon">📚</span>
                        <h3>Guru Pengampu Mapel</h3>
                    </div>
                    <span class="card-header-badge">{{ $mapelDiajar->count() }} Mata Pelajaran</span>
                </div>
                
                <p class="sub" style="margin-bottom:12px">Mata pelajaran yang Anda ampu pada semester aktif:</p>
                <div class="bar" style="flex-wrap:wrap;gap:8px;margin-bottom:16px">
                    @foreach($mapelDiajar as $m)
                        <span class="bd ok" style="padding:5px 12px;font-size:12px">📖 {{ $m->nama }}</span>
                    @endforeach
                </div>

                <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:16px">
                    <b style="display:block;font-size:12px;color:#475569;margin-bottom:6px">KELAS YANG DIAJAR:</b>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($kelasDiajar as $kd)
                            <span class="badge-subtle" style="font-size:12px;padding:3px 9px">{{ $kd->nama }}</span>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('guru.input-nilai-mapel') }}" class="btn a">📝 Input Nilai Mapel</a>
                    <a href="{{ route('guru.import-data') }}" class="btn o">📥 Import Nilai Excel</a>
                </div>
            </div>
        @endif

        @foreach($kelasWali as $kelas)
            <div class="card">
                <div class="card-header-modern">
                    <div class="card-title-group">
                        <span class="card-title-icon">🎓</span>
                        <h3>Wali Kelas {{ $kelas->nama }}</h3>
                    </div>
                    <span class="card-header-badge">{{ $kelas->jumlah_siswa }} Siswa Aktif</span>
                </div>
                <p class="sub" style="margin-bottom:16px">Sebagai wali kelas, Anda berwenang memantau seluruh biodata siswa, catatan kehadiran, ekstrakurikuler, dan memproses kenaikan kelas.</p>

                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('guru.kenaikan-mutasi', ['tab' => 'kenaikan', 'kelas' => $kelas->nama]) }}" class="btn a">↗ Kenaikan Kelas</a>
                    <a href="{{ route('guru.data-siswa', ['kelas' => $kelas->nama]) }}" class="btn o">👥 Lihat Siswa Kelas</a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Right Column: Quick Links & Summary --}}
    <div class="dashboard-col">
        <div class="card">
            <div class="card-header-modern">
                <div class="card-title-group">
                    <span class="card-title-icon">⚡</span>
                    <h3>Akses Cepat Pendidik</h3>
                </div>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:10px">
                @if($adaPenugasan)
                    <a href="{{ route('guru.data-siswa') }}" class="submetric-pill" style="justify-content:flex-start">
                        <span class="submetric-pill-icon" style="background:#e0f2fe;color:#0284c7">👥</span>
                        <div>
                            <b style="display:block;font-size:13px;color:#0f172a">Daftar Siswa</b>
                            <small style="color:#64748b">Cari dan lihat profil siswa lengkap</small>
                        </div>
                    </a>
                @endif

                @if($mapelDiajar->isNotEmpty() || $kelasWali->isNotEmpty())
                    <a href="{{ route('guru.import-data') }}" class="submetric-pill" style="justify-content:flex-start">
                        <span class="submetric-pill-icon" style="background:#dcfce7;color:#15803d">📥</span>
                        <div>
                            <b style="display:block;font-size:13px;color:#0f172a">Import Data Excel</b>
                            <small style="color:#64748b">Impor nilai raport dan ketidakhadiran</small>
                        </div>
                    </a>
                @endif

                <a href="{{ route('guru.log-aktivitas') }}" class="submetric-pill" style="justify-content:flex-start">
                    <span class="submetric-pill-icon" style="background:#f1f5f9;color:#475569">⏱</span>
                    <div>
                        <b style="display:block;font-size:13px;color:#0f172a">Log Aktivitas</b>
                        <small style="color:#64748b">Riwayat pembaruan yang Anda lakukan</small>
                    </div>
                </a>

                <a href="{{ route('guru.setting') }}" class="submetric-pill" style="justify-content:flex-start">
                    <span class="submetric-pill-icon" style="background:#fef3c7;color:#b45309">⚙️</span>
                    <div>
                        <b style="display:block;font-size:13px;color:#0f172a">Pengaturan Akun</b>
                        <small style="color:#64748b">Ubah profil dan kata sandi login</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
