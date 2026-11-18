@extends('layouts.app')

@section('title', 'Dashboard — JosBook')

@section('content')
@php
    $persenLaki = ($totalSiswaAktif ?? 0) > 0 ? round((($totalLaki ?? 0) / $totalSiswaAktif) * 100) : 50;
    $persenPerempuan = ($totalSiswaAktif ?? 0) > 0 ? round((($totalPerempuan ?? 0) / $totalSiswaAktif) * 100) : 50;
    $persenX = ($totalSiswaAktif ?? 0) > 0 ? round((($angkatanX ?? 0) / $totalSiswaAktif) * 100) : 0;
    $persenXI = ($totalSiswaAktif ?? 0) > 0 ? round((($angkatanXI ?? 0) / $totalSiswaAktif) * 100) : 0;
    $persenXII = ($totalSiswaAktif ?? 0) > 0 ? round((($angkatanXII ?? 0) / $totalSiswaAktif) * 100) : 0;
@endphp

{{-- Modern Hero Banner (Blue Theme with Education Silhouettes) --}}
<div class="dashboard-hero">
    <div class="dashboard-hero-glow"></div>
    {{-- Decorative education silhouettes --}}
    <svg class="dashboard-hero-silhouette" viewBox="0 0 1200 200" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMaxYMax slice">
        {{-- School building silhouette --}}
        <rect x="720" y="80" width="120" height="120" rx="3" fill="currentColor"/>
        <rect x="740" y="60" width="80" height="20" rx="2" fill="currentColor"/>
        <rect x="760" y="42" width="40" height="18" rx="2" fill="currentColor"/>
        <polygon points="780,22 798,42 762,42" fill="currentColor"/>
        {{-- Windows --}}
        <rect x="735" y="96" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="755" y="96" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="793" y="96" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="813" y="96" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="735" y="130" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="755" y="130" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="793" y="130" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="813" y="130" width="14" height="18" rx="2" fill="rgba(255,255,255,0.08)"/>
        {{-- Door --}}
        <rect x="770" y="160" width="20" height="40" rx="2" fill="rgba(255,255,255,0.1)"/>
        {{-- Flag pole --}}
        <line x1="780" y1="4" x2="780" y2="22" stroke="currentColor" stroke-width="2"/>
        <rect x="782" y="4" width="16" height="10" rx="1" fill="currentColor" opacity="0.7"/>
        {{-- Second building (smaller, library) --}}
        <rect x="870" y="110" width="80" height="90" rx="3" fill="currentColor"/>
        <rect x="880" y="94" width="60" height="16" rx="2" fill="currentColor"/>
        <polygon points="910,78 948,94 872,94" fill="currentColor"/>
        <rect x="884" y="124" width="12" height="16" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="902" y="124" width="12" height="16" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="920" y="124" width="12" height="16" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="884" y="152" width="12" height="16" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="920" y="152" width="12" height="16" rx="2" fill="rgba(255,255,255,0.08)"/>
        <rect x="900" y="168" width="16" height="32" rx="2" fill="rgba(255,255,255,0.1)"/>
        {{-- Trees --}}
        <circle cx="690" cy="160" r="22" fill="currentColor"/>
        <rect x="687" y="175" width="6" height="25" rx="2" fill="currentColor"/>
        <circle cx="980" cy="150" r="26" fill="currentColor"/>
        <rect x="977" y="170" width="6" height="30" rx="2" fill="currentColor"/>
        <circle cx="1020" cy="162" r="18" fill="currentColor"/>
        <rect x="1017" y="175" width="5" height="25" rx="2" fill="currentColor"/>
        {{-- Graduation cap --}}
        <polygon points="1070,120 1110,105 1150,120 1110,135" fill="currentColor"/>
        <rect x="1107" y="120" width="6" height="30" rx="1" fill="currentColor"/>
        <path d="M1090,125 L1090,148 Q1110,160 1130,148 L1130,125" fill="currentColor" opacity="0.6"/>
        <circle cx="1090" cy="148" r="3" fill="currentColor"/>
        <line x1="1090" y1="148" x2="1082" y2="168" stroke="currentColor" stroke-width="2"/>
        {{-- Books stack --}}
        <rect x="1060" y="168" width="40" height="8" rx="2" fill="currentColor"/>
        <rect x="1055" y="158" width="44" height="8" rx="2" fill="currentColor" opacity="0.7"/>
        <rect x="1058" y="148" width="38" height="8" rx="2" fill="currentColor" opacity="0.5"/>
        {{-- Ground line --}}
        <rect x="650" y="198" width="560" height="4" rx="2" fill="currentColor" opacity="0.3"/>
    </svg>
    <div class="dashboard-hero-content">
        <div class="dashboard-hero-pill">
            <span class="dashboard-hero-pulse"></span>
            <span>Tahun Ajaran {{ $taAktif ?? '2024/2025' }}</span>
        </div>
        <h2 class="dashboard-hero-title" style="color:#ffffff !important;font-size:32px !important;font-weight:900 !important;text-shadow:0 2px 14px rgba(0,0,0,0.4),0 1px 3px rgba(0,0,0,0.3) !important;margin:0 0 6px 0 !important;line-height:1.2 !important">Dashboard Akademik</h2>
        <p class="dashboard-hero-subtitle" style="color:#e0f2fe !important;font-size:13.5px !important;font-weight:700 !important;letter-spacing:0.5px !important;text-transform:uppercase !important;margin:0 0 10px 0 !important;text-shadow:0 1px 4px rgba(0,0,0,0.3) !important">SMA Negeri 1 Rejoso — Sistem Informasi Pendataan Siswa</p>
        <p class="dashboard-hero-desc" style="color:#ffffff !important;font-size:14.5px !important;line-height:1.65 !important;font-weight:500 !important;text-shadow:0 1px 6px rgba(0,0,0,0.25) !important;margin:0 !important;max-width:600px !important">Pantau data siswa, kehadiran, capaian nilai raport, mutasi, dan rekapitulasi sekolah secara real-time dan terintegrasi.</p>
    </div>
    <div class="dashboard-hero-badge">
        <div class="dashboard-hero-icon">
            <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook" class="dashboard-hero-logo">
        </div>
    </div>
