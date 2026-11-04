@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Admin')

@section('content')
<h2>Data Siswa</h2>
<p class="sub">
    @if(count($kelasList) === 0)
        Belum ada kelas master; biodata tanpa riwayat kelas ditampilkan sebagai belum ditetapkan.
    @else
        Pilih kelas dulu agar data tidak tampil sekaligus.
    @endif
</p>

{{-- Toolbar Sesuai Prototype (siswaPg) --}}
<div class="bar">
    <form method="GET" action="{{ route('admin.data-siswa') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <select name="kelas" onchange="this.form.submit()">
            <option value="">— Pilih kelas —</option>
            <option value="*" {{ $kelasSelected === '*' ? 'selected' : '' }}>Semua kelas (gabungan)</option>
            @foreach($kelasList as $k)
                <option value="{{ $k }}" {{ $kelasSelected === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>

        <input type="text" name="q" placeholder="Cari nama / NISN / NIS" value="{{ $search }}" style="width:200px">

        <select name="st" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $st)
                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn" style="padding:6px 12px">Cari</button>
    </form>

    <span class="sp"></span>

    <a href="{{ route('admin.data-siswa.export') }}" class="btn o">⬇ Ekspor Excel</a>
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
        <a href="{{ route('admin.data-siswa', ['kelas' => $k]) }}" class="card cc" style="text-decoration:none;color:inherit;cursor:pointer">
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
        @if($kelasSelected && $kelasSelected !== '*')
            <span class="bd ok">{{ $kelasSelected }}</span>
        @endif
        <span class="sp"></span>
        <button class="btn o" onclick="window.print()">⬇ PDF terpilih</button>
        <button class="btn o" onclick="window.print()">⬇ PDF semua tampil</button>
        <button class="btn o" onclick="toast('Fitur pindah kelas terpilih')">⇄ Pindah kelas</button>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="check-all"></th>
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
                    <td><input type="checkbox" class="check-item" value="{{ $s->id }}"></td>
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
                    <td colspan="11" style="text-align:center;padding:28px;color:var(--mu)">
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

@push('scripts')
<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    var checked = this.checked;
    document.querySelectorAll('.check-item').forEach(function(c) {
        c.checked = checked;
    });
});
</script>
@endpush
@endsection
