@extends('layouts.app')

@section('title', 'Alumni — JosBook Admin')

@push('styles')
<style>
    .alumni-modal{position:fixed;inset:0;z-index:1000;display:none;place-items:center;padding:20px;background:rgba(9,24,43,.62);backdrop-filter:blur(3px)}
    .alumni-modal.on{display:grid}
    .alumni-modal-box{display:flex;flex-direction:column;width:min(980px,100%);max-height:92vh;overflow:hidden;border:1px solid #dce5ef;border-radius:20px;background:#f5f8fc;box-shadow:0 24px 80px #06172f55}
    .alumni-modal-head{display:flex;align-items:center;gap:14px;padding:18px 22px;background:#fff;border-bottom:1px solid var(--ln)}
    .alumni-modal-head h3{margin:0;color:var(--p);font-size:17px}
    .alumni-modal-head p{margin:3px 0 0;color:var(--mu);font-size:12px}
    .alumni-modal-close{margin-left:auto;width:36px;height:36px;border:1px solid var(--ln);border-radius:50%;background:#fff;color:var(--p);font-size:20px;cursor:pointer}
    .alumni-modal-body{overflow:auto;padding:20px}
    .alumni-detail-section{margin-top:14px;padding:18px;border:1px solid var(--ln);border-radius:15px;background:#fff}
    .alumni-detail-section h4{margin:0 0 14px;color:var(--p);font-size:13px}
    .alumni-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 22px}
    .alumni-detail-item{min-width:0}
    .alumni-detail-item span{display:block;margin-bottom:4px;color:var(--mu);font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    .alumni-detail-item b{display:block;overflow-wrap:anywhere;color:#1b2430;font-size:12px}
    .alumni-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .alumni-form-grid label{display:block;margin:0 0 5px;color:var(--mu);font-size:11px;font-weight:700}
    .alumni-form-grid input,.alumni-form-grid select{width:100%}
    .alumni-form-grid input[type=checkbox]{width:auto;margin-right:6px}
    .alumni-form-flag{display:flex!important;align-items:center;gap:6px;margin-top:8px!important}
    .alumni-form-heading{grid-column:1/-1;margin:8px 0 0;padding-bottom:7px;border-bottom:1px solid var(--ln);color:var(--p);font-size:12px;font-weight:800}
    .alumni-form-summary{grid-column:1/-1}
    .alumni-form-summary small{display:block;margin-top:5px;color:var(--mu);font-size:10px}
    .alumni-name-link{color:var(--p);font:inherit;font-weight:700;text-align:left;text-decoration:none}
    .alumni-name-link:hover{text-decoration:underline}
    .alumni-pdf-frame{width:100%;height:min(76vh,920px);border:0;background:#e8edf4}
    .btn.alumni-tracking-action.pending{border:1px solid #f59e0b;background:#f59e0b;color:#fff}
    .btn.alumni-tracking-action.tracked{border:1px solid var(--ln);background:#fff;color:var(--p)}
    .student-profile{display:grid;gap:12px}
    .student-profile-identity{display:flex;align-items:center;gap:14px;padding:16px;border-radius:14px;background:linear-gradient(125deg,#12355b,#1d4f85);color:#fff}
    .student-profile-photo,.student-profile-avatar{display:grid;place-items:center;width:58px;height:58px;flex:none;border:2px solid #ffffff44;border-radius:16px;object-fit:cover}
    .student-profile-avatar{background:#ffffff20;font-size:23px;font-weight:800}
    .student-profile-identity h4{margin:0 0 5px;color:#fff;font-size:17px}
    .student-profile-identity p{margin:0;color:#d9e7f7;font-size:12px}
    .student-profile-section{padding:15px;border:1px solid var(--ln);border-radius:13px;background:#fff}
    .student-profile-section h4{margin:0 0 12px;color:var(--p);font-size:12px}
    .student-profile-history{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px}
    .student-profile-history-item{display:grid;gap:5px;padding:10px;border:1px solid #e2e8f0;border-radius:10px;color:#475569;font-size:11px}
    .student-profile-history-item b{color:#1b2430}
    .student-profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px}
    .student-profile-item{min-width:0}
    .student-profile-item span{display:block;margin-bottom:4px;color:var(--mu);font-size:9px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    .student-profile-item b{display:block;overflow-wrap:anywhere;color:#1b2430;font-size:11px;white-space:pre-wrap}
    .student-profile-item a{color:var(--p)}
    .student-profile-note{grid-column:1/-1}
    .student-profile-empty{margin:0;color:var(--mu);font-size:11px}
    .alumni-stats{margin-bottom:12px}
    .alumni-row{cursor:pointer}
    .alumni-row:hover{background:#f3f7fc}
    .alumni-side-backdrop{position:fixed;inset:0;z-index:1100;background:rgba(9,24,43,.42)}
    .alumni-side-panel{position:fixed;top:50%;left:50%;right:auto;z-index:1101;display:flex;flex-direction:column;width:min(760px,calc(100vw - 32px));height:min(88dvh,900px);max-height:calc(100dvh - 32px);transform:translate(-50%,-50%);border:1px solid #dce5ef;border-radius:20px;background:#f5f8fc;box-shadow:0 24px 80px #06172f55;animation:alumniSidePanelIn .3s cubic-bezier(.16,1,.3,1)!important}
    @keyframes alumniSidePanelIn{from{opacity:0;transform:translate(-50%,-50%) scale(.95) translateY(10px)}to{opacity:1;transform:translate(-50%,-50%) scale(1) translateY(0)}}
    .alumni-side-head{display:flex;align-items:flex-start;gap:12px;padding:18px;border-bottom:1px solid var(--ln);background:#fff}
    .alumni-side-head h3{margin:0;color:var(--p);font-size:17px}
    .alumni-side-head p{margin:4px 0 0;color:var(--mu);font-size:12px}
    .alumni-side-close{margin-left:auto}
    .alumni-side-tabs{display:flex;gap:8px;padding:12px 18px;border-bottom:1px solid var(--ln);background:#fff}
    .alumni-side-tabs a{padding:8px 12px;border:1px solid var(--ln);border-radius:8px;color:var(--p);font-size:12px;font-weight:700;text-decoration:none}
    .alumni-side-tabs a.on{background:var(--p);border-color:var(--p);color:#fff}
    .alumni-side-tabs button{padding:8px 12px;border:1px solid var(--ln);border-radius:8px;background:#fff;color:var(--p);font-size:12px;font-weight:700;cursor:pointer}
    .alumni-side-tabs button.on{background:var(--p);border-color:var(--p);color:#fff}
    #alumni-edit-tabs{flex:none;overflow-x:auto;white-space:nowrap}
    #alumni-edit-tabs button{flex:none}
    .alumni-side-body{flex:1;min-height:0;overflow-y:auto;padding:16px}
    .alumni-tracking-row{display:flex;align-items:flex-start;gap:10px;padding:12px 0;border-bottom:1px solid #e8edf3}
    .alumni-tracking-row form{margin-left:auto}
    .alumni-side-form{margin-top:14px;padding:14px;border:1px solid var(--ln);border-radius:12px;background:#fff}
    .alumni-side-form label{display:block;margin:8px 0 4px;color:var(--mu);font-size:11px;font-weight:700}
    .alumni-side-form input,.alumni-side-form select{width:100%}
    .alumni-side-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
    @media(max-width:600px){.alumni-modal{padding:8px}.alumni-modal-box{max-height:96vh;border-radius:14px}.alumni-modal-head{padding:14px}.alumni-modal-body{padding:12px}.alumni-side-panel{width:calc(100vw - 16px);height:96dvh;max-height:calc(100dvh - 16px);border-radius:14px}.alumni-detail-grid,.alumni-form-grid,.student-profile-grid{grid-template-columns:1fr}.student-profile-identity{align-items:flex-start;padding:13px}.student-profile-identity h4{font-size:15px}}
</style>
@endpush

@section('content')
<h2>Alumni</h2>
<p class="sub">Data lulus dan pindah/keluar, kontrol induk, serta pelacakan alumni · {{ $totalFiltered }} data · klik baris atau tombol Lihat/Edit untuk membuka panel alumni</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="note err" role="alert">{{ $errors->first() }}</div>
@endif

<div class="g g4 alumni-stats">
    <div class="card st">
        <div class="st-head">
            <span>Alumni lulus</span>
            <div class="st-icon st-icon-blue">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($trackingStats['total'] ?? 0) }}</b>
        <small class="st-foot text-blue">● Terdata lulus</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Sudah dilacak</span>
            <div class="st-icon st-icon-indigo">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($trackingStats['dilacak'] ?? 0) }}</b>
        <small class="st-foot text-indigo">● Status aktivitas terdata</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Kuliah</span>
            <div class="st-icon st-icon-purple">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
        </div>
        <b>{{ number_format($trackingStats['kuliah'] ?? 0) }}</b>
        <small class="st-foot text-purple">● Perguruan tinggi</small>
    </div>
    <div class="card st">
        <div class="st-head">
            <span>Bekerja</span>
            <div class="st-icon st-icon-emerald">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        <b>{{ number_format($trackingStats['bekerja'] ?? 0) }}</b>
        <small class="st-foot text-emerald">● Dunia usaha &amp; industri</small>
    </div>
</div>

<form method="GET" action="{{ route('admin.alumni') }}">
    <div class="bar" style="background:#fff;padding:14px 18px;border-radius:14px;border:1px solid var(--admin-border);box-shadow:0 2px 8px rgba(15,23,42,0.04);margin-bottom:16px">
        <select name="aj" onchange="this.form.submit()">
            @foreach($types as $value => $label)
            <option value="{{ $value }}" {{ $aj === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="ay" onchange="this.form.submit()">
            <option value="">Semua tahun</option>
            @foreach($ys as $y)
            <option value="{{ $y }}" {{ (string)$ay === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <select name="kelas" onchange="this.form.submit()">
            <option value="">Semua kelas</option>
            @foreach($kelasList as $kelasOption)
            <option value="{{ $kelasOption }}" {{ $kelas === $kelasOption ? 'selected' : '' }}>{{ $kelasOption }}</option>
            @endforeach
        </select>
        <input type="text" name="aq" placeholder="Cari nama / induk / NISN" value="{{ $aq }}" style="width:200px">
        <button type="submit" class="btn">🔍 Cari</button>
        <span class="sp"></span>
        <a href="{{ route('admin.alumni.export', ['aj' => $aj, 'ay' => $ay, 'kelas' => $kelas, 'aq' => $aq]) }}" class="btn o">📊 Ekspor Excel (Kontrol Induk + Biodata)</a>
    </div>
</form>

<div class="bar" style="flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <form method="POST" action="{{ route('admin.alumni.bulk-delete') }}" style="margin:0" onsubmit="return confirm('Semua alumni yang cocok dengan filter saat ini akan dipindahkan ke Tempat Sampah. Lanjutkan?')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="mode" value="all">
        <input type="hidden" name="aj" value="{{ $aj }}">
        <input type="hidden" name="ay" value="{{ $ay }}">
        <input type="hidden" name="kelas" value="{{ $kelas }}">
        <input type="hidden" name="aq" value="{{ $aq }}">
        <input type="hidden" name="konfirmasi" value="hapus">
        <button type="submit" class="btn x" {{ $totalFiltered === 0 ? 'disabled' : '' }}>🗑 Hapus semua hasil filter ({{ $totalFiltered }})</button>
    </form>
    <form id="alumni-delete-selected-form" method="POST" action="{{ route('admin.alumni.bulk-delete') }}" style="display:none" onsubmit="return confirm('Alumni yang dipilih akan dipindahkan ke Tempat Sampah. Lanjutkan?')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="mode" value="selected">
        <input type="hidden" name="aj" value="{{ $aj }}">
        <input type="hidden" name="ay" value="{{ $ay }}">
        <input type="hidden" name="kelas" value="{{ $kelas }}">
        <input type="hidden" name="aq" value="{{ $aq }}">
        <input type="hidden" name="konfirmasi" value="hapus">
    </form>
    <button type="submit" form="alumni-delete-selected-form" id="alumni-delete-selected-button" class="btn x" disabled>🗑 Hapus terpilih</button>
</div>

<div class="card wide">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="alumni-select-all" aria-label="Pilih semua alumni di halaman"></th>
                    <th>No</th>
                    <th>Induk</th>
                    <th>Nama Peserta Didik</th>
                    <th>NISN</th>
                    <th>Jenis Registrasi</th>
                    <th>Tanggal Masuk</th>
                    <th>Masuk Kelas</th>
                    <th>Sekolah Asal SMP</th>
                    <th>Keluar Karena</th>
                    <th>Tanggal Keluar</th>
                    <th>Keluar Kelas</th>
                    <th>Sekolah Tujuan</th>
                    <th>Nomor Peserta PSAJ</th>
                    <th>Nomor SKL</th>
                    <th>Nomor Ijazah Nasional</th>
                    <th>Tgl Pengesahan Ijazah</th>
                    <th>Nomor Transkrip Nilai</th>
                    <th>Melanjutkan Studi / Bekerja</th>
                    <th>Tahun Lulus</th>
                    <th>Status</th>
                    <th>Status Kontrol Induk</th>
                    <th>Status Pelacakan</th>
                    <th style="min-width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alumni as $idx => $s)
                @php
                    $isGraduated = $s->isGraduated();
                    $status = $s->status_alumni;
                    $statusClass = $status === 'Lulus' ? 'ok' : (in_array($status, ['Pindah keluar', 'Keluar', 'Pindah']) ? 'w' : '');
                    $thnLulusDisplay = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                    $panelUrl = route('admin.alumni', array_merge($panelQuery, ['siswa' => $s->id]));
                    $studentDetails = [
                        'id' => $s->id,
                        'nama_lengkap' => $s->nama_lengkap,
                        'nisn' => $s->nisn,
                        'edit_data_url' => route('admin.alumni.edit-data', $s->id),
                        'pdf_url' => route('admin.data-siswa.pdf', $s->id),
                    ];
                    $studentJson = json_encode($studentDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                @endphp
                <tr class="alumni-row" data-panel-url="{{ $panelUrl }}" tabindex="0" role="link" aria-label="Buka panel {{ $s->nama_lengkap }}">
                    <td><input type="checkbox" class="alumni-select-row" form="alumni-delete-selected-form" name="siswa_ids[]" value="{{ $s->id }}" aria-label="Pilih {{ $s->nama_lengkap }}" onclick="event.stopPropagation()"></td>
                    <td>{{ $alumni->firstItem() + $idx }}</td>
                    <td>{{ $s->nomor_induk ?: '-' }}</td>
                    <td>
                        <a class="alumni-name-link" href="{{ $panelUrl }}">{{ $s->nama_lengkap }}</a>
                    </td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->jenis_registrasi ?: '-' }}</td>
                    <td>{{ $s->tanggal_diterima ? \Carbon\Carbon::parse($s->tanggal_diterima)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $s->kelas_masuk_display ?: '-' }}</td>
                    <td>{{ $s->sekolah_asal ?: '-' }}</td>
                    <td>{{ $s->alasan_keluar ?: '-' }}</td>
                    <td>{{ $s->tanggal_keluar ? \Carbon\Carbon::parse($s->tanggal_keluar)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $s->kelas_keluar ?: '-' }}</td>
                    <td>{{ $s->sekolah_tujuan ?: '-' }}</td>
                    <td>{{ $s->nomor_peserta_psaj ?: '-' }}</td>
                    <td>{{ $s->nomor_skl ?: '-' }}</td>
                    <td>{{ $s->nomor_ijazah_sma ?: '-' }}</td>
                    <td>{{ $s->tanggal_ijazah_sma ? \Carbon\Carbon::parse($s->tanggal_ijazah_sma)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $s->nomor_transkrip ?: '-' }}</td>
                    <td>
                        @if($isGraduated && empty($s->kegiatan_setelah_lulus))
                            <span class="bd w">Belum diketahui</span>
                        @else
                            {{ $s->kegiatan_setelah_lulus ?: '-' }}
                        @endif
                    </td>
                    <td>{{ $thnLulusDisplay }}</td>
                    <td><span class="bd {{ $statusClass }}">{{ $status }}</span></td>
                    <td>
                        @if($s->status_kontrol_induk >= 100)
                            <span class="bd ok">Lengkap</span>
                        @else
                            <span class="bd w">{{ $s->status_kontrol_induk }}%</span>
                        @endif
                    </td>
                    <td><span class="bd {{ $s->pelacakan_count ? 'ok' : 'w' }}">{{ $s->status_pelacakan }}</span></td>
                    <td style="white-space:nowrap">
                        <a href="{{ $panelUrl }}" class="btn">Lihat</a>
                        <button id="alumni-edit-{{ $s->id }}" type="button" class="btn a" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniEdit(this)">✏ Edit</button>
                        <button type="button" class="btn o" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniPdf(this)">⬇ PDF</button>
                        @if($isGraduated)
                            <button type="button" class="btn alumni-tracking-action {{ $s->pelacakan_count ? 'tracked' : 'pending' }}" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniEdit(this, 'tracking')" title="{{ $s->pelacakan_count ? 'Ubah pelacakan terakhir' : 'Catat kelanjutan alumni' }}">{{ $s->pelacakan_count ? 'Sudah dilacak' : 'Lacak' }}</button>
                        @endif
                        <form method="POST" action="{{ route('admin.data-siswa.destroy', $s->id) }}" style="display:inline" onsubmit="return confirm('Pindahkan data ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="24" style="text-align:center;padding:24px;color:var(--mu)">
                        Tidak ada data siswa yang cocok dengan filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($alumni->hasPages())
    <div style="margin-top:14px;display:flex;justify-content:center">
        {{ $alumni->links() }}
    </div>
    @endif
</div>

@if($selectedAlumni)
    @php
        $panelClosedUrl = route('admin.alumni', array_diff_key($panelQuery, ['siswa' => true]));
        $controlPanelUrl = route('admin.alumni', array_merge($panelQuery, ['tab' => 'kontrol-induk']));
        $trackingPanelUrl = route('admin.alumni', array_merge($panelQuery, ['tab' => 'pelacakan']));
        $contactAddress = collect([
            $selectedAlumni->alamat_jalan,
            $selectedAlumni->alamat_rt_rw,
            $selectedAlumni->alamat_desa,
            $selectedAlumni->alamat_kecamatan,
            $selectedAlumni->alamat_kabupaten,
        ])->filter()->implode(', ');
    @endphp
    <a class="alumni-side-backdrop" href="{{ $panelClosedUrl }}" aria-label="Tutup panel Alumni" onclick="return closeAlumniSidePanel(event)"></a>
    <aside id="alumni-side-panel" class="alumni-side-panel" role="dialog" aria-modal="true" aria-labelledby="alumni-side-title">
        <div class="alumni-side-head">
            <div>
                <h3 id="alumni-side-title">{{ $selectedAlumni->nama_lengkap }}</h3>
                <p>NISN {{ $selectedAlumni->nisn ?: '-' }} · Induk {{ $selectedAlumni->nomor_induk ?: '-' }}</p>
            </div>
            <a class="btn o alumni-side-close" href="{{ $panelClosedUrl }}" aria-label="Tutup panel" onclick="return closeAlumniSidePanel(event)">&times;</a>
        </div>
        <nav class="alumni-side-tabs" aria-label="Detail alumni">
            <a href="{{ $controlPanelUrl }}" class="{{ $panelTab === 'kontrol-induk' ? 'on' : '' }}">Kontrol Induk</a>
            <a href="{{ $trackingPanelUrl }}" class="{{ $panelTab === 'pelacakan' ? 'on' : '' }}">Pelacakan</a>
        </nav>
        <div class="alumni-side-body">
            @if($panelTab === 'pelacakan')
                <section class="alumni-detail-section">
                    <h4>Kontak alumni</h4>
                    <div class="alumni-detail-grid">
                        <div class="alumni-detail-item"><span>Nomor HP</span><b>{{ $selectedAlumni->nomor_hp ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Alamat</span><b>{{ $contactAddress ?: '-' }}</b></div>
                    </div>
                    <p style="margin:12px 0 0"><b>Kegiatan Setelah Lulus:</b> {{ $selectedAlumni->kegiatan_setelah_lulus ?: 'Belum diketahui' }}</p>
                </section>

                <section class="alumni-detail-section">
                    <h4>Riwayat Pelacakan</h4>
                    @forelse($selectedAlumni->pelacakan->sortByDesc('id') as $tracking)
                        <div class="alumni-tracking-row">
                            <div>
                                <b>{{ $tracking->jenis }} · {{ $tracking->nama_tempat ?: '-' }}</b>
                                <div style="color:var(--mu);font-size:11px">{{ $tracking->tahun ?: '-' }} · {{ $tracking->keterangan ?: 'Tanpa catatan' }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="student-profile-empty">Belum ada riwayat pelacakan.</p>
                    @endforelse
                </section>

            @else
                <section class="alumni-detail-section">
                    <h4>Kontak dan status</h4>
                    <div class="alumni-detail-grid">
                        <div class="alumni-detail-item"><span>Nomor HP</span><b>{{ $selectedAlumni->nomor_hp ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Alamat</span><b>{{ $contactAddress ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Status</span><b>{{ $selectedAlumni->status_alumni }}</b></div>
                        <div class="alumni-detail-item"><span>Kelengkapan Kontrol Induk</span><b>{{ $selectedAlumni->status_kontrol_induk }}%</b></div>
                    </div>
                </section>
                <section class="alumni-detail-section">
                    <h4>Data Kontrol Induk</h4>
                    <div class="alumni-detail-grid">
                        <div class="alumni-detail-item"><span>Jenis registrasi</span><b>{{ $selectedAlumni->jenis_registrasi ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Tanggal diterima</span><b>{{ $selectedAlumni->tanggal_diterima ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Kelas diterima</span><b>{{ $selectedAlumni->kelas_masuk_display ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Sekolah asal</span><b>{{ $selectedAlumni->sekolah_asal ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Alasan keluar</span><b>{{ $selectedAlumni->alasan_keluar ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Tanggal keluar</span><b>{{ $selectedAlumni->tanggal_keluar ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Kelas keluar</span><b>{{ $selectedAlumni->kelas_keluar ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Sekolah tujuan</span><b>{{ $selectedAlumni->sekolah_tujuan ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Tahun lulus</span><b>{{ $selectedAlumni->tahun_lulus ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Nomor peserta PSAJ</span><b>{{ $selectedAlumni->nomor_peserta_psaj ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Nomor SKL</span><b>{{ $selectedAlumni->nomor_skl ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Nomor ijazah</span><b>{{ $selectedAlumni->nomor_ijazah_sma ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Tanggal ijazah</span><b>{{ $selectedAlumni->tanggal_ijazah_sma ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Nomor transkrip</span><b>{{ $selectedAlumni->nomor_transkrip ?: '-' }}</b></div>
                        <div class="alumni-detail-item"><span>Kegiatan lanjutan</span><b>{{ $selectedAlumni->kegiatan_setelah_lulus ?: 'Belum diketahui' }}</b></div>
                    </div>
                    <div class="alumni-side-actions">
                        <button type="button" class="btn a" onclick="document.getElementById('alumni-edit-{{ $selectedAlumni->id }}').click()">✏ Edit Kontrol Induk</button>
                        <a class="btn o" href="{{ route('admin.data-siswa.pdf', $selectedAlumni->id) }}" target="_blank" rel="noopener">Cetak PDF</a>
                    </div>
                </section>
                <section class="alumni-detail-section">
                    <h4>Biodata Lengkap dan Riwayat Kelas</h4>
                    @include('admin.siswa-detail-content', [
                        'siswa' => $selectedAlumni,
                        'includeGraduationDetails' => false,
                        'canManageAcademicRecords' => false,
                    ])
                </section>
                @if($selectedAlumni->isGraduated())
                    @if($canCancelGraduation)
                        <form class="alumni-side-form" method="POST" action="{{ route('admin.alumni.cancel-graduation', $selectedAlumni->id) }}" onsubmit="return confirm('Batalkan kelulusan dan kembalikan siswa menjadi aktif?')">
                            @csrf
                            <input type="hidden" name="aj" value="{{ $aj }}">
                            <input type="hidden" name="ay" value="{{ $ay }}">
                            <input type="hidden" name="kelas" value="{{ $kelas }}">
                            <input type="hidden" name="aq" value="{{ $aq }}">
                            <input type="hidden" name="page" value="{{ $alumni->currentPage() }}">
                            <button type="submit" class="btn o">Batalkan kelulusan</button>
                        </form>
                    @else
                        <button type="button" class="btn o" disabled>Batalkan kelulusan</button>
                        <p class="sub">Tidak dapat dibatalkan karena belum ada riwayat pada tahun ajaran aktif.</p>
                    @endif
                @endif
            @endif
        </div>
    </aside>
@endif

<div id="alumni-edit-modal" class="alumni-modal" role="dialog" aria-modal="true" aria-labelledby="alumni-edit-title" onclick="closeAlumniModal(event, this)">
    <form id="alumni-edit-form" class="alumni-modal-box" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="alumni_edit" value="1">
        <input type="hidden" name="alumni_edit_id" value="">
        <input type="hidden" name="return_aj" value="{{ $aj }}">
        <input type="hidden" name="return_ay" value="{{ $ay }}">
        <input type="hidden" name="return_kelas" value="{{ $kelas }}">
        <input type="hidden" name="return_aq" value="{{ $aq }}">
        <input type="hidden" name="return_tab" value="{{ $panelTab }}">
        <input type="hidden" name="return_page" value="{{ $alumni->currentPage() }}">
        <div class="alumni-modal-head">
            <div>
                <h3 id="alumni-edit-title">Edit Kontrol Induk</h3>
                <p data-edit-name>Perbarui data alumni</p>
            </div>
            <button type="button" class="alumni-modal-close" aria-label="Tutup" onclick="closeAlumniModal(null, document.getElementById('alumni-edit-modal'))">&times;</button>
        </div>
        @if($errors->any())
            <div class="note err" role="alert" style="margin:12px 20px 0">{{ $errors->first() }}</div>
        @endif
        <div id="alumni-edit-loading" class="note" role="status" hidden style="margin:12px 20px 0">Memuat biodata lengkap...</div>
        <div id="alumni-edit-load-error" class="note err" role="alert" hidden style="margin:12px 20px 0"></div>
        @include('admin._alumni-edit-fields')
        <div class="alumni-modal-foot">
            <button type="button" class="btn o" onclick="closeAlumniModal(null, document.getElementById('alumni-edit-modal'))">Batal</button>
            <button type="submit" class="btn a">Simpan perubahan</button>
        </div>
    </form>
</div>

<form id="alumni-tracking-delete-form" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<div id="alumni-pdf-modal" class="alumni-modal" role="dialog" aria-modal="true" aria-labelledby="alumni-pdf-title" onclick="closeAlumniModal(event, this)">
    <div class="alumni-modal-box">
        <div class="alumni-modal-head">
            <div>
                <h3 id="alumni-pdf-title">Pratinjau dokumen cetak</h3>
                <p data-pdf-name></p>
            </div>
            <button type="button" class="alumni-modal-close" aria-label="Tutup" onclick="closeAlumniModal(null, document.getElementById('alumni-pdf-modal'))">&times;</button>
        </div>
        <iframe id="alumni-pdf-frame" class="alumni-pdf-frame" title="Pratinjau PDF alumni" loading="lazy"></iframe>
    </div>
</div>

@push('scripts')
<script>
let alumniPanelRequestId = 0;

const initialAlumniSideBackdrop = document.querySelector('.alumni-side-backdrop');
const initialAlumniSidePanel = document.getElementById('alumni-side-panel');
if (initialAlumniSideBackdrop && initialAlumniSidePanel) {
    document.body.append(initialAlumniSideBackdrop, initialAlumniSidePanel);
}

function alumniData(button) {
    try {
        return JSON.parse(button.dataset.alumni);
    } catch (error) {
        console.error('Data alumni tidak dapat dibaca.', error);
        return null;
    }
}

function showAlumniModal(modal) {
    modal.classList.add('on');
    document.body.style.overflow = 'hidden';
}

async function openAlumniEdit(button, initialTab = 'control', oldValues = null) {
    const rowData = alumniData(button);
    if (!rowData) return;

    alumniPanelRequestId += 1;
    const panel = document.getElementById('alumni-side-panel');
    const backdrop = document.querySelector('.alumni-side-backdrop');
    if (panel && backdrop) {
        const destination = new URL(backdrop.href, window.location.href);
        panel.remove();
        backdrop.remove();
        window.history.replaceState(
            window.history.state,
            '',
            destination.pathname + destination.search + destination.hash
        );
    }

    const modal = document.getElementById('alumni-edit-modal');
    const form = document.getElementById('alumni-edit-form');
    const loading = document.getElementById('alumni-edit-loading');
    const loadError = document.getElementById('alumni-edit-load-error');
    const submitButton = form.querySelector('[type="submit"]');
    const requestId = Number(form.dataset.requestId || 0) + 1;
    let dataLoaded = false;
    form.dataset.requestId = String(requestId);
    form.action = rowData.edit_data_url;
    form.querySelector('[name="alumni_edit_id"]').value = rowData.id;
    modal.querySelector('[data-edit-name]').textContent = `${rowData.nama_lengkap} · NISN ${rowData.nisn || '-'}`;
    loading.hidden = false;
    loadError.hidden = true;
    submitButton.disabled = true;
    showAlumniModal(modal);

    try {
        const response = await fetch(rowData.edit_data_url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error(`Permintaan data alumni gagal (${response.status}).`);
        }

        const data = await response.json();
        if (form.dataset.requestId !== String(requestId)) return;
        form.action = data.edit_url;
        modal.querySelectorAll('[data-edit-field]').forEach((input) => {
            input.value = data[input.dataset.editField] ?? '';
        });
        form.querySelectorAll('input[name],select[name],textarea[name]').forEach((input) => {
            if (input.type === 'file' || input.type === 'hidden') return;
            const value = data[input.name];
            if (input.type === 'checkbox') {
                input.checked = value === true || value === 1 || value === '1' || value === 'on';
            } else {
                input.value = value ?? '';
            }
        });
        if (oldValues) {
            form.querySelectorAll('input[name],select[name],textarea[name]').forEach((input) => {
                if (input.type === 'file' || input.type === 'hidden') return;
                if (input.type === 'checkbox') {
                    input.checked = oldValues[input.name] === true || oldValues[input.name] === 1 || oldValues[input.name] === '1' || oldValues[input.name] === 'on';
                } else if (Object.prototype.hasOwnProperty.call(oldValues, input.name)) {
                    input.value = oldValues[input.name] ?? '';
                }
            });
        }
        renderAlumniPhoto(data.foto_url);
        renderAlumniAttachments(data.attachments || []);
        renderAlumniTrackingHistory(data.tracking_history || []);
        switchAlumniEditTab(initialTab);
        loading.hidden = true;
        dataLoaded = true;
    } catch (error) {
        if (form.dataset.requestId !== String(requestId)) return;
        console.error('Gagal memuat data alumni untuk diedit.', error);
        loading.hidden = true;
        loadError.textContent = 'Data lengkap alumni gagal dimuat. Muat ulang halaman atau coba lagi.';
        loadError.hidden = false;
    } finally {
        if (form.dataset.requestId === String(requestId)) {
            submitButton.disabled = !dataLoaded;
        }
    }
}

function renderAlumniPhoto(url) {
    const preview = document.getElementById('alumni-edit-photo-preview');
    const filePanel = document.getElementById('alumni-edit-photo-file');
    preview.hidden = !url;
    preview.style.display = url ? 'block' : 'none';
    preview.src = url || '';
    filePanel.replaceChildren();

    if (url) {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Lihat pas foto yang tersimpan';
        filePanel.append(link);
    } else {
        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'student-profile-empty';
        emptyMessage.textContent = 'Belum ada pas foto.';
        filePanel.append(emptyMessage);
    }
}

function renderAlumniAttachments(attachments) {
    document.querySelectorAll('[data-existing-attachment]').forEach((container) => {
        const attachment = attachments.find((item) => item.jenis === container.dataset.existingAttachment);
        container.replaceChildren();
        const message = document.createElement('p');
        message.className = 'student-profile-empty';

        if (attachment) {
            const link = document.createElement('a');
            link.href = attachment.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = `Tersimpan: ${attachment.nama_file}`;
            message.append(link);
        } else {
            message.textContent = 'Belum ada dokumen yang diunggah.';
        }
        container.append(message);
    });
}

function renderAlumniTrackingHistory(history) {
    const container = document.getElementById('alumni-edit-tracking-history');
    container.replaceChildren();

    if (history.length === 0) {
        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'student-profile-empty';
        emptyMessage.textContent = 'Belum ada riwayat pelacakan.';
        container.append(emptyMessage);
        return;
    }

    history.forEach((tracking) => {
        const row = document.createElement('div');
        row.className = 'alumni-tracking-row';
        const description = document.createElement('div');
        description.textContent = `${tracking.jenis} · ${tracking.nama_tempat || '-'} (${tracking.tahun || '-'})${tracking.keterangan ? ` · ${tracking.keterangan}` : ''}`;
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn x';
        deleteButton.dataset.trackingDeleteUrl = tracking.delete_url;
        deleteButton.textContent = 'Hapus';
        row.append(description, deleteButton);
        container.append(row);
    });
}

function switchAlumniEditTab(tab) {
    document.querySelectorAll('[data-edit-tab]').forEach((button) => {
        const isActive = button.dataset.editTab === tab;
        button.classList.toggle('on', isActive);
        button.setAttribute('aria-selected', String(isActive));
    });
    document.querySelectorAll('[data-edit-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.editPanel !== tab;
    });
    const modalBody = document.querySelector('#alumni-edit-modal .alumni-modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
}

function openAlumniPdf(button) {
    const data = alumniData(button);
    if (!data) return;
    const modal = document.getElementById('alumni-pdf-modal');
    modal.querySelector('[data-pdf-name]').textContent = data.nama_lengkap;
    document.getElementById('alumni-pdf-frame').src = data.pdf_url;
    showAlumniModal(modal);
}

function closeAlumniModal(event, modal) {
    if (!modal || (event && event.target !== modal)) return;
    modal.classList.remove('on');
    if (modal.id === 'alumni-pdf-modal') {
        document.getElementById('alumni-pdf-frame').src = 'about:blank';
    }
    if (!document.querySelector('.alumni-modal.on')) document.body.style.overflow = '';
}

function closeAlumniSidePanel(event) {
    const panel = document.getElementById('alumni-side-panel');
    const backdrop = document.querySelector('.alumni-side-backdrop');
    if (!panel || !backdrop) return true;

    event.preventDefault();
    alumniPanelRequestId += 1;
    const destination = new URL(event.currentTarget.href, window.location.href);
    panel.remove();
    backdrop.remove();
    window.history.replaceState(
        window.history.state,
        '',
        destination.pathname + destination.search + destination.hash
    );

    return false;
}

async function loadAlumniSidePanel(url, updateHistory = true) {
    const requestId = ++alumniPanelRequestId;

    try {
        const response = await fetch(url, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error(`Permintaan panel alumni gagal (${response.status}).`);
        }

        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const backdrop = page.querySelector('.alumni-side-backdrop');
        const panel = page.getElementById('alumni-side-panel');
        if (!backdrop || !panel) {
            throw new Error('Panel alumni tidak ditemukan pada respons.');
        }
        if (requestId !== alumniPanelRequestId) return;

        document.querySelector('.alumni-side-backdrop')?.remove();
        document.getElementById('alumni-side-panel')?.remove();
        document.body.append(backdrop, panel);

        if (updateHistory) {
            const currentState = window.history.state;
            const nextState = currentState && typeof currentState === 'object'
                ? { ...currentState, alumniPanel: true }
                : { alumniPanel: true };
            window.history.pushState(nextState, '', url);
        }
    } catch (error) {
        if (requestId !== alumniPanelRequestId) return;
        console.error('Gagal memuat panel alumni.', error);
        window.location.assign(url);
    }
}

document.querySelectorAll('.alumni-row').forEach((row) => {
    const openPanel = () => loadAlumniSidePanel(row.dataset.panelUrl);
    row.addEventListener('click', (event) => {
        if (event.target.closest('a,button,form,input,select')) return;
        openPanel();
    });
    row.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openPanel();
        }
    });
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link) return;

    if (link.closest('.alumni-side-tabs')) {
        event.preventDefault();
        loadAlumniSidePanel(link.href);
        return;
    }

    const row = link.closest('.alumni-row');
    if (row && link.href === new URL(row.dataset.panelUrl, window.location.href).href) {
        event.preventDefault();
        loadAlumniSidePanel(row.dataset.panelUrl);
    }
});

window.addEventListener('popstate', () => {
    const url = new URL(window.location.href);
    if (url.searchParams.has('siswa')) {
        loadAlumniSidePanel(url.href, false);
        return;
    }

    alumniPanelRequestId += 1;
    document.querySelector('.alumni-side-backdrop')?.remove();
    document.getElementById('alumni-side-panel')?.remove();
});

document.querySelectorAll('[data-edit-tab]').forEach((button) => {
    button.addEventListener('click', () => switchAlumniEditTab(button.dataset.editTab));
});

const alumniSelectAll = document.getElementById('alumni-select-all');
const alumniSelectRows = document.querySelectorAll('.alumni-select-row');
const alumniDeleteSelectedButton = document.getElementById('alumni-delete-selected-button');

function updateAlumniDeleteSelection() {
    const selectedCount = document.querySelectorAll('.alumni-select-row:checked').length;
    alumniDeleteSelectedButton.disabled = selectedCount === 0;
    alumniSelectAll.checked = alumniSelectRows.length > 0 && selectedCount === alumniSelectRows.length;
    alumniSelectAll.indeterminate = selectedCount > 0 && selectedCount < alumniSelectRows.length;
}

alumniSelectAll.addEventListener('change', () => {
    alumniSelectRows.forEach((checkbox) => {
        checkbox.checked = alumniSelectAll.checked;
    });
    updateAlumniDeleteSelection();
});

alumniSelectRows.forEach((checkbox) => {
    checkbox.addEventListener('change', updateAlumniDeleteSelection);
});

document.getElementById('alumni-edit-tracking-history').addEventListener('click', (event) => {
    const button = event.target.closest('[data-tracking-delete-url]');
    if (!button || !window.confirm('Hapus riwayat pelacakan ini?')) return;

    const form = document.getElementById('alumni-tracking-delete-form');
    form.action = button.dataset.trackingDeleteUrl;
    form.submit();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.alumni-modal.on').forEach((modal) => closeAlumniModal(null, modal));
    }
});

const oldAlumniEditValues = @json(old());
if (oldAlumniEditValues.alumni_edit === '1' && oldAlumniEditValues.alumni_edit_id) {
    const editButton = document.getElementById(`alumni-edit-${oldAlumniEditValues.alumni_edit_id}`);
    if (editButton) {
        openAlumniEdit(editButton, 'control', oldAlumniEditValues);
    }
}
</script>
@endpush
@endsection