</div>

{{-- 4 Primary Metric Cards (Cohesive, High-Impact Top Row) --}}
<div class="dashboard-stats-grid">
    {{-- Card 1: Siswa Aktif --}}
    <div class="card st">
        <div>
            <div class="st-head">
                <span>Siswa Aktif</span>
                <div class="st-icon st-icon-blue">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                </div>
            </div>
            <b>{{ number_format($totalSiswaAktif ?? 0) }}</b>
        </div>
        <div class="st-mini-badges">
            <span class="st-mini-badge boy">👦 {{ number_format($totalLaki ?? 0) }} Laki-laki</span>
            <span class="st-mini-badge girl">👧 {{ number_format($totalPerempuan ?? 0) }} Perempuan</span>
        </div>
    </div>

    {{-- Card 2: Guru & Pendidik --}}
    <div class="card st">
        <div>
            <div class="st-head">
                <span>Tenaga Pendidik</span>
                <div class="st-icon st-icon-emerald">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <b>{{ number_format($totalGuru ?? 0) }}</b>
        </div>
        <div>
            <span class="st-foot-chip text-emerald">● Pengajar &amp; Wali Kelas</span>
        </div>
    </div>

    {{-- Card 3: Total Alumni --}}
    <div class="card st">
        <div>
            <div class="st-head">
                <span>Total Alumni</span>
                <div class="st-icon st-icon-purple">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 011.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
            </div>
            <b>{{ number_format($totalAlumni ?? 0) }}</b>
        </div>
        <div>
            <span class="st-foot-chip text-purple">● Kontrol Induk Terdata</span>
        </div>
    </div>

    {{-- Card 4: Kelengkapan Berkas --}}
    <div class="card st">
        <div>
            <div class="st-head">
                <span>Kelengkapan Berkas</span>
                <div class="st-icon st-icon-indigo">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <b>{{ $persenLengkap ?? 100 }}%</b>
        </div>
        <div>
            <div class="st-progress">
                <div class="st-progress-fill" style="width:{{ $persenLengkap ?? 100 }}%"></div>
            </div>
            <span class="st-progress-label">{{ ($persenLengkap ?? 100) >= 90 ? '● Data Sangat Lengkap' : '● Perlu Verifikasi Berkas' }}</span>
        </div>
    </div>
</div>

