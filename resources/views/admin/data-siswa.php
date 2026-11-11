@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Admin')

@push('styles')
<style>
    .student-detail-modal{position:fixed;inset:0;z-index:1000;display:none;place-items:center;padding:20px;background:rgba(9,24,43,.62);backdrop-filter:blur(3px)}
    .student-detail-modal.on{display:grid}
    .student-detail-box{display:flex;flex-direction:column;width:min(1040px,100%);max-height:92vh;overflow:hidden;border:1px solid #dce5ef;border-radius:20px;background:#f5f8fc;box-shadow:0 24px 80px #06172f55}
    .student-detail-head{display:flex;align-items:center;gap:14px;padding:18px 22px;background:#fff;border-bottom:1px solid #dce5ef}
    .student-detail-head h3{margin:0;color:#12355b;font-size:17px}
    .student-detail-head p{margin:3px 0 0;color:#64748b;font-size:12px}
    .student-detail-close{margin-left:auto;width:36px;height:36px;border:1px solid #dce5ef;border-radius:50%;background:#fff;color:#12355b;font-size:20px;cursor:pointer}
    .student-detail-body{overflow:auto;padding:20px}
    .student-detail-foot{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid #dce5ef;background:#fff}
    .student-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
    .student-filters,.student-page-actions,.student-table-actions,.student-row-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .student-page-actions{margin-left:auto;justify-content:flex-end}
    .student-table-actions{margin-left:auto}
    .student-row-actions{flex-wrap:nowrap;white-space:nowrap}
    .student-year-filter{position:relative;font-size:12px}
    .student-year-filter summary{cursor:pointer;color:var(--mu);white-space:nowrap}
    .student-year-filter form{position:absolute;z-index:5;top:24px;left:0;display:grid;gap:8px;min-width:190px;padding:10px;border:1px solid var(--ln);border-radius:8px;background:#fff;box-shadow:0 6px 18px #0b224020}
    .bulk-move-copy{margin:0;color:#64748b;font-size:12px}
    @media(max-width:600px){.student-detail-modal{padding:8px}.student-detail-box{max-height:96vh;border-radius:14px}.student-detail-head{padding:14px}.student-detail-body{padding:12px}.student-detail-foot{padding:12px}}
    @media(max-width:700px){.student-page-actions,.student-table-actions{margin-left:0;justify-content:flex-start}.student-toolbar{align-items:flex-start}}
</style>
@endpush

@section('content')
<h2>Data Siswa</h2>
@if(session('success'))
    <div class="note ok" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="note err" role="alert">{{ session('error') }}</div>
@endif
@if(session('bulk_move_results'))
    <div class="card" style="margin-top:12px" aria-label="Hasil pindah kelas">
        <b>Hasil pindah kelas per siswa</b>
        <ul style="margin:8px 0 0;padding-left:22px">
            @foreach(session('bulk_move_results') as $result)
                <li>
                    <span class="bd {{ $result['berhasil'] ? 'ok' : 'e' }}">{{ $result['berhasil'] ? 'Berhasil' : 'Ditolak' }}</span>
                    <b>{{ $result['nama'] }}</b>: {{ $result['pesan'] }}
                </li>
            @endforeach
        </ul>
    </div>
@endif
<p class="sub">
    @if(count($kelasList) === 0)
        Belum ada kelas master; biodata tanpa riwayat kelas ditampilkan sebagai belum ditetapkan.
    @else
        Menampilkan seluruh siswa tahun ajaran terpilih. Pilih kelas atau gunakan pencarian untuk menyaring daftar.
    @endif
</p>

{{-- Filter and actions follow the reference layout. --}}
<div class="student-toolbar">
    <form method="GET" action="{{ route('admin.data-siswa') }}" class="student-filters">
        <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
        <select name="kelas">
            <option value="">— Pilih kelas —</option>
            <option value="*" {{ $kelasSelected === '*' ? 'selected' : '' }}>Semua kelas (gabungan)</option>
            @foreach($kelasList as $k)
                <option value="{{ $k }}" {{ $kelasSelected === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>

        <input type="text" name="q" placeholder="Cari nama / NISN" value="{{ $search }}" style="width:160px">

        <select name="st">
            <option value="">Semua status</option>
            @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

    </form>

    <details class="student-year-filter">
        <summary>Tahun: {{ $taAktif }}</summary>
        <form method="GET" action="{{ route('admin.data-siswa') }}">
            <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
            <input type="hidden" name="q" value="{{ $search }}">
            <input type="hidden" name="st" value="{{ $statusFilter }}">
            <select name="tahun_ajaran" aria-label="Tahun ajaran" onchange="this.form.submit()">
                <option value="*" {{ $tahunAjaranTerpilihId === '*' ? 'selected' : '' }}>Semua tahun ajaran</option>
                @foreach($tahunAjaranList as $tahunAjaran)
                    <option value="{{ $tahunAjaran->id }}" {{ (string) $tahunAjaranTerpilihId === (string) $tahunAjaran->id ? 'selected' : '' }}>{{ $tahunAjaran->nama }}</option>
                @endforeach
            </select>
        </form>
    </details>

    <div class="student-page-actions">
        <a href="{{ route('admin.data-siswa.export', ['tahun_ajaran' => $tahunAjaranTerpilihId]) }}" class="btn o">Ekspor Excel</a>
        <a href="{{ route('admin.import.siswa') }}" class="btn o">Import Excel</a>
        <a href="{{ route('admin.form-pendataan') }}" class="btn">+ Tambah siswa</a>
        @if($kelasSelected === '' || $kelasSelected === '*')
            <button type="button" class="btn x" disabled title="Pilih satu kelas terlebih dahulu">Hapus semua kelas ini</button>
        @endif
        @if($taAktifDefaultId)
            <form method="POST" action="{{ route('admin.data-siswa.bulk-delete') }}" style="margin:0" onsubmit="return confirm('Semua profil siswa yang memiliki riwayat pada tahun ajaran berjalan akan dipindahkan ke Tempat Sampah. Riwayat tahun lain pada profil yang sama ikut terhapus. Lanjutkan?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="tahun_ajaran" value="{{ $taAktifDefaultId }}">
                <input type="hidden" name="kelas" value="*">
                <input type="hidden" name="konfirmasi" value="hapus">
                <button type="submit" class="btn x">Hapus semua data tahun ini</button>
            </form>
        @endif
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="bar">
        <b>{{ $siswaList ? $siswaList->total() : 0 }} siswa</b>
        <div class="student-table-actions">
            <button class="btn o" onclick="window.print()">PDF terpilih</button>
            <button class="btn o" onclick="window.print()">PDF semua tampil</button>
            <button type="button" class="btn o" onclick="openBulkMovePanel()">Pindah kelas</button>
            <button type="button" id="delete-selected-button" class="btn x" onclick="deleteSelectedStudents()" disabled>Hapus terpilih</button>
            @if($kelasSelected !== '' && $kelasSelected !== '*')
                <form method="POST" action="{{ route('admin.data-siswa.bulk-delete') }}" style="margin:0" onsubmit="return confirm('Semua siswa kelas {{ $kelasSelected }} pada {{ $tahunAjaranTerpilihId === '*' ? 'semua tahun ajaran' : 'tahun ajaran yang dipilih' }} akan dipindahkan ke Tempat Sampah. Seluruh riwayat pada profil siswa ikut terhapus. Lanjutkan?')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
                    <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
                    <input type="hidden" name="konfirmasi" value="hapus">
                    <button type="submit" class="btn x">Hapus semua kelas ini</button>
                </form>
            @endif
        </div>
    </div>

    <form id="delete-selected-form" method="POST" action="{{ route('admin.data-siswa.bulk-delete') }}" style="display:none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
        <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
        <input type="hidden" name="st" value="{{ $statusFilter }}">
        <input type="hidden" name="q" value="{{ $search }}">
        <input type="hidden" name="konfirmasi" value="hapus">
    </form>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all" aria-label="Pilih semua siswa di halaman"></th>
                    <th>NISN</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>JK</th>
                    <th>TTL</th>
                    <th>Agama</th>
                    <th>Orang tua</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswaList as $s)
                @php
                    $rk = $s->riwayatKelas->first();
                    $kelasNama = $rk?->kelas?->nama ?? 'Belum ditetapkan';
                    $statusNama = $rk?->status ?? 'Belum diatur';
                    $badgeClass = in_array($statusNama, ['Aktif', 'Lulus'], true)
                        ? 'ok'
                        : (in_array($statusNama, ['Pindah keluar', 'Belum diatur'], true) ? 'w' : 'e');
                @endphp
                <tr>
                    <td><input type="checkbox" class="check-item" value="{{ $s->id }}" aria-label="Pilih {{ $s->nama_lengkap }}"></td>
                    <td><code>{{ $s->nisn ?? '-' }}</code></td>
                    <td>{{ $s->nomor_induk ?? '-' }}</td>
                    <td><b>{{ $s->nama_lengkap ?? '-' }}</b></td>
                    <td>{{ $kelasNama }}</td>
                    <td>{{ $s->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $s->tempat_lahir ?? '-' }}, {{ $s->tanggal_lahir ? \Carbon\Carbon::parse($s->tanggal_lahir)->translatedFormat('d M Y') : '-' }}</td>
                    <td>{{ $s->agama ?? '-' }}</td>
                    <td>{{ $s->nama_ayah ?? $s->nama_ibu ?? '-' }}</td>
                    <td><span class="bd {{ $badgeClass }}">{{ $statusNama }}</span></td>
                    <td style="white-space:nowrap">
                        <button
                            type="button"
                            data-pdf-url="{{ route('admin.data-siswa.pdf', $s->id) }}"
                            data-edit-url="{{ route('admin.form-pendataan.edit', $s->id) }}"
                            class="btn o"
                            style="padding:3px 8px;font-size:12px"
                            aria-haspopup="dialog"
                            onclick="openStudentDetail(this)"
                        >Lihat</button>
                        <template class="student-detail-template">
                            @include('admin.siswa-detail-content', [
                                'siswa' => $s,
                                'includeGraduationDetails' => true,
                                'canManageAcademicRecords' => true,
                                'academicMapelOptions' => $academicMapelOptions,
                            ])
                        </template>
                        <a href="{{ route('admin.data-siswa.pdf', $s->id) }}" class="btn a" style="padding:3px 8px;font-size:12px">⬇ PDF</a>
                        <a href="{{ route('admin.form-pendataan.edit', $s->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">Edit</a>
                        <button type="button" class="btn o" style="padding:3px 8px;font-size:12px" title="Pindah kelas" aria-label="Pindah kelas {{ $s->nama_lengkap }}" onclick="openBulkMovePanel([{{ $s->id }}])">⇄</button>
                        <form action="{{ route('admin.data-siswa.destroy', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Pindahkan data {{ $s->nama_lengkap }} ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x" style="padding:3px 8px;font-size:12px">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align:center;padding:28px;color:var(--mu)">
                        Tidak ada data siswa ditemukan untuk kriteria ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($siswaList && $siswaList->total() > 0)
    <div id="bulk-move-modal" class="student-detail-modal" role="dialog" aria-modal="true" aria-labelledby="bulk-move-title" onclick="closeBulkMovePanel(event, this)">
        <div class="student-detail-box" style="width:min(520px,100%);border-radius:12px" onclick="event.stopPropagation()">
            <div class="student-detail-head">
                <div>
                    <p>Pilih penempatan tujuan untuk siswa yang dipilih.</p>
                </div>
                <button type="button" class="student-detail-close" aria-label="Tutup" onclick="closeBulkMovePanel(null, document.getElementById('bulk-move-modal'))">&times;</button>
            </div>
            <div class="student-detail-body">
                @if($tahunAjaranTerpilihId === '*' || !$tahunAjaranTerpilihId)
                    <p class="note">Pilih satu tahun ajaran pada filter sebelum memindahkan siswa.</p>
                @else
                    <p class="bulk-move-copy" style="margin-bottom:14px">Kelas siswa pada tahun ajaran terpilih akan diubah. Pilih kotak di tabel untuk memindahkan siswa tertentu; tanpa pilihan, tombol sekunder memindahkan seluruh hasil filter.</p>
                    <form id="bulk-update-form" method="POST" action="{{ route('admin.data-siswa.bulk-update') }}" onsubmit="return confirmBulkUpdate(this, {{ $siswaList->total() }})">
                        @csrf
                        <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
                        <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
                        <input type="hidden" name="st" value="{{ $statusFilter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                        <div id="selected-move-ids"></div>
                        <div style="display:grid;gap:12px">
                            <label>Kelas tujuan
                                <select name="target_kelas" style="width:100%" required>
                                    <option value="">Pilih kelas</option>
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k }}">{{ $k }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Status tujuan
                                <select name="target_status" style="width:100%">
                                    <option value="">Pertahankan status masing-masing</option>
                                    @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                                        <option value="{{ $st }}">{{ $st }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </form>
                @endif
            </div>
            <div class="student-detail-foot">
                <button type="button" class="btn o" onclick="closeBulkMovePanel(null, document.getElementById('bulk-move-modal'))">Batal</button>
                @if($tahunAjaranTerpilihId !== '*' && $tahunAjaranTerpilihId)
                    <button type="button" class="btn o" onclick="moveFilteredStudents()">Pindah hasil filter</button>
                    <button type="button" class="btn a" onclick="moveSelectedStudents()">Pindah terpilih</button>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($siswaList && $siswaList->hasPages())
    <div style="margin-top:14px">
        {{ $siswaList->links() }}
    </div>
    @endif
</div>

<div id="student-detail-modal" class="student-detail-modal" role="dialog" aria-modal="true" aria-labelledby="student-detail-title" onclick="closeStudentDetail(event, this)">
    <div class="student-detail-box">
        <div class="student-detail-head">
            <div>
                <h3 id="student-detail-title">Detail Lengkap Siswa</h3>
                <p>Biodata, keluarga, riwayat kelas, dan dokumen</p>
            </div>
            <button type="button" class="student-detail-close" aria-label="Tutup" onclick="closeStudentDetail(null, document.getElementById('student-detail-modal'))">&times;</button>
        </div>
        <div id="student-detail-content" class="student-detail-body" aria-live="polite">
            <p class="sub">Pilih siswa untuk melihat detailnya.</p>
        </div>
        <div class="student-detail-foot">
            <a id="student-detail-pdf" class="btn o" target="_blank" rel="noopener">Cetak PDF</a>
            <a id="student-detail-edit" class="btn a">Edit biodata</a>
            <button type="button" class="btn o" onclick="closeStudentDetail(null, document.getElementById('student-detail-modal'))">Tutup</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openStudentDetail(button) {
    const modal = document.getElementById('student-detail-modal');
    const content = document.getElementById('student-detail-content');
    content.replaceChildren(button.parentElement.querySelector('.student-detail-template').content.cloneNode(true));
    document.getElementById('student-detail-pdf').href = button.dataset.pdfUrl;
    document.getElementById('student-detail-edit').href = button.dataset.editUrl;
    modal.classList.add('on');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.student-detail-close').focus();
}

function closeStudentDetail(event, modal) {
    if (!modal || (event && event.target !== modal)) return;
    modal.classList.remove('on');
    document.getElementById('student-detail-content').replaceChildren();
    if (!document.querySelector('.student-detail-modal.on, .alumni-modal.on')) {
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeStudentDetail(null, document.getElementById('student-detail-modal'));
        closeBulkMovePanel(null, document.getElementById('bulk-move-modal'));
    }
});

document.getElementById('check-all')?.addEventListener('change', function() {
    var checked = this.checked;
    document.querySelectorAll('.check-item').forEach(function(c) {
        c.checked = checked;
    });
    updateSelectedActions();
});

document.querySelectorAll('.check-item').forEach(function(checkbox) {
    checkbox.addEventListener('change', updateSelectedActions);
});

function selectedStudentIds() {
    return Array.from(document.querySelectorAll('.check-item:checked')).map(function(checkbox) {
        return checkbox.value;
    });
}

function updateSelectedActions() {
    const hasSelectedStudents = selectedStudentIds().length > 0;
    const deleteButton = document.getElementById('delete-selected-button');
    if (deleteButton) deleteButton.disabled = !hasSelectedStudents;
}

function openBulkMovePanel(studentIds = null) {
    if (studentIds) {
        document.querySelectorAll('.check-item').forEach(function(checkbox) {
            checkbox.checked = studentIds.includes(checkbox.value);
        });
        updateSelectedActions();
    }

    const modal = document.getElementById('bulk-move-modal');
    if (!modal) return;
    modal.classList.add('on');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.student-detail-close')?.focus();
}

function closeBulkMovePanel(event, modal) {
    if (!modal || (event && event.target !== modal)) return;
    modal.classList.remove('on');
    if (!document.querySelector('.student-detail-modal.on, .alumni-modal.on')) {
        document.body.style.overflow = '';
    }
}

function deleteSelectedStudents() {
    const ids = selectedStudentIds();
    if (!ids.length || !confirm('Pindahkan ' + ids.length + ' data siswa terpilih ke Tempat Sampah?')) return;

    const form = document.getElementById('delete-selected-form');
    ids.forEach(function(id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'siswa_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    form.submit();
}

function moveSelectedStudents() {
    const ids = selectedStudentIds();
    if (!ids.length) {
        alert('Pilih minimal satu siswa terlebih dahulu.');
        return;
    }

    const form = document.getElementById('bulk-update-form');
    const container = document.getElementById('selected-move-ids');
    container.replaceChildren();
    ids.forEach(function(id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'siswa_ids[]';
        input.value = id;
        container.appendChild(input);
    });
    form.requestSubmit();
}

function moveFilteredStudents() {
    const container = document.getElementById('selected-move-ids');
    container.replaceChildren();
    document.getElementById('bulk-update-form').requestSubmit();
}

function confirmBulkUpdate(form, total) {
    const selectedCount = form.querySelectorAll('input[name="siswa_ids[]"]').length;
    const message = selectedCount
        ? 'Pindahkan ' + selectedCount + ' siswa terpilih ke penempatan tujuan?'
        : 'Terapkan penempatan ke semua ' + total + ' siswa pada filter ini?';
    return confirm(message);
}
</script>
@endpush
@endsection
