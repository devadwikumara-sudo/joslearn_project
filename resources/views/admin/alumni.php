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
    .alumni-identity{display:flex;align-items:center;gap:16px;padding:20px;border-radius:16px;background:linear-gradient(125deg,#12355b,#1d4f85);color:#fff}
    .alumni-avatar{display:grid;place-items:center;width:58px;height:58px;flex:none;border-radius:16px;background:#ffffff20;color:#fff;font-size:23px;font-weight:800}
    .alumni-identity h4{margin:0 0 5px;font-size:20px}
    .alumni-identity p{margin:0;color:#d9e7f7;font-size:12px}
    .alumni-identity .bd{margin-left:auto;background:#fff;color:var(--p)}
    .alumni-detail-section{margin-top:14px;padding:18px;border:1px solid var(--ln);border-radius:15px;background:#fff}
    .alumni-detail-section h4{margin:0 0 14px;color:var(--p);font-size:13px}
    .alumni-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 22px}
    .alumni-detail-item{min-width:0}
    .alumni-detail-item span{display:block;margin-bottom:4px;color:var(--mu);font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    .alumni-detail-item b{display:block;overflow-wrap:anywhere;color:#1b2430;font-size:12px}
    .alumni-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .alumni-form-grid input{width:100%}
    .alumni-name-link{padding:0;border:0;background:none;color:var(--p);font:inherit;font-weight:700;text-align:left;cursor:pointer}
    .alumni-modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid var(--ln);background:#fff}
    .alumni-pdf-frame{width:100%;height:min(76vh,920px);border:0;background:#e8edf4}
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
    .alumni-side-panel{position:fixed;top:0;right:0;z-index:1101;display:flex;flex-direction:column;width:min(560px,100%);height:100dvh;background:#f5f8fc;box-shadow:-18px 0 55px #06172f33}
    .alumni-side-head{display:flex;align-items:flex-start;gap:12px;padding:18px;border-bottom:1px solid var(--ln);background:#fff}
    .alumni-side-head h3{margin:0;color:var(--p);font-size:17px}
    .alumni-side-head p{margin:4px 0 0;color:var(--mu);font-size:12px}
    .alumni-side-close{margin-left:auto}
    .alumni-side-tabs{display:flex;gap:8px;padding:12px 18px;border-bottom:1px solid var(--ln);background:#fff}
    .alumni-side-tabs a{padding:8px 12px;border:1px solid var(--ln);border-radius:8px;color:var(--p);font-size:12px;font-weight:700;text-decoration:none}
    .alumni-side-tabs a.on{background:var(--p);border-color:var(--p);color:#fff}
    .alumni-side-body{overflow-y:auto;padding:16px}
    .alumni-tracking-row{display:flex;align-items:flex-start;gap:10px;padding:12px 0;border-bottom:1px solid #e8edf3}
    .alumni-tracking-row form{margin-left:auto}
    .alumni-side-form{margin-top:14px;padding:14px;border:1px solid var(--ln);border-radius:12px;background:#fff}
    .alumni-side-form label{display:block;margin:8px 0 4px;color:var(--mu);font-size:11px;font-weight:700}
    .alumni-side-form input,.alumni-side-form select{width:100%}
    .alumni-side-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
    @media(max-width:600px){.alumni-modal{padding:8px}.alumni-modal-box{max-height:96vh;border-radius:14px}.alumni-modal-head{padding:14px}.alumni-modal-body{padding:12px}.alumni-detail-grid,.alumni-form-grid,.student-profile-grid{grid-template-columns:1fr}.alumni-identity{align-items:flex-start;padding:15px}.alumni-identity h4{font-size:16px}.alumni-identity .bd{margin-left:auto}.student-profile-identity{align-items:flex-start;padding:13px}.student-profile-identity h4{font-size:15px}}
</style>
@endpush

@section('content')
<h2>Alumni</h2>
<p class="sub">Data lulus dan pindah/keluar, kontrol induk, serta pelacakan alumni · {{ $totalFiltered }} data · klik baris untuk membuka panel samping</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="note err" role="alert">{{ $errors->first() }}</div>
@endif

<div class="g g4 alumni-stats">
    <div class="card st"><span>Alumni lulus</span><b>{{ $trackingStats['total'] }}</b></div>
    <div class="card st"><span>Sudah dilacak</span><b>{{ $trackingStats['dilacak'] }}</b></div>
    <div class="card st"><span>Kuliah</span><b>{{ $trackingStats['kuliah'] }}</b></div>
    <div class="card st"><span>Bekerja</span><b>{{ $trackingStats['bekerja'] }}</b></div>
</div>

<form method="GET" action="{{ route('admin.alumni') }}">
    <div class="bar">
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
        <input type="text" name="aq" placeholder="Cari nama / induk / NISN" value="{{ $aq }}">
        <button type="submit" class="btn" style="display:none"></button>
        <span class="sp"></span>
        <a href="{{ route('admin.alumni.export', ['aj' => $aj, 'ay' => $ay, 'aq' => $aq]) }}" class="btn">⬇ Ekspor Excel (Kontrol Induk + biodata)</a>
    </div>
</form>

<div class="card wide">
    <div class="tw">
        <table>
            <thead>
                <tr>
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
                    $latestRiwayat = $s->riwayatKelas->first();
                    $isGraduated = $s->isGraduated();
                    $status = $s->status_alumni;
                    $statusClass = $status === 'Lulus' ? 'ok' : (in_array($status, ['Pindah keluar', 'Keluar', 'Pindah']) ? 'w' : '');
                    $thnLulusDisplay = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                    $masukKelas = trim(($s->kelas_diterima ?: '') . ($s->rombel_diterima ? '-' . $s->rombel_diterima : ''));
                    $panelUrl = route('admin.alumni', array_merge($panelQuery, ['siswa' => $s->id]));
                    $studentDetails = [
                        'id' => $s->id,
                        'nama_lengkap' => $s->nama_lengkap,
                        'nisn' => $s->nisn,
                        'nomor_induk' => $s->nomor_induk,
                        'status' => $status,
                        'kelas_terakhir' => $s->kelas_keluar ?: ($latestRiwayat?->kelas?->nama ?? '-'),
                        'jenis_registrasi' => $s->jenis_registrasi,
                        'tanggal_diterima' => $s->tanggal_diterima,
                        'kelas_diterima' => $s->kelas_diterima,
                        'rombel_diterima' => $s->rombel_diterima,
                        'sekolah_asal' => $s->sekolah_asal,
                        'alasan_keluar' => $s->alasan_keluar,
                        'tanggal_keluar' => $s->tanggal_keluar,
                        'kelas_keluar' => $s->kelas_keluar,
                        'sekolah_tujuan' => $s->sekolah_tujuan,
                        'nomor_peserta_psaj' => $s->nomor_peserta_psaj,
                        'nomor_skl' => $s->nomor_skl,
                        'nomor_ijazah_sma' => $s->nomor_ijazah_sma,
                        'tanggal_ijazah_sma' => $s->tanggal_ijazah_sma,
                        'nomor_transkrip' => $s->nomor_transkrip,
                        'tahun_lulus' => $s->tahun_lulus,
                        'kegiatan_setelah_lulus' => $s->kegiatan_setelah_lulus,
                        'nomor_hp' => $s->nomor_hp,
                        'alamat_jalan' => $s->alamat_jalan,
                        'status_kontrol_induk' => $s->status_kontrol_induk,
                        'status_pelacakan' => $s->status_pelacakan,
                        'edit_url' => route('admin.alumni.update', $s->id),
                        'full_edit_url' => route('admin.form-pendataan.edit', $s->id),
                        'pdf_url' => route('admin.data-siswa.pdf', $s->id),
                        'tracking_url' => route('admin.alumni', array_merge($panelQuery, [
                            'tab' => 'pelacakan',
                            'siswa' => $s->id,
                        ])),
                    ];
                    $studentJson = json_encode($studentDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                @endphp
                <tr class="alumni-row" data-panel-url="{{ $panelUrl }}" tabindex="0" role="link" aria-label="Buka panel {{ $s->nama_lengkap }}">
                    <td>{{ $alumni->firstItem() + $idx }}</td>
                    <td>{{ $s->nomor_induk ?: '-' }}</td>
                    <td>
                        <button type="button" class="alumni-name-link" data-alumni="{{ $studentJson }}" onclick="openAlumniDetail(this)">
                            {{ $s->nama_lengkap }}
                        </button>
                    </td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->jenis_registrasi ?: '-' }}</td>
                    <td>{{ $s->tanggal_diterima ? \Carbon\Carbon::parse($s->tanggal_diterima)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $masukKelas ?: '-' }}</td>
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
                        <button type="button" class="btn" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniDetail(this)">Lihat</button>
                        <button type="button" class="btn a" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniEdit(this)">✏ Edit</button>
                        <button type="button" class="btn o" data-alumni="{{ $studentJson }}" onclick="event.stopPropagation();openAlumniPdf(this)">⬇ PDF</button>
                        @if($isGraduated && empty($s->kegiatan_setelah_lulus))
                            <a href="{{ $studentDetails['tracking_url'] }}" class="btn o" title="Catat kelanjutan alumni">Lacak</a>
                        @endif
                        <form method="POST" action="{{ route('admin.data-siswa.destroy', $s->id) }}" style="display:inline" onsubmit="return confirm('Pindahkan data ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x">🗑</button>
                        </form>
                        <template class="alumni-profile-template">
                            @include('admin.siswa-detail-content', [
                                'siswa' => $s,
                                'includeGraduationDetails' => false,
                                'canManageAcademicRecords' => false,
                            ])
                        </template>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="23" style="text-align:center;padding:24px;color:var(--mu)">
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
    <a class="alumni-side-backdrop" href="{{ $panelClosedUrl }}" aria-label="Tutup panel Alumni"></a>
    <aside id="alumni-side-panel" class="alumni-side-panel" role="dialog" aria-modal="true" aria-labelledby="alumni-side-title">
        <div class="alumni-side-head">
            <div>
                <h3 id="alumni-side-title">{{ $selectedAlumni->nama_lengkap }}</h3>
                <p>NISN {{ $selectedAlumni->nisn ?: '-' }} · Induk {{ $selectedAlumni->nomor_induk ?: '-' }}</p>
            </div>
            <a class="btn o alumni-side-close" href="{{ $panelClosedUrl }}" aria-label="Tutup panel">&times;</a>
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
                            <form method="POST" action="{{ route('admin.pelacakan-alumni.destroy', $tracking->id) }}" onsubmit="return confirm('Hapus riwayat pelacakan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn x" aria-label="Hapus riwayat pelacakan">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <p class="student-profile-empty">Belum ada riwayat pelacakan.</p>
                    @endforelse
                </section>

                <form class="alumni-side-form" method="POST" action="{{ route('admin.pelacakan-alumni.store') }}">
                    @csrf
                    <input type="hidden" name="siswa_id" value="{{ $selectedAlumni->id }}">
                    <h4 style="margin:0;color:var(--p)">Catat kegiatan lanjutan</h4>
                    <label for="tracking-kind">Kegiatan</label>
                    <select id="tracking-kind" name="jenis" required>
                        <option value="Kuliah">Kuliah</option>
                        <option value="Bekerja">Bekerja</option>
                        <option value="Wirausaha">Wirausaha</option>
                        <option value="Mencari kerja">Mencari kerja</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <label for="tracking-place">Kampus / instansi / perusahaan</label>
                    <input id="tracking-place" name="nama_tempat" maxlength="150" required>
                    <label for="tracking-year">Tahun</label>
                    <input id="tracking-year" type="number" name="tahun" value="{{ date('Y') }}" min="1900" max="2099" required>
                    <label for="tracking-note">Catatan</label>
                    <input id="tracking-note" name="keterangan" maxlength="255">
                    <div class="alumni-side-actions">
                        <button type="submit" class="btn a">Simpan pelacakan</button>
                    </div>
                </form>
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
                        <div class="alumni-detail-item"><span>Kelas diterima</span><b>{{ trim(($selectedAlumni->kelas_diterima ?: '').($selectedAlumni->rombel_diterima ? '-'.$selectedAlumni->rombel_diterima : '')) ?: '-' }}</b></div>
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
                        <button type="button" class="btn a" data-alumni="{{ json_encode([
                            'id' => $selectedAlumni->id,
                            'nama_lengkap' => $selectedAlumni->nama_lengkap,
                            'nisn' => $selectedAlumni->nisn,
                            'jenis_registrasi' => $selectedAlumni->jenis_registrasi,
                            'tanggal_diterima' => $selectedAlumni->tanggal_diterima,
                            'kelas_diterima' => $selectedAlumni->kelas_diterima,
                            'rombel_diterima' => $selectedAlumni->rombel_diterima,
                            'sekolah_asal' => $selectedAlumni->sekolah_asal,
                            'alasan_keluar' => $selectedAlumni->alasan_keluar,
                            'tanggal_keluar' => $selectedAlumni->tanggal_keluar,
                            'kelas_keluar' => $selectedAlumni->kelas_keluar,
                            'sekolah_tujuan' => $selectedAlumni->sekolah_tujuan,
                            'tahun_lulus' => $selectedAlumni->tahun_lulus,
                            'nomor_peserta_psaj' => $selectedAlumni->nomor_peserta_psaj,
                            'nomor_skl' => $selectedAlumni->nomor_skl,
                            'nomor_ijazah_sma' => $selectedAlumni->nomor_ijazah_sma,
                            'tanggal_ijazah_sma' => $selectedAlumni->tanggal_ijazah_sma,
                            'nomor_transkrip' => $selectedAlumni->nomor_transkrip,
                            'kegiatan_setelah_lulus' => $selectedAlumni->kegiatan_setelah_lulus,
                            'edit_url' => route('admin.alumni.update', $selectedAlumni->id),
                            'full_edit_url' => route('admin.form-pendataan.edit', $selectedAlumni->id),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" onclick="openAlumniEdit(this)">Edit Kontrol Induk</button>
                        <a class="btn o" href="{{ route('admin.form-pendataan.edit', $selectedAlumni->id) }}">Edit biodata lengkap</a>
                        <a class="btn o" href="{{ route('admin.data-siswa.pdf', $selectedAlumni->id) }}" target="_blank" rel="noopener">Cetak PDF</a>
                    </div>
                </section>
                @if($selectedAlumni->isGraduated())
                    <form class="alumni-side-form" method="POST" action="{{ route('admin.alumni.cancel-graduation', $selectedAlumni->id) }}" onsubmit="return confirm('Batalkan kelulusan dan kembalikan siswa menjadi aktif?')">
                        @csrf
                        <input type="hidden" name="aj" value="{{ $aj }}">
                        <input type="hidden" name="ay" value="{{ $ay }}">
                        <input type="hidden" name="aq" value="{{ $aq }}">
                        <input type="hidden" name="page" value="{{ $alumni->currentPage() }}">
                        <button type="submit" class="btn o">Batalkan kelulusan</button>
                    </form>
                @endif
            @endif
        </div>
    </aside>
@endif

<div id="alumni-detail-modal" class="alumni-modal" role="dialog" aria-modal="true" aria-labelledby="alumni-detail-title" onclick="closeAlumniModal(event, this)">
    <div class="alumni-modal-box">
        <div class="alumni-modal-head">
            <div>
                <h3 id="alumni-detail-title">Biodata dan Kontrol Induk</h3>
                <p>Ringkasan data peserta didik</p>
            </div>
            <button type="button" class="alumni-modal-close" aria-label="Tutup" onclick="closeAlumniModal(null, document.getElementById('alumni-detail-modal'))">&times;</button>
        </div>
        <div class="alumni-modal-body">
            <div class="alumni-identity">
                <div class="alumni-avatar" data-detail="inisial">S</div>
                <div>
                    <h4 data-detail="nama_lengkap">Nama siswa</h4>
                    <p>NISN <span data-detail="nisn"></span> · Induk <span data-detail="nomor_induk"></span></p>
                </div>
                <span class="bd" data-detail="status"></span>
            </div>
            <section class="alumni-detail-section">
                <h4>Registrasi dan asal peserta didik</h4>
                <div class="alumni-detail-grid">
                    <div class="alumni-detail-item"><span>Jenis registrasi</span><b data-detail="jenis_registrasi"></b></div>
                    <div class="alumni-detail-item"><span>Tanggal diterima</span><b data-detail="tanggal_diterima"></b></div>
                    <div class="alumni-detail-item"><span>Kelas masuk</span><b data-detail="kelas_masuk"></b></div>
                    <div class="alumni-detail-item"><span>Sekolah asal</span><b data-detail="sekolah_asal"></b></div>
                </div>
            </section>
            <section class="alumni-detail-section">
                <h4>Kelulusan dan kontrol induk</h4>
                <div class="alumni-detail-grid">
                    <div class="alumni-detail-item"><span>Alasan keluar</span><b data-detail="alasan_keluar"></b></div>
                    <div class="alumni-detail-item"><span>Tanggal keluar</span><b data-detail="tanggal_keluar"></b></div>
                    <div class="alumni-detail-item"><span>Kelas keluar</span><b data-detail="kelas_keluar"></b></div>
                    <div class="alumni-detail-item"><span>Sekolah tujuan</span><b data-detail="sekolah_tujuan"></b></div>
                    <div class="alumni-detail-item"><span>Tahun lulus</span><b data-detail="tahun_lulus"></b></div>
                    <div class="alumni-detail-item"><span>Nomor peserta PSAJ</span><b data-detail="nomor_peserta_psaj"></b></div>
                    <div class="alumni-detail-item"><span>Nomor SKL</span><b data-detail="nomor_skl"></b></div>
                    <div class="alumni-detail-item"><span>Nomor ijazah nasional</span><b data-detail="nomor_ijazah_sma"></b></div>
                    <div class="alumni-detail-item"><span>Tanggal pengesahan ijazah</span><b data-detail="tanggal_ijazah_sma"></b></div>
                    <div class="alumni-detail-item"><span>Nomor transkrip nilai</span><b data-detail="nomor_transkrip"></b></div>
                    <div class="alumni-detail-item"><span>Melanjutkan studi / bekerja</span><b data-detail="kegiatan_setelah_lulus"></b></div>
                </div>
            </section>
            <section class="alumni-detail-section">
                <h4>Biodata Lengkap dan Riwayat Kelas</h4>
                <div data-profile-content aria-live="polite">Memuat biodata lengkap...</div>
            </section>
        </div>
    </div>
</div>

<div id="alumni-edit-modal" class="alumni-modal" role="dialog" aria-modal="true" aria-labelledby="alumni-edit-title" onclick="closeAlumniModal(event, this)">
    <form id="alumni-edit-form" class="alumni-modal-box" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="aj" value="{{ $aj }}">
        <input type="hidden" name="ay" value="{{ $ay }}">
        <input type="hidden" name="aq" value="{{ $aq }}">
        <div class="alumni-modal-head">
            <div>
                <h3 id="alumni-edit-title">Edit Kontrol Induk</h3>
                <p data-edit-name>Perbarui data alumni</p>
            </div>
            <button type="button" class="alumni-modal-close" aria-label="Tutup" onclick="closeAlumniModal(null, document.getElementById('alumni-edit-modal'))">&times;</button>
        </div>
        <div class="alumni-modal-body">
            <div class="alumni-form-grid">
                <div><label>Jenis registrasi</label><input name="jenis_registrasi" data-edit-field="jenis_registrasi"></div>
                <div><label>Tanggal diterima</label><input type="date" name="tanggal_diterima" data-edit-field="tanggal_diterima"></div>
                <div><label>Kelas diterima</label><input name="kelas_diterima" data-edit-field="kelas_diterima"></div>
                <div><label>Rombel diterima</label><input name="rombel_diterima" data-edit-field="rombel_diterima"></div>
                <div><label>Sekolah asal</label><input name="sekolah_asal" data-edit-field="sekolah_asal"></div>
                <div><label>Alasan keluar</label><input name="alasan_keluar" data-edit-field="alasan_keluar"></div>
                <div><label>Tanggal keluar</label><input type="date" name="tanggal_keluar" data-edit-field="tanggal_keluar"></div>
                <div><label>Kelas keluar</label><input name="kelas_keluar" data-edit-field="kelas_keluar"></div>
                <div><label>Sekolah tujuan</label><input name="sekolah_tujuan" data-edit-field="sekolah_tujuan"></div>
                <div><label>Tahun lulus</label><input name="tahun_lulus" maxlength="4" inputmode="numeric" data-edit-field="tahun_lulus"></div>
                <div><label>Nomor peserta PSAJ</label><input name="nomor_peserta_psaj" data-edit-field="nomor_peserta_psaj"></div>
                <div><label>Nomor SKL</label><input name="nomor_skl" data-edit-field="nomor_skl"></div>
                <div><label>Nomor ijazah nasional</label><input name="nomor_ijazah_sma" data-edit-field="nomor_ijazah_sma"></div>
                <div><label>Tanggal pengesahan ijazah</label><input type="date" name="tanggal_ijazah_sma" data-edit-field="tanggal_ijazah_sma"></div>
                <div><label>Nomor transkrip nilai</label><input name="nomor_transkrip" data-edit-field="nomor_transkrip"></div>
                <div><label>Melanjutkan studi / bekerja</label><input name="kegiatan_setelah_lulus" data-edit-field="kegiatan_setelah_lulus"></div>
            </div>
        </div>
        <div class="alumni-modal-foot">
            <a id="alumni-full-edit-link" class="btn o" target="_blank" rel="noopener">Biodata lengkap</a>
            <button type="button" class="btn o" onclick="closeAlumniModal(null, document.getElementById('alumni-edit-modal'))">Batal</button>
            <button type="submit" class="btn a">Simpan perubahan</button>
        </div>
    </form>
</div>

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

function openAlumniDetail(button) {
    const data = alumniData(button);
    if (!data) return;
    const modal = document.getElementById('alumni-detail-modal');
    const display = (value) => value === null || value === undefined || value === '' ? '-' : String(value);
    const formatDate = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value ?? ''));
        return match ? `${match[3]}/${match[2]}/${match[1]}` : display(value);
    };
    modal.querySelectorAll('[data-detail]').forEach((element) => {
        const key = element.dataset.detail;
        let value = data[key];
        if (key === 'inisial') value = String(data.nama_lengkap || 'S').trim().slice(0, 1).toUpperCase();
        if (key === 'kelas_masuk') value = [data.kelas_diterima, data.rombel_diterima].filter(Boolean).join('-');
        if (['tanggal_diterima', 'tanggal_keluar', 'tanggal_ijazah_sma'].includes(key)) value = formatDate(value);
        element.textContent = display(value);
    });
    const profileContent = modal.querySelector('[data-profile-content]');
    const profileTemplate = button.closest('tr').querySelector('.alumni-profile-template');
    profileContent.replaceChildren(profileTemplate.content.cloneNode(true));
    showAlumniModal(modal);
}

function openAlumniEdit(button) {
    const data = alumniData(button);
    if (!data) return;
    const modal = document.getElementById('alumni-edit-modal');
    const form = document.getElementById('alumni-edit-form');
    form.action = data.edit_url;
    document.getElementById('alumni-full-edit-link').href = data.full_edit_url;
    modal.querySelector('[data-edit-name]').textContent = `${data.nama_lengkap} · NISN ${data.nisn || '-'}`;
    modal.querySelectorAll('[data-edit-field]').forEach((input) => {
        input.value = data[input.dataset.editField] ?? '';
    });
    showAlumniModal(modal);
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

document.querySelectorAll('.alumni-row').forEach((row) => {
    const openPanel = () => window.location.assign(row.dataset.panelUrl);
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

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.alumni-modal.on').forEach((modal) => closeAlumniModal(null, modal));
    }
});
</script>
@endpush
@endsection
