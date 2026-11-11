@extends('layouts.app')

@section('title', 'Alumni & Kontrol Induk — JosBook Admin')

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
    @media(max-width:600px){.alumni-modal{padding:8px}.alumni-modal-box{max-height:96vh;border-radius:14px}.alumni-modal-head{padding:14px}.alumni-modal-body{padding:12px}.alumni-detail-grid,.alumni-form-grid,.student-profile-grid{grid-template-columns:1fr}.alumni-identity{align-items:flex-start;padding:15px}.alumni-identity h4{font-size:16px}.alumni-identity .bd{margin-left:auto}.student-profile-identity{align-items:flex-start;padding:13px}.student-profile-identity h4{font-size:15px}}
</style>
@endpush

@section('content')
<h2>Alumni &amp; Kontrol Induk</h2>
<p class="sub">Registrasi masuk dan keluar (Kontrol Induk), kelulusan, dan arsip alumni dalam satu tempat · {{ $totalFiltered }} data · klik Lihat untuk membuka detail tanpa meninggalkan halaman</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="note err" role="alert">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('admin.alumni') }}">
    <div class="bar">
        <select name="aj" onchange="this.form.submit()">
            @foreach($JN as $item)
            <option value="{{ $item }}" {{ $aj === $item ? 'selected' : '' }}>{{ $item }}</option>
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

<div class="note">
    Lihat, edit Kontrol Induk, dan pratinjau cetak terbuka di jendela pada halaman ini. Alumni yang belum diketahui kelanjutan studinya tetap tampil di Pelacakan Alumni sebagai <b>Belum dilacak</b>.
</div>

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
                    <th>Kelengkapan</th>
                    <th style="min-width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alumni as $idx => $s)
                @php
                    $latestRiwayat = $s->riwayatKelas->first();
                    $isGraduated = $s->isGraduated();
                    $status = $isGraduated ? 'Lulus' : ($latestRiwayat->status ?? ($s->alasan_keluar ?: 'Alumni'));
                    $statusClass = $status === 'Lulus' ? 'ok' : (in_array($status, ['Pindah keluar', 'Keluar', 'Pindah']) ? 'w' : '');
                    
                    // Cek kelengkapan data kontrol induk
                    $checkFields = ['alasan_keluar', 'tanggal_keluar', 'kelas_keluar'];
                    if ($status === 'Lulus') {
                        $checkFields = array_merge($checkFields, ['nomor_peserta_psaj', 'nomor_skl', 'nomor_ijazah_sma', 'tanggal_ijazah_sma', 'nomor_transkrip']);
                    } else {
                        $checkFields[] = 'sekolah_tujuan';
                    }
                    $filled = 0;
                    foreach ($checkFields as $f) {
                        if (!empty($s->{$f})) $filled++;
                    }
                    $pct = count($checkFields) > 0 ? round(($filled / count($checkFields)) * 100) : 100;
                    $thnLulusDisplay = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                    $masukKelas = trim(($s->kelas_diterima ?: '') . ($s->rombel_diterima ? '-' . $s->rombel_diterima : ''));
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
                        'edit_url' => route('admin.alumni.update', $s->id),
                        'full_edit_url' => route('admin.form-pendataan.edit', $s->id),
                        'pdf_url' => route('admin.data-siswa.pdf', $s->id),
                        'tracking_url' => route('admin.pelacakan-alumni', array_filter([
                            'lcy' => $thnLulusDisplay !== '-' ? ($s->tahun_lulus ?: $thnLulusDisplay) : null,
                        ])),
                    ];
                    $studentJson = json_encode($studentDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                @endphp
                <tr>
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
                        @if($pct >= 100)
                            <span class="bd ok">Lengkap</span>
                        @else
                            <span class="bd w">{{ $pct }}%</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <button type="button" class="btn" data-alumni="{{ $studentJson }}" onclick="openAlumniDetail(this)">Lihat</button>
                        <button type="button" class="btn a" data-alumni="{{ $studentJson }}" onclick="openAlumniEdit(this)">✏ Edit</button>
                        <button type="button" class="btn o" data-alumni="{{ $studentJson }}" onclick="openAlumniPdf(this)">⬇ PDF</button>
                        @if($isGraduated && empty($s->kegiatan_setelah_lulus))
                            <a href="{{ $studentDetails['tracking_url'] }}" class="btn o" title="Catat kelanjutan alumni">Lacak</a>
                        @endif
                        <form method="POST" action="{{ route('admin.data-siswa.destroy', $s->id) }}" style="display:inline" onsubmit="return confirm('Pindahkan data ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x">🗑</button>
                        </form>
                        <template class="alumni-profile-template">
<<<<<<< HEAD
                            @include('admin.siswa-detail-content', [
                                'siswa' => $s,
                                'includeGraduationDetails' => false,
                                'canManageAcademicRecords' => false,
                            ])
=======
                            @include('admin.siswa-detail-content', ['siswa' => $s, 'includeGraduationDetails' => false])
>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
                        </template>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="22" style="text-align:center;padding:24px;color:var(--mu)">
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

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.alumni-modal.on').forEach((modal) => closeAlumniModal(null, modal));
    }
});
</script>
@endpush
@endsection