{{-- Secondary Demographics & Status Strip --}}
<div class="dashboard-submetrics-strip">
    <div class="submetric-gender">
        <div class="submetric-gender-header">
            <span>Rasio Gender Siswa Aktif</span>
            <span><strong>{{ number_format($totalLaki ?? 0) }} L</strong> ({{ $persenLaki }}%) · <strong>{{ number_format($totalPerempuan ?? 0) }} P</strong> ({{ $persenPerempuan }}%)</span>
        </div>
        <div class="submetric-gender-bar">
            <div class="submetric-gender-bar-boy" style="width:{{ $persenLaki }}%" title="{{ $persenLaki }}% Siswa Laki-laki"></div>
            <div class="submetric-gender-bar-girl" style="width:{{ $persenPerempuan }}%" title="{{ $persenPerempuan }}% Siswa Perempuan"></div>
        </div>
    </div>

    <div class="submetric-pills">
        <a href="{{ route('admin.kenaikan-mutasi', ['tab' => 'mutasi']) }}" class="submetric-pill">
            <span class="submetric-pill-icon orange">⇄</span>
            <span><b>{{ number_format($totalMutasi ?? 0) }}</b> Mutasi / Pindah</span>
        </a>
        <a href="{{ route('admin.setting', ['tab' => 'sampah']) }}" class="submetric-pill">
            <span class="submetric-pill-icon rose">🗑</span>
            <span><b>{{ number_format($totalSampah ?? 0) }}</b> di Tempat Sampah</span>
        </a>
    </div>
</div>

