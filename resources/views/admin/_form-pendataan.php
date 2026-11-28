@php
    $edit = $formData['edit'];
    $kelasList = $formData['kelasList'];
    $tahunAjaranList = $formData['tahunAjaranList'];
    $tahunAjaranTerpilih = $formData['tahunAjaranTerpilih'];
    $alumniEdit = (bool) ($formData['alumniEdit'] ?? false);
    $alumniTracking = $formData['alumniTracking'] ?? null;
    $alumniReturn = $formData['alumniReturn'] ?? [];
    $tahunAjaranForm = old('tahun_ajaran', $tahunAjaranTerpilih);
    $riwayatTerpilih = $edit?->riwayatKelas->firstWhere('tahunAjaran.nama', $tahunAjaranForm);
    $lampiranBerkas = [
        'kk' => ['Kartu Keluarga (KK)', 'kk_ada', '📄 Dokumen KK asli atau legalisir'],
        'akta' => ['Akta Kelahiran', 'akta_ada', '📜 Dokumen Akta Kelahiran asli atau legalisir'],
        'ijazah_smp' => ['Ijazah SMP / MTs', 'ijazah_smp_ada', '🎓 Ijazah kelulusan jenjang sebelumnya'],
        'surat_pindah' => ['Surat Pindah (Jika Siswa Pindahan)', 'surat_pindah_ada', '📨 Surat keterangan mutasi dari sekolah asal'],
    ];
    $formAction = $edit
        ? route('admin.data-siswa.update', $edit->id)
        : route('admin.data-siswa.store');
    $cancelUrl = $formCloseUrl;
@endphp

@push('styles')
<style>
    /* ── POPUP CONTAINER & CENTERING (Identical to Alumni Modal) ── */
    .student-form-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 1100 !important;
        display: none !important;
        place-items: center !important;
        padding: 20px !important;
        background: rgba(9, 24, 43, 0.62) !important;
        backdrop-filter: blur(4px) !important;
        -webkit-backdrop-filter: blur(4px) !important;
    }

    .student-form-modal.on {
        display: grid !important;
        animation: adminFadeIn 0.2s ease-out !important;
    }

    .student-form-box {
        display: flex !important;
        flex-direction: column !important;
        width: min(1040px, 100%) !important;
        height: min(92vh, 920px) !important;
        max-height: calc(100vh - 32px) !important;
        border-radius: 20px !important;
        background: #f8fafd !important;
        border: 1px solid #dce5ef !important;
        box-shadow: 0 24px 80px rgba(6, 23, 47, 0.45) !important;
        overflow: hidden !important;
        animation: adminModalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    /* ── HEADER ── */
    .student-form-head {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 16px 24px !important;
        background: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        flex-shrink: 0 !important;
    }

    .student-form-head-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .student-form-head-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1967d2;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(25, 103, 210, 0.15);
    }

    .student-form-head h3 {
        margin: 0 !important;
        color: #0f172a !important;
        font-size: 19px !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .student-form-head p {
        margin: 3px 0 0 !important;
        color: #64748b !important;
        font-size: 12.5px !important;
    }

    .student-form-close {
        width: 36px !important;
        height: 36px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: 20px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: none !important;
        transition: all 0.15s ease !important;
    }

    .student-form-close:hover {
        background: #fee2e2 !important;
        color: #dc2626 !important;
        border-color: #fca5a5 !important;
        transform: scale(1.05);
    }

    /* ── TABS NAVIGATION BAR ── */
    .student-form-nav {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 20px;
        overflow-x: auto;
        white-space: nowrap;
        flex-shrink: 0;
        display: flex;
        gap: 6px;
        scrollbar-width: thin;
    }

    .student-form-nav::-webkit-scrollbar {
        height: 4px;
    }

    .student-form-nav::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .sf-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .sf-tab-btn:hover {
        background: #e2e8f0;
        color: #1967d2;
        border-color: #cbd5e1;
    }

    .sf-tab-btn.on {
        background: linear-gradient(135deg, #1967d2 0%, #05286b 100%) !important;
        color: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 4px 12px rgba(25, 103, 210, 0.35) !important;
    }

    .sf-tab-badge-req {
        font-size: 9.5px;
        font-weight: 800;
        padding: 1px 5px;
        border-radius: 6px;
        background: #ef4444;
        color: #ffffff;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .sf-tab-btn.on .sf-tab-badge-req {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    /* ── SCROLLABLE BODY ── */
    .student-form-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 24px;
    }

    /* ── SECTION CARDS ── */
    .sf-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .sf-card:last-child {
        margin-bottom: 0;
    }

    .sf-card.highlight {
        background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%);
        border-color: #bfdbfe;
        box-shadow: 0 4px 16px rgba(25, 103, 210, 0.08);
    }

    .sf-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sf-card-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .sf-card-subtitle {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }

    /* ── FORM CONTROLS & LABELS ── */
    .sf-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .sf-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .sf-col-span-full {
        grid-column: 1 / -1;
    }

    .sf-col-span-2 {
        grid-column: span 2;
    }

    .sf-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sf-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12.5px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .sf-req {
        color: #ef4444;
        font-size: 13px;
        font-weight: 800;
        margin-left: 2px;
    }

    .sf-input,
    .sf-select,
    .sf-textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        padding: 9px 13px;
        font-size: 13.5px;
        color: #0f172a;
        background: #ffffff;
        transition: all 0.15s ease;
        outline: none;
        font-family: inherit;
    }

    .sf-input:focus,
    .sf-select:focus,
    .sf-textarea:focus {
        border-color: #1967d2 !important;
        box-shadow: 0 0 0 3.5px rgba(25, 103, 210, 0.14) !important;
        background: #ffffff !important;
    }

    .sf-input::placeholder,
    .sf-textarea::placeholder {
        color: #94a3b8;
        font-size: 13px;
    }

    .sf-hint {
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
    }

    /* ── FOTO BOX ── */
    .sf-photo-box {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        margin-bottom: 0;
    }

    .sf-photo-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .sf-photo-preview {
        width: 78px;
        height: 104px;
        border-radius: 10px;
        background: #e2e8f0;
        border: 1.5px solid #cbd5e1;
        display: grid;
        place-items: center;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .sf-photo-preview.photo-2x3 {
        height: 117px;
    }

    .sf-photo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    @media (max-width: 680px) {
        .sf-photo-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ── UPLOAD BERKAS CARDS ── */
    .sf-berkas-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .sf-berkas-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        transition: all 0.2s ease;
    }

    .sf-berkas-card:hover {
        border-color: #bfdbfe;
        box-shadow: 0 6px 18px rgba(25, 103, 210, 0.08);
    }

    .sf-berkas-title {
        font-size: 13.5px;
        font-weight: 800;
        color: #1967d2;
        margin: 0 0 4px;
    }

    .sf-berkas-desc {
        font-size: 11.5px;
        color: #64748b;
        margin: 0 0 12px;
    }

    /* ── STICKY FOOTER ── */
    .student-form-foot {
        background: #ffffff !important;
        border-top: 1px solid #e2e8f0 !important;
        padding: 14px 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-shrink: 0 !important;
        z-index: 10 !important;
    }

    .sf-step-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 99px;
        background: #eef4fc;
        color: #1967d2;
        font-size: 12px;
        font-weight: 700;
    }

    .sf-foot-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 820px) {
        .sf-grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }
        .sf-berkas-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .student-form-modal {
            padding: 8px !important;
        }
        .student-form-box {
            width: 100% !important;
            height: 96vh !important;
            max-height: calc(100vh - 16px) !important;
            border-radius: 16px !important;
        }
        .student-form-body {
            padding: 14px;
        }
        .student-form-head {
            padding: 12px 16px !important;
        }
        .student-form-foot {
            padding: 12px 16px !important;
            flex-direction: column;
            gap: 10px;
        }
        .sf-grid-3,
        .sf-grid-2 {
            grid-template-columns: 1fr;
        }
        .sf-col-span-2,
        .sf-col-span-full {
            grid-column: 1 / -1;
        }
        .sf-step-indicator {
            width: 100%;
            justify-content: center;
        }
        .sf-foot-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
