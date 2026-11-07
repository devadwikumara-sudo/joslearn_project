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
    @media(max-width:600px){.student-detail-modal{padding:8px}.student-detail-box{max-height:96vh;border-radius:14px}.student-detail-head{padding:14px}.student-detail-body{padding:12px}.student-detail-foot{padding:12px}}
</style>
@endpush

@section('content')
<h2>Data Siswa</h2>
<p class="sub">
    @if(count($kelasList) === 0)
        Belum ada kelas master; biodata tanpa riwayat kelas ditampilkan sebagai belum ditetapkan.
    @else
        Pilih tahun ajaran dan kelas untuk melihat data. Aksi massal berlaku untuk seluruh hasil filter, termasuk halaman berikutnya.
    @endif
</p>

{{-- Toolbar Sesuai Prototype (siswaPg) --}}
<div class="bar">
    <form method="GET" action="{{ route('admin.data-siswa') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <select name="tahun_ajaran">
            <option value="*" {{ $tahunAjaranTerpilihId === '*' ? 'selected' : '' }}>Semua tahun ajaran</option>
            @foreach($tahunAjaranList as $tahunAjaran)
                <option value="{{ $tahunAjaran->id }}" {{ (string) $tahunAjaranTerpilihId === (string) $tahunAjaran->id ? 'selected' : '' }}>{{ $tahunAjaran->nama }}</option>
            @endforeach
        </select>

        <select name="kelas">
            <option value="">— Pilih kelas —</option>
            <option value="*" {{ $kelasSelected === '*' ? 'selected' : '' }}>Semua kelas (gabungan)</option>
            @foreach($kelasList as $k)
                <option value="{{ $k }}" {{ $kelasSelected === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>

        <input type="text" name="q" placeholder="Cari nama / NISN / NIS" value="{{ $search }}" style="width:200px">

        <select name="st">
            <option value="">Semua status</option>
            @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn" style="padding:6px 12px">Cari</button>
    </form>

    <span class="sp"></span>

    <a href="{{ route('admin.data-siswa.pdf-all', request()->query()) }}" class="btn o">⬇ PDF seluruh hasil filter</a>
    <a href="{{ route('admin.data-siswa.export', ['tahun_ajaran' => $tahunAjaranTerpilihId]) }}" class="btn o">⬇ Ekspor Excel</a>
    <a href="{{ route('admin.import.siswa') }}" class="btn a">⬆ Import Excel</a>
    <a href="{{ route('admin.form-pendataan') }}" class="btn">+ Tambah siswa</a>
</div>

{{-- JIKA BELUM PILIH KELAS & TIDAK SEDANG SEARCH: Tampilkan Grid Card Kelas (Prototype baris 330) --}}
@if(!$kelasSelected && !$search && !$statusFilter && count($kelasList) > 0)
<div class="g g4" style="margin-top:14px">
    @forelse($kelasList as $k)
        @php
            $count = $kelasCounts[$k] ?? 0;
        @endphp
        <a href="{{ route('admin.data-siswa', ['kelas' => $k, 'tahun_ajaran' => $tahunAjaranTerpilihId]) }}" class="card cc" style="text-decoration:none;color:inherit;cursor:pointer">
            <h3 style="font-size:16px">{{ $k }}</h3>
            <div class="st">
                <b>{{ $count }}</b>
                <span>siswa</span>
            </div>
        </a>
    @empty
        <div class="card" style="grid-column:1/-1">
            <p class="sub">Belum ada kelas di master kelas.</p>
        </div>
    @endforelse
</div>

{{-- JIKA KELAS SUDAH DIPILIH: Tampilkan Tabel Siswa (Prototype baris 331) --}}
@else
<div class="card" style="margin-top:14px">
    <div class="bar">
        <b>{{ $siswaList ? $siswaList->total() : 0 }} siswa</b>
        <span class="bd">{{ $taAktif }}</span>
        @if($kelasSelected && $kelasSelected !== '*')
            <span class="bd ok">{{ $kelasSelected }}</span>
        @endif
        <span class="sp"></span>
        <button class="btn o" onclick="window.print()">⬇ PDF terpilih</button>
        <button class="btn o" onclick="window.print()">⬇ PDF semua tampil</button>
        <button class="btn o" onclick="toast('Fitur pindah kelas terpilih')">⇄ Pindah kelas</button>
    </div>

    @if($siswaList && $siswaList->total() > 0)
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;margin:12px 0">
        <details class="card" style="flex:1;min-width:320px;margin:0;padding:12px">
            <summary style="cursor:pointer;font-weight:600">✏️ Edit semua hasil filter</summary>
            @if($tahunAjaranTerpilihId === '*' || !$tahunAjaranTerpilihId)
                <p class="sub">Untuk mengedit penempatan, pilih satu tahun ajaran pada filter di atas terlebih dahulu.</p>
            @else
                <p class="sub">Mengubah penempatan semua {{ $siswaList->total() }} siswa yang cocok dengan filter. Tahun sebelumnya tetap tersimpan sebagai riwayat.</p>
                <form method="POST" action="{{ route('admin.data-siswa.bulk-update') }}" onsubmit="return confirm('Terapkan penempatan ke semua {{ $siswaList->total() }} siswa pada filter ini?')">
                    @csrf
                    <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
                    <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
                    <input type="hidden" name="st" value="{{ $statusFilter }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <div class="bar" style="flex-wrap:wrap;gap:8px">
                        <select name="target_tahun_ajaran" required>
                            <option value="">Tahun ajaran tujuan</option>
                            @foreach($tahunAjaranList as $tahunAjaran)
                                <option value="{{ $tahunAjaran->id }}" {{ (string) ($tahunAjaranTerpilihId === '*' ? $taAktifDefaultId : $tahunAjaranTerpilihId) === (string) $tahunAjaran->id ? 'selected' : '' }}>{{ $tahunAjaran->nama }}</option>
                            @endforeach
                        </select>
                        <select name="target_kelas" required>
                            <option value="">Kelas tujuan</option>
                            @foreach($kelasList as $k)
                                <option value="{{ $k }}">{{ $k }}</option>
                            @endforeach
                        </select>
                        <select name="target_status">
                            <option value="">Pertahankan status masing-masing</option>
                            @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn a">Simpan edit massal</button>
                    </div>
                </form>
            @endif
        </details>

        <form method="POST" action="{{ route('admin.data-siswa.bulk-delete') }}" style="margin:0" onsubmit="return confirm('Pindahkan SEMUA {{ $siswaList->total() }} data siswa sesuai filter ini ke Tempat Sampah? Data profil akan ikut dihapus, tidak hanya riwayat tahun ajaran yang dipilih.')">
            @csrf
            @method('DELETE')
            <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaranTerpilihId }}">
            <input type="hidden" name="kelas" value="{{ $kelasSelected }}">
            <input type="hidden" name="st" value="{{ $statusFilter }}">
            <input type="hidden" name="q" value="{{ $search }}">
            <input type="hidden" name="konfirmasi" value="hapus">
            <button type="submit" class="btn x">🗑 Hapus semua hasil filter ({{ $siswaList->total() }})</button>
        </form>
    </div>
    @endif

    <div class="tw">
        <table>
            <thead>
                <tr>
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
                            @include('admin.siswa-detail-content', ['siswa' => $s, 'includeGraduationDetails' => true])
                        </template>
                        <a href="{{ route('admin.data-siswa.pdf', $s->id) }}" class="btn a" style="padding:3px 8px;font-size:12px">⬇ PDF</a>
                        <a href="{{ route('admin.form-pendataan.edit', $s->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">Edit</a>
                        <form action="{{ route('admin.data-siswa.destroy', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Pindahkan data {{ $s->nama_lengkap }} ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x" style="padding:3px 8px;font-size:12px">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:28px;color:var(--mu)">
                        Tidak ada data siswa ditemukan untuk kriteria ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($siswaList && $siswaList->hasPages())
    <div style="margin-top:14px">
        {{ $siswaList->links() }}
    </div>
    @endif
</div>
@endif

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
    }
});

document.getElementById('check-all')?.addEventListener('change', function() {
    var checked = this.checked;
    document.querySelectorAll('.check-item').forEach(function(c) {
        c.checked = checked;
    });
});
</script>
@endpush
@endsection