{{-- Main Asymmetric 2-Column Dashboard Grid --}}
<div class="dashboard-main-grid">
    {{-- Left Column: Core Analytics & Compositions --}}
    <div class="dashboard-col">
        {{-- Card: Distribusi Siswa per Angkatan (dengan popup detail per kelas) --}}
        <div class="card">
            <div class="card-header-modern">
                <div class="card-title-group">
                    <span class="card-title-icon">🎓</span>
                    <h3>Distribusi Siswa per Angkatan</h3>
                </div>
                <span class="card-header-badge">3 Tingkatan</span>
            </div>
            <div class="ang-horizontal-grid">
                <div class="ang-card-modern grade-x" onclick="openGradeModal('X')" title="Klik untuk lihat detail kelas">
                    <div class="ang-card-head">
                        <span class="ang-card-tag">Tingkat 10</span>
                        <span class="ang-card-share">{{ $persenX }}%</span>
                    </div>
                    <div class="ang-card-label">Kelas X</div>
                    <div class="ang-card-value">{{ number_format($angkatanX ?? 0) }} <span>Siswa</span></div>
                    <small style="color:var(--admin-text-muted);font-size:11px">Angkatan Masuk Terbaru</small>
                    <span class="ang-card-btn">📊 Lihat Detail Kelas</span>
                </div>
                <div class="ang-card-modern grade-xi" onclick="openGradeModal('XI')" title="Klik untuk lihat detail kelas">
                    <div class="ang-card-head">
                        <span class="ang-card-tag">Tingkat 11</span>
                        <span class="ang-card-share">{{ $persenXI }}%</span>
                    </div>
                    <div class="ang-card-label">Kelas XI</div>
                    <div class="ang-card-value">{{ number_format($angkatanXI ?? 0) }} <span>Siswa</span></div>
                    <small style="color:var(--admin-text-muted);font-size:11px">Tahun Kedua</small>
                    <span class="ang-card-btn">📊 Lihat Detail Kelas</span>
                </div>
                <div class="ang-card-modern grade-xii" onclick="openGradeModal('XII')" title="Klik untuk lihat detail kelas">
                    <div class="ang-card-head">
                        <span class="ang-card-tag">Tingkat 12</span>
                        <span class="ang-card-share">{{ $persenXII }}%</span>
                    </div>
                    <div class="ang-card-label">Kelas XII</div>
                    <div class="ang-card-value">{{ number_format($angkatanXII ?? 0) }} <span>Siswa</span></div>
                    <small style="color:var(--admin-text-muted);font-size:11px">Persiapan Kelulusan</small>
                    <span class="ang-card-btn">📊 Lihat Detail Kelas</span>
                </div>
            </div>
        </div>

        {{-- Card: Rekapitulasi Alumni per Tahun Lulus --}}
        <div class="card">
            <div class="card-header-modern">
                <div class="card-title-group">
                    <span class="card-title-icon">📜</span>
                    <h3>Alumni per Tahun Kelulusan</h3>
                </div>
                <a href="{{ route('admin.alumni') }}" class="card-header-link">Buka Menu Alumni →</a>
            </div>
            <div class="bar" style="flex-wrap:wrap;gap:8px;margin:4px 0">
                @if(!empty($alumniPerTahun))
                    @foreach($alumniPerTahun as $thn => $jml)
                        <span class="bd ok" style="padding:6px 14px;font-size:12.5px">{{ $thn }}: <b>{{ $jml }}</b> lulusan</span>
                    @endforeach
                @else
                    <span class="sub" style="margin:0">Belum ada data alumni tercatat</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Right Column: Actionable Alerts & Live Feeds --}}
    <div class="dashboard-col">
        {{-- Card: Perlu Dilengkapi (Action Required) --}}
        <div class="card">
            <div class="card-header-modern">
                <div class="card-title-group">
                    <span class="card-title-icon">⚠️</span>
                    <h3>Perlu Dilengkapi</h3>
                </div>
                @if(!empty($perluDilengkapi) && count($perluDilengkapi) > 0)
                    <span class="card-header-badge alert">{{ count($perluDilengkapi) }} Perlu Perhatian</span>
                @endif
            </div>

            @if(!empty($perluDilengkapi) && count($perluDilengkapi) > 0)
                <div class="needs-completion-list">
                    @foreach($perluDilengkapi as $item)
                    @php
                        $words = explode(' ', trim($item['nama']));
                        $initial = count($words) >= 2 
                            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) 
                            : strtoupper(substr($item['nama'], 0, 2));
                    @endphp
                    <div class="needs-completion-modern-item">
                        <div class="needs-completion-avatar">{{ $initial }}</div>
                        <div class="needs-completion-details">
                            <div class="needs-completion-name">
                                <span>{{ $item['nama'] }}</span>
                                <span class="badge-subtle">{{ $item['kelas'] }}</span>
                            </div>
                            <div class="needs-completion-missing-tags">
                                @foreach(array_slice($item['missing'], 0, 2) as $missingField)
                                    <span class="missing-tag">{{ $missingField }}</span>
                                @endforeach
                                @if(count($item['missing']) > 2)
                                    <span class="missing-more-tag">+{{ count($item['missing']) - 2 }} lainnya</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.data-siswa.edit', $item['id']) }}" class="btn a" style="padding:6px 12px;font-size:11.5px">Lengkapi</a>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="all-complete-state">
                    <span class="all-complete-icon">🎉</span>
                    <b>Semua data siswa aktif sudah lengkap!</b>
                    <small>Tidak ada berkas atau biodata yang perlu dilengkapi.</small>
                </div>
            @endif
        </div>

        {{-- Card: Aktivitas Terbaru (Live Stream) --}}
        <div class="card">
            <div class="card-header-modern">
                <div class="card-title-group">
                    <span class="card-title-icon">⏱</span>
                    <h3>Aktivitas Terbaru</h3>
                </div>
                <a href="{{ route('admin.setting', ['tab' => 'log']) }}" class="card-header-link">Log Lengkap →</a>
            </div>

            @if(!empty($aktivitasTerbaru) && count($aktivitasTerbaru) > 0)
                <div class="activity-timeline-modern">
                    @foreach($aktivitasTerbaru as $log)
                    <div class="activity-timeline-item">
                        <div class="activity-timeline-bullet"></div>
                        <div class="activity-timeline-content">
                            {{ $log->keterangan ?? $log->aksi }}
                        </div>
                        <div class="activity-timeline-time">
                            {{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <span class="sub" style="display:block;padding:12px 0;margin:0">Belum ada catatan aktivitas sistem.</span>
            @endif
        </div>
    </div>
</div>

{{-- Grade Detail Modal --}}
<div id="grade-modal-backdrop" class="grade-modal-backdrop" onclick="closeGradeModal(event)">
    <div class="grade-modal-container" onclick="event.stopPropagation()">
        <div class="grade-modal-head">
            <div class="grade-modal-title-wrap">
                <div class="grade-modal-icon" id="gm-icon"></div>
                <div>
                    <h3 class="grade-modal-title" id="gm-title"></h3>
                    <p class="grade-modal-desc" id="gm-desc"></p>
                </div>
            </div>
            <button class="grade-modal-close" onclick="closeGradeModal()">&times;</button>
        </div>
        <div class="grade-modal-metrics" id="gm-metrics"></div>
        <div class="grade-modal-body" id="gm-body"></div>
        <div class="grade-modal-foot">
            <span class="sub" id="gm-foot-label"></span>
            <button class="btn o" onclick="closeGradeModal()">Tutup</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var komposisiKelas = @json($komposisiKelas ?? []);

    var gradeConfig = {
        'X':   { label:'Kelas X',   subtitle:'Angkatan Masuk Terbaru',  icon:'🔵', iconBg:'#dbeafe', iconColor:'#1d4ed8', accentFrom:'#38bdf8', accentTo:'#0284c7', tagBg:'#dbeafe', tagColor:'#1d4ed8' },
        'XI':  { label:'Kelas XI',  subtitle:'Tahun Kedua',             icon:'🟣', iconBg:'#e0e7ff', iconColor:'#4338ca', accentFrom:'#6366f1', accentTo:'#4338ca', tagBg:'#e0e7ff', tagColor:'#4338ca' },
        'XII': { label:'Kelas XII', subtitle:'Persiapan Kelulusan',     icon:'🟪', iconBg:'#ede9fe', iconColor:'#6d28d9', accentFrom:'#8b5cf6', accentTo:'#6d28d9', tagBg:'#ede9fe', tagColor:'#6d28d9' }
    };

    function filterByTingkat(tingkat) {
        return komposisiKelas.filter(function(k) {
            var name = k.kelas.toUpperCase();
            if (tingkat === 'XII') return name.indexOf('XII') === 0;
            if (tingkat === 'XI')  return name.indexOf('XI') === 0 && name.indexOf('XII') !== 0;
            return name.indexOf('X') === 0 && name.indexOf('XI') !== 0 && name.indexOf('XII') !== 0;
        });
    }

    window.openGradeModal = function(tingkat) {
        var cfg = gradeConfig[tingkat];
        if (!cfg) return;

        var items = filterByTingkat(tingkat);
        var totalL = 0, totalP = 0, totalAll = 0, totalPersen = 0;
        items.forEach(function(k) {
            totalL += k.l;
            totalP += k.p;
            totalAll += k.total;
            totalPersen += k.persen;
        });
        var avgPersen = items.length > 0 ? Math.round(totalPersen / items.length) : 0;

        // Icon
        var iconEl = document.getElementById('gm-icon');
        iconEl.textContent = cfg.icon;
        iconEl.style.background = cfg.iconBg;

        // Title
        document.getElementById('gm-title').textContent = 'Detail ' + cfg.label;
        document.getElementById('gm-title').style.color = cfg.accentTo;
        document.getElementById('gm-desc').textContent = cfg.subtitle + ' — ' + items.length + ' kelas terdaftar';

        // Metrics
        var metricsHtml = '';
        var metricData = [
            { label:'Total Siswa',  val: totalAll,    color: cfg.accentTo },
            { label:'Laki-laki',    val: totalL,      color: '#2563eb' },
            { label:'Perempuan',    val: totalP,      color: '#db2777' },
            { label:'Kelengkapan',  val: avgPersen+'%', color: avgPersen >= 90 ? '#059669' : '#d97706' }
        ];
        metricData.forEach(function(m) {
            metricsHtml += '<div class="grade-modal-metric-card">' +
                '<span class="grade-modal-metric-label">' + m.label + '</span>' +
                '<span class="grade-modal-metric-val" style="color:' + m.color + '">' + m.val + '</span>' +
                '</div>';
        });
        document.getElementById('gm-metrics').innerHTML = metricsHtml;

        // Table
        var tableHtml = '<div class="tw"><table>' +
            '<thead><tr>' +
            '<th>Kelas</th><th>Laki-laki</th><th>Perempuan</th><th>Total</th><th>Kelengkapan</th>' +
            '</tr></thead><tbody>';
        if (items.length === 0) {
            tableHtml += '<tr><td colspan="5" style="text-align:center;color:var(--admin-text-muted);padding:24px">Tidak ada data kelas untuk tingkat ini.</td></tr>';
        } else {
            items.forEach(function(k) {
                var barClass = k.persen >= 90 ? 'ok' : 'w';
                tableHtml += '<tr>' +
                    '<td><strong>' + k.kelas + '</strong></td>' +
                    '<td>' + k.l + '</td>' +
                    '<td>' + k.p + '</td>' +
                    '<td><b>' + k.total + '</b></td>' +
                    '<td>' +
                        (k.total > 0
                            ? '<div style="display:flex;align-items:center;gap:8px">' +
                              '<div class="cell-progress"><div class="cell-progress-bar ' + barClass + '" style="width:' + k.persen + '%"></div></div>' +
                              '<span class="bd ' + barClass + '">' + k.persen + '%</span></div>'
                            : '<span style="color:var(--admin-text-muted)">-</span>') +
                    '</td></tr>';
            });
        }
        tableHtml += '</tbody></table></div>';
        document.getElementById('gm-body').innerHTML = tableHtml;

        // Footer
        document.getElementById('gm-foot-label').textContent = 'Menampilkan ' + items.length + ' kelas dari tingkat ' + tingkat;

        // Show
        document.getElementById('grade-modal-backdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeGradeModal = function(e) {
        if (e && e.target !== document.getElementById('grade-modal-backdrop')) return;
        document.getElementById('grade-modal-backdrop').classList.remove('open');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.closeGradeModal();
    });
})();
</script>
@endpush

@endsection