@endpush

{{-- Modal Overlay (Centered grid exactly like alumni-modal) --}}
<div id="student-form-modal" class="student-form-modal {{ ($formOpen ?? false) || ($alumniEdit ?? false) ? 'on' : '' }}" role="dialog" aria-modal="true" aria-labelledby="student-form-title" onclick="closeStudentForm(event, this)">
    <aside id="student-form-panel" class="student-form-box" onclick="event.stopPropagation()">
    
    {{-- Modal Header --}}
    <div class="student-form-head">
        <div class="student-form-head-title-wrap">
            <div class="student-form-head-icon">
                {{ $edit ? '✏️' : '🎓' }}
            </div>
            <div>
                <h3 id="student-form-title">{{ $edit ? ($alumniEdit ? 'Ubah Data Alumni' : 'Ubah Data Siswa') : 'Tambah Siswa Baru' }}</h3>
                <p>
                    @if($edit)
                        <strong>{{ $edit->nama_lengkap }}</strong> · NISN {{ $edit->nisn ?? '-' }}
                    @else
                        Lengkapi identitas, akademik, dan dokumen siswa. Kolom bertanda <span style="color:#ef4444;font-weight:700">*</span> wajib diisi.
                    @endif
                </p>
            </div>
        </div>
        <a class="student-form-close" href="{{ $cancelUrl }}" aria-label="Tutup panel" onclick="return closeStudentForm(event)">&times;</a>
    </div>

    {{-- Form Wrapping Header, Tabs, Body, and Sticky Footer --}}
    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" id="form-pendataan-siswa" novalidate style="display:flex;flex-direction:column;flex:1;min-height:0;margin:0">
        @csrf
        <input type="hidden" name="_form_panel" value="1">
        <input type="hidden" name="_form_mode" value="{{ $edit ? 'edit' : 'create' }}">
        @if($edit)
            @method('PUT')
            <input type="hidden" name="_form_siswa" value="{{ $edit->id }}">
        @endif
        @if($alumniEdit)
            <input type="hidden" name="alumni_edit" value="1">
            <input type="hidden" name="return_tab" value="{{ $alumniReturn['tab'] ?? 'kontrol-induk' }}">
            <input type="hidden" name="return_aj" value="{{ $alumniReturn['aj'] ?? '' }}">
            <input type="hidden" name="return_ay" value="{{ $alumniReturn['ay'] ?? '' }}">
            <input type="hidden" name="return_kelas" value="{{ $alumniReturn['kelas'] ?? '' }}">
            <input type="hidden" name="return_aq" value="{{ $alumniReturn['aq'] ?? '' }}">
            <input type="hidden" name="return_page" value="{{ $alumniReturn['page'] ?? '' }}">
        @endif

        {{-- Tabs Navigation Bar --}}
        <div class="student-form-nav">
            <span class="sf-tab-btn tab-btn on" data-target="tab-a">
                <span>👤 1. Peserta Didik</span>
                <span class="sf-tab-badge-req">Wajib</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-b">
                <span>📍 2. Tempat Tinggal</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-c">
                <span>🩺 3. Kesehatan</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-d">
                <span>🏫 4. Pendidikan Asal</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-e">
                <span>👨‍👩‍👧 5. Orang Tua</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-f">
                <span>🤝 6. Wali</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-g">
                <span>🚪 7. Mutasi Keluar</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-h">
                <span>🎓 8. Pasca Lulus</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-i">
                <span>📝 9. Catatan</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-ki">
                <span>📋 10. Kontrol Induk</span>
            </span>
            <span class="sf-tab-btn tab-btn" data-target="tab-berkas">
                <span>📁 11. Lampiran Berkas</span>
            </span>
        </div>

        {{-- Scrollable Form Body --}}
        <div class="student-form-body">
            @if($errors->any())
                <div class="note err" role="alert" style="margin-bottom:18px;border-radius:12px">
                    <b>Perhatian:</b> {{ $errors->first() }}
                </div>
            @endif
            @if(session('error'))
                <div class="note err" role="alert" style="margin-bottom:18px;border-radius:12px">
                    <b>Gagal:</b> {{ session('error') }}
                </div>
            @endif
            @if(count($tahunAjaranList) === 0)
                <div class="note err" role="alert" style="margin-bottom:18px;border-radius:12px">
                    <b>Data Master Kurang:</b> Pilihan tahun ajaran belum tersedia. Silakan isi data tahun ajaran terlebih dahulu.
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════
                 TAB A: PESERTA DIDIK (DATA UTAMA & PENEMPATAN KELAS)
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-a" class="tab-pane">
                
                {{-- Card 1: Foto & Identitas Pokok --}}
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">👤 Identitas Pokok Siswa</h4>
                            <p class="sf-card-subtitle">Biodata utama peserta didik sesuai dengan dokumen kependudukan resmi</p>
                        </div>
                    </div>

                    <div class="sf-photo-grid">
                        @php
                            $hasStoredPhotoSlots = $edit && collect(array_keys(\App\Support\FotoSiswa::SLOTS))
                                ->contains(fn ($slot) => filled($edit->{$slot}));
                        @endphp
                        @foreach(\App\Support\FotoSiswa::SLOTS as $photoSlot => $details)
                            @php
                                $inputName = $photoSlot.'_file';
                                $previewId = 'preview-'.$photoSlot;
                                $labelId = 'label-'.$photoSlot;
                                $photoPath = $edit?->{$photoSlot};
                                $usesLatestPhotoFallback = ! $hasStoredPhotoSlots
                                    && $photoSlot === 'foto_masuk_3x4'
                                    && filled($edit?->foto);
                                if ($usesLatestPhotoFallback) {
                                    $photoPath = $edit->foto;
                                }
                                $photoUrl = $edit && filled($photoPath)
                                    ? ($usesLatestPhotoFallback
                                        ? route('admin.data-siswa.foto', $edit->id)
                                        : route('admin.data-siswa.foto', ['id' => $edit->id, 'slot' => $photoSlot]))
                                    : null;
                            @endphp
                            <div class="sf-photo-box">
                                <div class="sf-photo-preview {{ $details['size'] === '2x3' ? 'photo-2x3' : '' }}">
                                    @if($photoUrl)
                                        <img id="{{ $previewId }}" src="{{ $photoUrl }}" alt="{{ $details['label'] }} {{ $details['size'] }}">
                                    @else
                                        <img id="{{ $previewId }}" style="display:none" alt="{{ $details['label'] }} {{ $details['size'] }}">
                                        <span id="{{ $labelId }}" style="font-size:11px;color:#64748b;text-align:center;font-weight:600">{{ $details['size'] }}<br>{{ $details['label'] }}</span>
                                    @endif
                                </div>
                                <div style="flex:1">
                                    <label for="{{ $inputName }}" class="sf-label" style="margin-bottom:4px">{{ $details['label'] }} {{ $details['size'] }}</label>
                                    <input type="file" name="{{ $inputName }}" id="{{ $inputName }}" accept=".jpg,.jpeg,.png,.webp" class="sf-input sf-photo-input" data-preview="{{ $previewId }}" data-label="{{ $labelId }}" style="font-size:12.5px;padding:6px 10px">
                                    <p class="sf-hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Nama Lengkap Siswa <span class="sf-req">*</span></label>
                            <input type="text" name="nama_lengkap" class="sf-input" value="{{ old('nama_lengkap', $edit->nama_lengkap ?? '') }}" placeholder="Nama sesuai akta kelahiran / ijazah" required>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nama Panggilan</label>
                            <input type="text" name="nama_panggilan" class="sf-input" value="{{ old('nama_panggilan', $edit->nama_panggilan ?? '') }}" placeholder="Nama panggilan sehari-hari">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Jenis Kelamin <span class="sf-req">*</span></label>
                            <select name="jenis_kelamin" class="sf-select">
                                <option value="L" {{ old('jenis_kelamin', $edit->jenis_kelamin ?? 'L') == 'L' ? 'selected' : '' }}>👦 Laki-laki (L)</option>
                                <option value="P" {{ old('jenis_kelamin', $edit->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>👧 Perempuan (P)</option>
                            </select>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">NISN (10 Digit) <span class="sf-req">*</span></label>
                            <input type="text" name="nisn" class="sf-input sync-nisn" maxlength="10" value="{{ old('nisn', $edit->nisn ?? '') }}" placeholder="Contoh: 0071234567" required>
                            <span class="sf-hint">10 digit nomor induk siswa nasional</span>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Induk Sekolah (NIS)</label>
                            <input type="text" name="nomor_induk" class="sf-input sync-nis" value="{{ old('nomor_induk', $edit->nomor_induk ?? '') }}" placeholder="Nomor induk dari buku induk">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">NIK Siswa (16 Digit)</label>
                            <input type="text" name="nik" class="sf-input" maxlength="16" value="{{ old('nik', $edit->nik ?? '') }}" placeholder="Nomor Induk Kependudukan">
                            <span class="sf-hint">Sesuai Kartu Keluarga (KK)</span>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Kelahiran & Karakteristik Diri --}}
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🎂 Tempat Lahir &amp; Karakteristik Pribadi</h4>
                            <p class="sf-card-subtitle">Informasi kelahiran, agama, dan data keluarga kandung</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="sf-input" value="{{ old('tempat_lahir', $edit->tempat_lahir ?? '') }}" placeholder="Kota / Kabupaten tempat lahir">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="sf-input" value="{{ old('tanggal_lahir', $edit?->tanggal_lahir ? \Carbon\Carbon::parse($edit->tanggal_lahir)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Agama</label>
                            <select name="agama" class="sf-select">
                                @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $ag)
                                    <option value="{{ $ag }}" {{ old('agama', $edit->agama ?? 'Islam') == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kewarganegaraan</label>
                            <input type="text" name="kewarganegaraan" class="sf-input" value="{{ old('kewarganegaraan', $edit->kewarganegaraan ?? 'Warga Negara Indonesia') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Anak Ke-</label>
                            <input type="number" name="anak_ke" class="sf-input" value="{{ old('anak_ke', $edit->anak_ke ?? '') }}" placeholder="Contoh: 1">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Jumlah Saudara Kandung</label>
                            <input type="text" name="jumlah_saudara" class="sf-input" value="{{ old('jumlah_saudara', $edit->jumlah_saudara ?? '') }}" placeholder="Contoh: 2 (Dua)">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Status Yatim / Piatu</label>
                            <select name="status_yatim" class="sf-select">
                                <option value="Bukan" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Bukan' ? 'selected' : '' }}>Bukan (Lengkap)</option>
                                <option value="Yatim" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Yatim' ? 'selected' : '' }}>Yatim (Ayah Wafat)</option>
                                <option value="Piatu" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Piatu' ? 'selected' : '' }}>Piatu (Ibu Wafat)</option>
                                <option value="Yatim Piatu" {{ old('status_yatim', $edit->status_yatim ?? '') == 'Yatim Piatu' ? 'selected' : '' }}>Yatim Piatu (Keduanya Wafat)</option>
                            </select>
                        </div>

                        <div class="sf-group sf-col-span-2">
                            <label class="sf-label">Bahasa Sehari-hari</label>
                            <input type="text" name="bahasa_sehari_hari" class="sf-input" value="{{ old('bahasa_sehari_hari', $edit->bahasa_sehari_hari ?? 'Indonesia') }}" placeholder="Indonesia, Jawa, dll.">
                        </div>
                    </div>
                </div>

                {{-- Card 3: Penempatan Kelas & Tahun Ajaran (Krusial untuk Siswa Aktif) --}}
                @unless($alumniEdit)
                <div class="sf-card highlight">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🏫 Penempatan Akademik &amp; Rombel</h4>
                            <p class="sf-card-subtitle">Pilih tahun ajaran berjalan, penempatan kelas, dan status keaktifan</p>
                        </div>
                        <span class="bd ok" style="padding:4px 12px;font-size:11.5px">Wajib Ditentukan</span>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Tahun Ajaran <span class="sf-req">*</span></label>
                            <select name="tahun_ajaran" class="sf-select" required>
                                @foreach($tahunAjaranList as $tahunAjaran)
                                    <option value="{{ $tahunAjaran }}" {{ $tahunAjaranForm === $tahunAjaran ? 'selected' : '' }}>{{ $tahunAjaran }}</option>
                                @endforeach
                            </select>
                            <span class="sf-hint">Tahun ajaran penempatan data</span>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kelas Saat Ini <span class="sf-req">*</span></label>
                            <select name="kelas" class="sf-select" required>
                                @foreach($kelasList as $k)
                                    <option value="{{ $k }}" {{ old('kelas', $riwayatTerpilih?->kelas?->nama ?? '') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                @endforeach
                            </select>
                            <span class="sf-hint">Rombongan belajar terdaftar</span>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Status Keaktifan Siswa</label>
                            <select name="status" class="sf-select">
                                @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                                    <option value="{{ $st }}" {{ old('status', $riwayatTerpilih?->status ?? 'Aktif') == $st ? 'selected' : '' }}>● {{ $st }}</option>
                                @endforeach
                            </select>
                            <span class="sf-hint">Status dalam buku induk</span>
                        </div>
                    </div>
                </div>
                @endunless

            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB B: TEMPAT TINGGAL
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-b" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">📍 Alamat Domisili Siswa</h4>
                            <p class="sf-card-subtitle">Alamat tempat tinggal siswa saat ini selama menempuh pendidikan</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Alamat Jalan / Dusun</label>
                            <input type="text" name="alamat_jalan" class="sf-input" value="{{ old('alamat_jalan', $edit->alamat_jalan ?? '') }}" placeholder="Nama jalan, nomor rumah, atau nama dusun">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">RT / RW</label>
                            <input type="text" name="alamat_rt_rw" class="sf-input" value="{{ old('alamat_rt_rw', $edit->alamat_rt_rw ?? '') }}" placeholder="001/002">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Desa / Kelurahan</label>
                            <input type="text" name="alamat_desa" class="sf-input" value="{{ old('alamat_desa', $edit->alamat_desa ?? '') }}" placeholder="Desa atau kelurahan">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kecamatan</label>
                            <input type="text" name="alamat_kecamatan" class="sf-input" value="{{ old('alamat_kecamatan', $edit->alamat_kecamatan ?? '') }}" placeholder="Kecamatan">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kabupaten / Kota</label>
                            <input type="text" name="alamat_kabupaten" class="sf-input" value="{{ old('alamat_kabupaten', $edit->alamat_kabupaten ?? '') }}" placeholder="Kabupaten / Kota">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Provinsi</label>
                            <input type="text" name="alamat_provinsi" class="sf-input" value="{{ old('alamat_provinsi', $edit->alamat_provinsi ?? 'Jawa Timur') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kode Pos</label>
                            <input type="text" name="alamat_kode_pos" class="sf-input" value="{{ old('alamat_kode_pos', $edit->alamat_kode_pos ?? '') }}" placeholder="Kode pos">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Telepon / WhatsApp Siswa</label>
                            <input type="text" name="nomor_hp" class="sf-input" value="{{ old('nomor_hp', $edit->nomor_hp ?? '') }}" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tinggal Bersama</label>
                            <select name="tempat_tinggal" class="sf-select">
                                <option value="Orang Tua" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? 'Orang Tua') == 'Orang Tua' ? 'selected' : '' }}>Orang Tua</option>
                                <option value="Wali" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Wali' ? 'selected' : '' }}>Wali / Saudara</option>
                                <option value="Kost" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Kost' ? 'selected' : '' }}>Kost Mandiri</option>
                                <option value="Asrama" {{ old('tempat_tinggal', $edit->tempat_tinggal ?? '') == 'Asrama' ? 'selected' : '' }}>Pondok Pesantren / Asrama</option>
                            </select>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Jarak ke Sekolah (KM)</label>
                            <input type="number" step="0.1" name="jarak_ke_sekolah_km" class="sf-input" value="{{ old('jarak_ke_sekolah_km', $edit->jarak_ke_sekolah_km ?? '') }}" placeholder="misal: 2.5">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB C: KESEHATAN
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-c" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🩺 Riwayat Kesehatan Siswa</h4>
                            <p class="sf-card-subtitle">Data fisik, golongan darah, dan catatan medis pendukung pembelajaran</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Golongan Darah</label>
                            <select name="golongan_darah" class="sf-select">
                                <option value="-" {{ old('golongan_darah', $edit->golongan_darah ?? '-') == '-' ? 'selected' : '' }}>- Belum Diketahui -</option>
                                <option value="A" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'A' ? 'selected' : '' }}>A</option>
                                <option value="B" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'B' ? 'selected' : '' }}>B</option>
                                <option value="AB" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'AB' ? 'selected' : '' }}>AB</option>
                                <option value="O" {{ old('golongan_darah', $edit->golongan_darah ?? '') == 'O' ? 'selected' : '' }}>O</option>
                            </select>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tinggi Badan (cm)</label>
                            <input type="number" name="tinggi_badan_cm" class="sf-input" value="{{ old('tinggi_badan_cm', $edit->tinggi_badan_cm ?? '') }}" placeholder="Contoh: 165">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Berat Badan (kg)</label>
                            <input type="number" name="berat_badan_kg" class="sf-input" value="{{ old('berat_badan_kg', $edit->berat_badan_kg ?? '') }}" placeholder="Contoh: 55">
                        </div>

                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Penyakit yang Pernah Diderita</label>
                            <input type="text" name="penyakit" class="sf-input" value="{{ old('penyakit', $edit->penyakit ?? '') }}" placeholder="Contoh: Asma, Alergi debu, dll. (kosongkan jika tidak ada)">
                        </div>

                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Kelainan Jasmani / Fisik</label>
                            <input type="text" name="kelainan_fisik" class="sf-input" value="{{ old('kelainan_fisik', $edit->kelainan_fisik ?? '') }}" placeholder="Kosongkan jika tidak ada kelainan jasmani">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB D: PENDIDIKAN SEBELUMNYA
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-d" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🏫 Riwayat Pendidikan SMP / MTs</h4>
                            <p class="sf-card-subtitle">Asal sekolah dan nomor ijazah jenjang sebelumnya</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Sekolah Asal SMP / MTs</label>
                            <input type="text" name="sekolah_asal" class="sf-input" value="{{ old('sekolah_asal', $edit->sekolah_asal ?? '') }}" placeholder="Contoh: SMPN 1 Rejoso">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Ijazah SMP</label>
                            <input type="text" name="nomor_ijazah_smp" class="sf-input" value="{{ old('nomor_ijazah_smp', $edit->nomor_ijazah_smp ?? '') }}" placeholder="Nomor ijazah SMP">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tanggal Ijazah SMP</label>
                            <input type="text" name="tanggal_ijazah_smp" class="sf-input" value="{{ old('tanggal_ijazah_smp', $edit->tanggal_ijazah_smp ?? '') }}" placeholder="misal: 09 Juni 2023">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Lama Belajar di SMP (Tahun)</label>
                            <input type="number" name="lama_belajar_tahun" class="sf-input" value="{{ old('lama_belajar_tahun', $edit->lama_belajar_tahun ?? 3) }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Jenis Registrasi Masuk</label>
                            <select name="jenis_registrasi" class="sf-select">
                                <option value="Siswa Baru" {{ old('jenis_registrasi', $edit->jenis_registrasi ?? 'Siswa Baru') == 'Siswa Baru' ? 'selected' : '' }}>Siswa Baru (Tingkat X)</option>
                                <option value="Pindahan" {{ old('jenis_registrasi', $edit->jenis_registrasi ?? '') == 'Pindahan' ? 'selected' : '' }}>Pindahan dari Sekolah Lain</option>
                            </select>
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tanggal Diterima di Sekolah</label>
                            <input type="date" name="tanggal_diterima" class="sf-input" value="{{ old('tanggal_diterima', $edit?->tanggal_diterima ? \Carbon\Carbon::parse($edit->tanggal_diterima)->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                </div>

                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">📨 Detail Tambahan (Khusus Siswa Pindahan)</h4>
                            <p class="sf-card-subtitle">Hanya diisi jika jenis registrasi adalah "Pindahan"</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Pindahan Dari Sekolah</label>
                            <input type="text" name="pindahan_dari_sekolah" class="sf-input" value="{{ old('pindahan_dari_sekolah', $edit->pindahan_dari_sekolah ?? '') }}" placeholder="Nama SMA/SMK asal jika pindahan">
                        </div>

                        <div class="sf-group sf-col-span-2">
                            <label class="sf-label">Alasan Pindah</label>
                            <input type="text" name="alasan_pindah" class="sf-input" value="{{ old('alasan_pindah', $edit->alasan_pindah ?? '') }}" placeholder="Alasan kepindahan ke sekolah ini">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Diterima di Tingkat / Kelas</label>
                            <input type="text" name="kelas_diterima" class="sf-input" value="{{ old('kelas_diterima', $edit->kelas_diterima ?? 'X') }}" placeholder="X, XI, atau XII">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Rombel Kelas Ke-</label>
                            <input type="text" name="rombel_diterima" class="sf-input" value="{{ old('rombel_diterima', $edit->rombel_diterima ?? '1') }}" placeholder="1, 2, dll.">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB E: ORANG TUA
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-e" class="tab-pane" style="display:none">
                
                {{-- Data Ayah --}}
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title" style="color:#1d4ed8">👨 Data Ayah Kandung</h4>
                            <p class="sf-card-subtitle">Identitas, profesi, dan kontak ayah kandung siswa</p>
                        </div>
                        <span class="bd" style="background:#dbeafe;color:#1e40af;font-size:11px">Ayah Kandung</span>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Nama Ayah Kandung</label>
                            <input type="text" name="nama_ayah" class="sf-input" value="{{ old('nama_ayah', $edit->nama_ayah ?? '') }}" placeholder="Nama lengkap ayah">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Agama Ayah</label>
                            <input type="text" name="agama_ayah" class="sf-input" value="{{ old('agama_ayah', $edit->agama_ayah ?? 'Islam') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Pendidikan Terakhir Ayah</label>
                            <input type="text" name="pendidikan_ayah" class="sf-input" value="{{ old('pendidikan_ayah', $edit->pendidikan_ayah ?? '') }}" placeholder="SD / SMP / SMA / S1 / S2">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Pekerjaan Ayah</label>
                            <input type="text" name="pekerjaan_ayah" class="sf-input" value="{{ old('pekerjaan_ayah', $edit->pekerjaan_ayah ?? '') }}" placeholder="PNS, Wiraswasta, Petani, dll.">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Penghasilan Rata-rata Ayah</label>
                            <input type="text" name="penghasilan_ayah" class="sf-input" value="{{ old('penghasilan_ayah', $edit->penghasilan_ayah ?? '') }}" placeholder="mis. Rp 2.000.000 - Rp 5.000.000">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">No. Telepon / WhatsApp Ayah</label>
                            <input type="text" name="nomor_hp_ayah" class="sf-input" value="{{ old('nomor_hp_ayah', $edit->nomor_hp_ayah ?? '') }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                </div>

                {{-- Data Ibu --}}
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title" style="color:#be185d">👩 Data Ibu Kandung</h4>
                            <p class="sf-card-subtitle">Identitas, profesi, dan kontak ibu kandung siswa</p>
                        </div>
                        <span class="bd" style="background:#fce7f3;color:#9d174d;font-size:11px">Ibu Kandung</span>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Nama Ibu Kandung</label>
                            <input type="text" name="nama_ibu" class="sf-input" value="{{ old('nama_ibu', $edit->nama_ibu ?? '') }}" placeholder="Nama lengkap ibu">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Agama Ibu</label>
                            <input type="text" name="agama_ibu" class="sf-input" value="{{ old('agama_ibu', $edit->agama_ibu ?? 'Islam') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Pendidikan Terakhir Ibu</label>
                            <input type="text" name="pendidikan_ibu" class="sf-input" value="{{ old('pendidikan_ibu', $edit->pendidikan_ibu ?? '') }}" placeholder="SD / SMP / SMA / S1 / S2">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Pekerjaan Ibu</label>
                            <input type="text" name="pekerjaan_ibu" class="sf-input" value="{{ old('pekerjaan_ibu', $edit->pekerjaan_ibu ?? '') }}" placeholder="Ibu Rumah Tangga, PNS, dll.">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Penghasilan Rata-rata Ibu</label>
                            <input type="text" name="penghasilan_ibu" class="sf-input" value="{{ old('penghasilan_ibu', $edit->penghasilan_ibu ?? '') }}" placeholder="mis. Rp 1.000.000 - Rp 3.000.000">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">No. Telepon / WhatsApp Ibu</label>
                            <input type="text" name="nomor_hp_ibu" class="sf-input" value="{{ old('nomor_hp_ibu', $edit->nomor_hp_ibu ?? '') }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                </div>

                {{-- Alamat Orang Tua --}}
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🏡 Alamat Orang Tua</h4>
                            <p class="sf-card-subtitle">Alamat domisili orang tua (kosongkan jika sama persis dengan alamat siswa)</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Jalan / Dusun / Desa</label>
                            <input type="text" name="ortu_alamat_jalan" class="sf-input" value="{{ old('ortu_alamat_jalan', $edit->ortu_alamat_jalan ?? '') }}" placeholder="Alamat rumah orang tua">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Kecamatan</label>
                            <input type="text" name="ortu_alamat_kecamatan" class="sf-input" value="{{ old('ortu_alamat_kecamatan', $edit->ortu_alamat_kecamatan ?? '') }}" placeholder="Kecamatan">
                        </div>

                        <div class="sf-group sf-col-span-2">
                            <label class="sf-label">Kabupaten / Kota</label>
                            <input type="text" name="ortu_alamat_kabupaten" class="sf-input" value="{{ old('ortu_alamat_kabupaten', $edit->ortu_alamat_kabupaten ?? '') }}" placeholder="Kabupaten / Kota">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB F: WALI
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-f" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🤝 Data Wali Siswa</h4>
                            <p class="sf-card-subtitle">Hanya diisi jika peserta didik berada di bawah perwalian atau tidak tinggal bersama orang tua</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Nama Lengkap Wali</label>
                            <input type="text" name="nama_wali" class="sf-input" value="{{ old('nama_wali', $edit->nama_wali ?? '') }}" placeholder="Nama wali siswa">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Agama Wali</label>
                            <input type="text" name="agama_wali" class="sf-input" value="{{ old('agama_wali', $edit->agama_wali ?? '') }}" placeholder="Islam / Kristen / dll.">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Pekerjaan Wali</label>
                            <input type="text" name="pekerjaan_wali" class="sf-input" value="{{ old('pekerjaan_wali', $edit->pekerjaan_wali ?? '') }}" placeholder="Profesi / Pekerjaan wali">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">No. Telepon / WhatsApp Wali</label>
                            <input type="text" name="nomor_hp_wali" class="sf-input" value="{{ old('nomor_hp_wali', $edit->nomor_hp_wali ?? '') }}" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="sf-group sf-col-span-2">
                            <label class="sf-label">Alamat Lengkap Wali</label>
                            <input type="text" name="wali_alamat_jalan" class="sf-input" value="{{ old('wali_alamat_jalan', $edit->wali_alamat_jalan ?? '') }}" placeholder="Alamat tempat tinggal wali">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB G: MENINGGALKAN SEKOLAH
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-g" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🚪 Catatan Meninggalkan Sekolah / Kelulusan</h4>
                            <p class="sf-card-subtitle">Diisi saat peserta didik pindah keluar, mutasi, atau menyelesaikan pendidikan (lulus)</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Tanggal Keluar</label>
                            <input type="date" name="tanggal_keluar" class="sf-input" value="{{ old('tanggal_keluar', $edit?->tanggal_keluar ? \Carbon\Carbon::parse($edit->tanggal_keluar)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Keluar dari Kelas</label>
                            <input type="text" name="kelas_keluar" class="sf-input" value="{{ old('kelas_keluar', $edit->kelas_keluar ?? '') }}" placeholder="misal: XII-IPA-1">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Alasan Keluar</label>
                            <input type="text" name="alasan_keluar" class="sf-input" value="{{ old('alasan_keluar', $edit->alasan_keluar ?? '') }}" placeholder="Pindah tempat tinggal / Mengundurkan diri">
                        </div>

                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Sekolah Tujuan (Jika Mutasi Keluar)</label>
                            <input type="text" name="sekolah_tujuan" class="sf-input" value="{{ old('sekolah_tujuan', $edit->sekolah_tujuan ?? '') }}" placeholder="Nama sekolah tujuan kepindahan">
                        </div>
                    </div>
                </div>

                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">📜 Ijazah &amp; Kelulusan SMA</h4>
                            <p class="sf-card-subtitle">Nomor pengesahan ijazah, SKL, dan transkrip nilai</p>
                        </div>
                    </div>

                    <div class="sf-grid-3">
                        <div class="sf-group">
                            <label class="sf-label">Nomor Peserta PSAJ</label>
                            <input type="text" name="nomor_peserta_psaj" class="sf-input" value="{{ old('nomor_peserta_psaj', $edit->nomor_peserta_psaj ?? '') }}" placeholder="Nomor peserta ujian">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor SKL</label>
                            <input type="text" name="nomor_skl" class="sf-input sync-skl" value="{{ old('nomor_skl', $edit->nomor_skl ?? '') }}" placeholder="Nomor Surat Keterangan Lulus">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tahun Lulus (4 Digit)</label>
                            <input type="text" name="tahun_lulus" class="sf-input" maxlength="4" value="{{ old('tahun_lulus', $edit->tahun_lulus ?? '') }}" placeholder="Contoh: 2026">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Ijazah Nasional SMA</label>
                            <input type="text" name="nomor_ijazah_sma" class="sf-input sync-ijazah" value="{{ old('nomor_ijazah_sma', $edit->nomor_ijazah_sma ?? '') }}" placeholder="DN-xx/xxxxxxx">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Tanggal Pengesahan Ijazah</label>
                            <input type="date" name="tanggal_ijazah_sma" class="sf-input" value="{{ old('tanggal_ijazah_sma', $edit?->tanggal_ijazah_sma ? \Carbon\Carbon::parse($edit->tanggal_ijazah_sma)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Transkrip Nilai</label>
                            <input type="text" name="nomor_transkrip" class="sf-input sync-transkrip" value="{{ old('nomor_transkrip', $edit->nomor_transkrip ?? '') }}" placeholder="Nomor transkrip">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB H: SELESAI PENDIDIKAN
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-h" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">🎓 Jejak Pasca Kelulusan</h4>
                            <p class="sf-card-subtitle">Perguruan tinggi lanjutan atau dunia usaha / kerja alumni</p>
                        </div>
                    </div>

                    @if($alumniEdit)
                        <div class="sf-group sf-col-span-full">
                            <label class="sf-label">Kegiatan Setelah Lulus (ringkasan otomatis)</label>
                            <input type="text" class="sf-input" value="{{ $edit->kegiatan_setelah_lulus ?: '' }}" readonly>
                            <span class="sf-hint">Ringkasan diperbarui dari data Pelacakan terakhir.</span>
                        </div>
                        <div class="sf-group">
                            <label class="sf-label" for="alumni-tracking-kind">Kegiatan</label>
                            <select id="alumni-tracking-kind" name="tracking_jenis" class="sf-select" @if($alumniTracking) required @endif>
                                <option value="">Pilih kegiatan</option>
                                @foreach(['Kuliah', 'Bekerja', 'Wirausaha', 'Mencari kerja', 'Lainnya'] as $trackingType)
                                    <option value="{{ $trackingType }}" @selected(old('tracking_jenis', $alumniTracking?->jenis) === $trackingType)>{{ $trackingType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sf-group">
                            <label class="sf-label" for="alumni-tracking-place">Kampus / instansi / perusahaan</label>
                            <input id="alumni-tracking-place" type="text" name="tracking_nama_tempat" class="sf-input" maxlength="150" value="{{ old('tracking_nama_tempat', $alumniTracking?->nama_tempat ?? '') }}" @if($alumniTracking) required @endif>
                        </div>
                        <div class="sf-group">
                            <label class="sf-label" for="alumni-tracking-year">Tahun</label>
                            <input id="alumni-tracking-year" type="number" name="tracking_tahun" class="sf-input" min="1900" max="2099" maxlength="4" value="{{ old('tracking_tahun', $alumniTracking?->tahun ?? '') }}" @if($alumniTracking) required @endif>
                        </div>
                        <div class="sf-group">
                            <label class="sf-label" for="alumni-tracking-note">Catatan</label>
                            <input id="alumni-tracking-note" type="text" name="tracking_keterangan" class="sf-input" maxlength="255" value="{{ old('tracking_keterangan', $alumniTracking?->keterangan ?? '') }}">
                        </div>
                    @else
                        <div class="sf-group">
                            <label class="sf-label">Melanjutkan Studi / Tempat Bekerja</label>
                            <input type="text" name="kegiatan_setelah_lulus" class="sf-input" value="{{ old('kegiatan_setelah_lulus', $edit->kegiatan_setelah_lulus ?? '') }}" placeholder="mis. Universitas Indonesia (Teknik Informatika) / Bekerja di PT...">
                            <span class="sf-hint">Informasi penelusuran tamatan / tracer alumni</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB I: CATATAN
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-i" class="tab-pane" style="display:none">
                <div class="sf-card">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">📝 Catatan Khusus Peserta Didik</h4>
                            <p class="sf-card-subtitle">Prestasi, kebutuhan konseling, atau catatan penting pembinaan</p>
                        </div>
                    </div>

                    <div class="sf-group">
                        <label class="sf-label">Catatan Bimbingan / Prestasi Siswa</label>
                        <textarea name="catatan" rows="6" class="sf-textarea" placeholder="Tuliskan catatan khusus terkait perkembangan atau prestasi peserta didik...">{{ old('catatan', $edit->catatan ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB KONTROL INDUK
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-ki" class="tab-pane" style="display:none">
                <div class="sf-card highlight">
                    <div class="sf-card-head">
                        <div>
                            <h4 class="sf-card-title">📋 Sinkronisasi Data Buku Kontrol Induk</h4>
                            <p class="sf-card-subtitle">Data registrasi induk, SKL, ijazah, dan transkrip kelulusan</p>
                        </div>
                        <span class="bd ok" style="padding:4px 12px;font-size:11.5px">Kontrol Induk</span>
                    </div>

                    <div class="sf-grid-2">
                        <div class="sf-group">
                            <label class="sf-label">Nomor Induk Sekolah (NIS)</label>
                            <input type="text" name="nomor_induk" class="sf-input sync-nis" value="{{ old('nomor_induk', $edit->nomor_induk ?? '') }}" placeholder="Nomor induk dari buku induk">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor SKL</label>
                            <input type="text" name="nomor_skl" class="sf-input sync-skl" value="{{ old('nomor_skl', $edit->nomor_skl ?? '') }}" placeholder="Nomor Surat Keterangan Lulus">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Ijazah Nasional SMA</label>
                            <input type="text" name="nomor_ijazah_sma" class="sf-input sync-ijazah" value="{{ old('nomor_ijazah_sma', $edit->nomor_ijazah_sma ?? '') }}" placeholder="Nomor Ijazah">
                        </div>

                        <div class="sf-group">
                            <label class="sf-label">Nomor Transkrip Nilai</label>
                            <input type="text" name="nomor_transkrip" class="sf-input sync-transkrip" value="{{ old('nomor_transkrip', $edit->nomor_transkrip ?? '') }}" placeholder="Nomor Transkrip">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 TAB LAMPIRAN BERKAS
                 ═══════════════════════════════════════════════════════ --}}
            <div id="tab-berkas" class="tab-pane" style="display:none">
                <div class="note" style="margin-top:0;margin-bottom:18px;background:#eef6ff;border-color:#bfdbfe;border-radius:12px">
                    <b>📁 Petunjuk Pengunggahan Berkas:</b> Format file yang didukung adalah PDF, JPG, JPEG, atau PNG (maksimal 5 MB per berkas). Berkas disimpan secara privat dan terlindungi, hanya dapat diunduh oleh akun staf resmi SMA 1 Rejoso.
                </div>

                <div class="sf-berkas-grid">
                    @foreach($lampiranBerkas as $jenisBerkas => [$labelBerkas, $statusField, $hintBerkas])
                        @php($berkasTersimpan = $edit?->berkas?->firstWhere('jenis', $jenisBerkas))
                        <div class="sf-berkas-card">
                            <div class="sf-berkas-title">{{ $labelBerkas }}</div>
                            <p class="sf-berkas-desc">{{ $hintBerkas }}</p>

                            <input
                                type="file"
                                id="berkas-{{ $jenisBerkas }}"
                                name="berkas[{{ $jenisBerkas }}]"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="sf-input"
                                style="font-size:12px;padding:6px 10px;margin-bottom:8px"
                            >

                            @if($berkasTersimpan)
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;padding:6px 10px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0">
                                    <span style="font-size:14px">✅</span>
                                    <span style="font-size:11.5px;color:#166534">
                                        Tersimpan: <a href="{{ route('admin.data-siswa.berkas', [$edit->id, $jenisBerkas]) }}" target="_blank" style="font-weight:700;color:#15803d;text-decoration:underline">{{ $berkasTersimpan->nama_file }}</a>
                                    </span>
                                </div>
                            @else
                                <p style="margin:0 0 10px;font-size:11.5px;color:#94a3b8">Belum ada dokumen yang diunggah.</p>
                            @endif

                            <label style="display:flex;gap:8px;align-items:center;cursor:pointer;font-size:12.5px;font-weight:600;color:#334155;user-select:none">
                                <input type="checkbox" name="{{ $statusField }}" value="1" {{ old($statusField, $edit->{$statusField} ?? 0) ? 'checked' : '' }} style="width:16px;height:16px;accent-color:#1967d2">
                                <span>Dokumen sudah diserahkan / diverifikasi</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Sticky Form Footer with Step Progress & Actions --}}
        <div class="student-form-foot">
            <div style="display:flex;align-items:center;gap:10px">
                <span id="sf-step-label" class="sf-step-indicator">
                    <span>Langkah 1 dari 11: A. Peserta Didik</span>
                </span>
                <button type="button" class="btn o" id="btn-prev" style="display:none;padding:7px 14px;font-size:12px">‹ Sebelumnya</button>
            </div>
            
            <div class="sf-foot-actions">
                <button type="button" class="btn o" id="btn-next" style="padding:8px 16px;font-size:12.5px">Berikutnya ›</button>
                <a href="{{ $cancelUrl }}" class="btn o" onclick="return closeStudentForm(event)" style="padding:8px 16px;font-size:12.5px">Batal</a>
                <button type="submit" class="btn" style="background:linear-gradient(135deg, #1967d2 0%, #05286b 100%);color:#ffffff;box-shadow:0 4px 14px rgba(25, 103, 210, 0.35);padding:8px 20px;font-size:12.5px">
                    {{ $edit ? '💾 Simpan Perubahan' : '💾 Simpan Data Siswa' }}
                </button>
            </div>
        </div>
    </form>
</aside>
</div>

@push('scripts')
<script>
function setupStudentFormValidation(form, panes, showTab) {
    form.addEventListener('submit', function(event) {
        var invalidField = Array.from(form.elements).find(function(field) {
            return !field.disabled
                && typeof field.checkValidity === 'function'
                && !field.checkValidity();
        });
        if (!invalidField) return;

        event.preventDefault();
        var invalidPane = invalidField.closest('.tab-pane');
        var invalidPaneIndex = Array.from(panes).indexOf(invalidPane);
        if (invalidPaneIndex >= 0) showTab(invalidPaneIndex);
        invalidField.reportValidity();
    });
}

function openStudentCreateModal() {
    var modal = document.getElementById('student-form-modal');
    if (!modal) return;

    var form = document.getElementById('form-pendataan-siswa');
    if (form && !@json($edit ? true : false)) {
        form.reset();
        form.querySelectorAll('.sf-photo-input').forEach(function(input) {
            var preview = document.getElementById(input.dataset.preview);
            var label = document.getElementById(input.dataset.label);
            if (preview) { preview.src = ''; preview.style.display = 'none'; }
            if (label) { label.style.display = 'block'; }
        });
    }

    if (typeof window.sfShowTab === 'function') {
        window.sfShowTab(0);
    }

    modal.classList.add('on');
    document.body.style.overflow = 'hidden';
}

function closeStudentForm(event, modalEl) {
    if (modalEl && event && event.target !== modalEl) return;
    if (event && event.preventDefault) event.preventDefault();

    var modal = document.getElementById('student-form-modal');
    if (!modal) return false;

    var isAlumniEdit = @json($alumniEdit);
    if (isAlumniEdit) {
        var referrer = document.referrer;
        if (referrer && new URL(referrer, window.location.href).origin === window.location.origin) {
            window.history.back();
            return false;
        }
    }

    modal.classList.remove('on');
    document.body.style.overflow = '';

    var closeUrl = @json($cancelUrl);
    if (window.location.search.indexOf('panel=form') !== -1) {
        window.history.replaceState({}, '', closeUrl);
    }

    return false;
}

(function() {
    var formPanel = document.getElementById('student-form-panel');
    if (!formPanel) return;

    var studentForm = document.getElementById('form-pendataan-siswa');
    var tabs = formPanel.querySelectorAll('.tab-btn');
    var panes = formPanel.querySelectorAll('.tab-pane');
    var btnPrev = document.getElementById('btn-prev');
    var btnNext = document.getElementById('btn-next');
    var stepLabel = document.getElementById('sf-step-label');
    var currentIndex = 0;

    var stepNames = [
        '1 dari 11: A. Peserta Didik',
        '2 dari 11: B. Tempat Tinggal',
        '3 dari 11: C. Kesehatan',
        '4 dari 11: D. Pendidikan Asal',
        '5 dari 11: E. Orang Tua',
        '6 dari 11: F. Wali',
        '7 dari 11: G. Mutasi Keluar',
        '8 dari 11: H. Pasca Lulus',
        '9 dari 11: I. Catatan',
        '10 dari 11: Kontrol Induk',
        '11 dari 11: Lampiran Berkas'
    ];

    function showTab(index) {
        if (index < 0 || index >= tabs.length) return;
        currentIndex = index;

        tabs.forEach(function(t, i) {
            if (i === currentIndex) {
                t.classList.add('on');
                t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                t.classList.remove('on');
            }
        });

        panes.forEach(function(p, i) {
            if (i === currentIndex) p.style.display = 'block';
            else p.style.display = 'none';
        });

        if (stepLabel && stepNames[currentIndex]) {
            stepLabel.textContent = 'Langkah ' + stepNames[currentIndex];
        }

        if (btnPrev) btnPrev.style.display = currentIndex > 0 ? 'inline-block' : 'none';
        if (btnNext) btnNext.style.display = currentIndex < tabs.length - 1 ? 'inline-block' : 'none';
    }

    window.sfShowTab = showTab;
    if (studentForm) setupStudentFormValidation(studentForm, panes, showTab);

    var failedFields = {!! json_encode(array_keys($errors->getMessages()), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    var failedInput = Array.from(formPanel.querySelectorAll('[name]')).find(function(input) {
        var fieldName = input.name.replace(/\[([^\]]+)\]/g, '.$1');
        return failedFields.includes(fieldName);
    });
    if (failedInput) {
        var failedPane = failedInput.closest('.tab-pane');
        if (failedPane) showTab(Array.from(panes).indexOf(failedPane));
    }

    tabs.forEach(function(t, i) {
        t.addEventListener('click', function() {
            showTab(i);
        });
    });

    btnPrev?.addEventListener('click', function() {
        showTab(currentIndex - 1);
    });

    btnNext?.addEventListener('click', function() {
        showTab(currentIndex + 1);
    });

    formPanel.querySelectorAll('.sf-photo-input').forEach(function(photoInput) {
        photoInput.addEventListener('change', function(event) {
            var file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('File harus berupa gambar (JPG/PNG/WEBP).');
                return;
            }

            var preview = document.getElementById(photoInput.dataset.preview);
            var label = document.getElementById(photoInput.dataset.label);
            var reader = new FileReader();
            reader.onload = function(loadEvent) {
                if (preview) {
                    preview.src = loadEvent.target.result;
                    preview.style.display = 'block';
                }
                if (label) label.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    });

    // Input Synchronization across tabs
    function syncFields(selector) {
        var inputs = document.querySelectorAll(selector);
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                var val = this.value;
                inputs.forEach(function(other) {
                    if (other !== input) other.value = val;
                });
            });
        });
    }

    syncFields('.sync-nis');
    syncFields('.sync-skl');
    syncFields('.sync-ijazah');
    syncFields('.sync-transkrip');

    // Esc key listener to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStudentForm(e);
        }
    });
})();
</script>
@endpush
