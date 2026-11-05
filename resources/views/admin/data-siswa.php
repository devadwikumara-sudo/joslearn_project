@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Admin')

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
                        <a href="{{ route('admin.data-siswa.show', $s->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat</a>
                        <a href="{{ route('admin.data-siswa.pdf', ['id' => $s->id, 'tahun_ajaran' => $tahunAjaranTerpilihId]) }}" class="btn a" style="padding:3px 8px;font-size:12px">⬇ PDF</a>
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
@endsection
