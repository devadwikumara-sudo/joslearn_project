@extends('layouts.app')

@section('title', 'Dashboard — JosBook')

@section('content')
{{-- Modern Hero Banner (Blue Theme matching Login page) --}}
<div class="dashboard-hero">
    <div class="dashboard-hero-glow"></div>
    <div class="dashboard-hero-content">
        <div class="dashboard-hero-pill">
            <span class="dashboard-hero-pulse"></span>
            <span>Tahun Ajaran {{ $taAktif ?? '2024/2025' }}</span>
        </div>
        <h2 class="dashboard-hero-title">Dashboard Akademik</h2>
        <p class="dashboard-hero-desc">Pantau data siswa, kehadiran, capaian nilai raport, mutasi, dan rekapitulasi sekolah secara real-time dan terintegrasi.</p>
    </div>
    <div class="dashboard-hero-badge">
        <div class="dashboard-hero-icon">
            <img src="{{ asset('images/josbook-logo.png') }}" alt="JosBook">
        </div>
    </div>
</div>

{{-- 4 Stat Cards Utama (Row 1) --}}
<div class="g g4">
    <div class="card st">
        <div class="st-head">
            <span>Siswa aktif</span>
            <div class="st-icon st-icon-blue">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalSiswaAktif ?? 0) }}</b>
        <small class="st-foot text-blue">● Terdaftar aktif</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Guru</span>
            <div class="st-icon st-icon-emerald">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalGuru ?? 0) }}</b>
        <small class="st-foot text-emerald">● Pengajar &amp; Wali</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Alumni</span>
            <div class="st-icon st-icon-amber">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 011.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalAlumni ?? 0) }}</b>
        <small class="st-foot text-amber">● Lulusan sekolah</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Data di sampah</span>
            <div class="st-icon st-icon-rose">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalSampah ?? 0) }}</b>
        <small class="st-foot text-rose">● Dapat dipulihkan</small>
    </div>
</div>

{{-- 4 Stat Cards Rincian (Row 2) --}}
<div class="g g4" style="margin-top:14px">
    <div class="card st">
        <div class="st-head">
            <span>Laki-laki</span>
            <div class="st-icon st-icon-sky">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalLaki ?? 0) }}</b>
        <small class="st-foot text-muted">Siswa laki-laki aktif</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Perempuan</span>
            <div class="st-icon st-icon-pink">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalPerempuan ?? 0) }}</b>
        <small class="st-foot text-muted">Siswi perempuan aktif</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Pindah / keluar</span>
            <div class="st-icon st-icon-orange">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            </div>
        </div>
        <b>{{ number_format($totalMutasi ?? 0) }}</b>
        <small class="st-foot text-muted">Periode {{ $taAktif ?? '' }}</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Kelengkapan data</span>
            <div class="st-icon st-icon-indigo">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <b>{{ $persenLengkap ?? 100 }}%</b>
        <div class="st-progress">
            <div class="st-progress-fill" style="width:{{ $persenLengkap ?? 100 }}%"></div>
        </div>
    </div>
</div>

{{-- Row 3: Komposisi per Kelas & Perlu Dilengkapi --}}
<div class="g g2" style="margin-top:16px">
    <div class="card">
        <h3>Komposisi per kelas</h3>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>L</th>
                        <th>P</th>
                        <th>Total</th>
                        <th>Kelengkapan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($komposisiKelas ?? [] as $k)
                    <tr>
                        <td><strong>{{ $k['kelas'] }}</strong></td>
                        <td>{{ $k['l'] }}</td>
                        <td>{{ $k['p'] }}</td>
                        <td><b>{{ $k['total'] }}</b></td>
                        <td>
                            @if($k['total'] > 0)
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div class="cell-progress">
                                        <div class="cell-progress-bar {{ $k['persen'] >= 90 ? 'ok' : 'w' }}" style="width:{{ $k['persen'] }}%"></div>
                                    </div>
                                    <span class="bd {{ $k['persen'] >= 90 ? 'ok' : 'w' }}">{{ $k['persen'] }}%</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--mu);padding:24px">
                            Belum ada data kelas aktif pada tahun ajaran ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Perlu dilengkapi</h3>
        @if(!empty($perluDilengkapi))
            <div class="needs-completion-list">
                @foreach($perluDilengkapi as $item)
                <div class="needs-completion-item">
                    <div class="needs-completion-icon">⚠️</div>
                    <div class="needs-completion-info">
                        <b>{{ $item['nama'] }}</b> · <span class="badge-subtle">{{ $item['kelas'] }}</span>
                        <small class="needs-completion-missing">
                            Kurang: {{ implode(', ', array_slice($item['missing'], 0, 3)) }}
                            @if(count($item['missing']) > 3)
                                <span class="missing-more">+{{ count($item['missing']) - 3 }} lainnya</span>
                            @endif
                        </small>
                    </div>
                    <a href="{{ route('admin.data-siswa.edit', $item['id']) }}" class="btn a" style="padding:5px 12px;font-size:12px">Edit</a>
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
</div>

{{-- Row 4: Siswa per Angkatan & Alumni per Tahun Lulus + Log Aktivitas --}}
<div class="g g2" style="margin-top:16px">
    <div class="card">
        <h3>Siswa per angkatan</h3>
        <div class="angs">
            <div class="ang ang-x">
                <div class="ang-badge">Tingkat 10</div>
                <small>Kelas X</small>
                <b>{{ number_format($angkatanX ?? 0) }} <span>Siswa</span></b>
                <span class="ang-status">● Aktif</span>
            </div>
            <div class="ang ang-xi">
                <div class="ang-badge">Tingkat 11</div>
                <small>Kelas XI</small>
                <b>{{ number_format($angkatanXI ?? 0) }} <span>Siswa</span></b>
                <span class="ang-status">● Aktif</span>
            </div>
            <div class="ang ang-xii">
                <div class="ang-badge">Tingkat 12</div>
                <small>Kelas XII</small>
                <b>{{ number_format($angkatanXII ?? 0) }} <span>Siswa</span></b>
                <span class="ang-status">● Aktif</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Alumni per tahun lulus</h3>
        <div class="bar" style="flex-wrap:wrap;gap:8px;margin:10px 0 16px">
            @if(!empty($alumniPerTahun))
                @foreach($alumniPerTahun as $thn => $jml)
                    <span class="bd ok" style="padding:5px 12px;font-size:12px">{{ $thn }}: <b>{{ $jml }}</b> lulusan</span>
                @endforeach
            @else
                <span class="sub">Belum ada data alumni tercatat</span>
            @endif
        </div>

        <h3 style="margin-top:20px">Aktivitas terbaru</h3>
        @if(!empty($aktivitasTerbaru) && count($aktivitasTerbaru) > 0)
            <div class="activity-timeline">
                @foreach($aktivitasTerbaru as $log)
                <div class="activity-item">
                    <div class="activity-bullet"></div>
                    <div class="activity-content">
                        <b>{{ $log->keterangan ?? $log->aksi }}</b>
                        <span class="activity-time">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <span class="sub" style="display:block;padding:12px 0">Belum ada aktivitas tercatat.</span>
        @endif
    </div>
</div>
@endsection
