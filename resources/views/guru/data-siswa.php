@extends('layouts.app')

@section('title', 'Data Siswa - JosBook Guru')

@section('content')
<h2>Data Siswa</h2>
<p class="sub">Daftar siswa pada kelas yang Anda ampu (hanya untuk referensi)</p>

<div class="card">
    <div class="bar">
        <select name="kelas" style="min-width:160px" onchange="window.location.href='?kelas='+this.value">
            <option value="">Semua Kelas</option>
            @foreach($kelasList ?? [] as $k)
                <option value="{{ $k }}" {{ request('kelas') === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>
        <input type="text" id="qSiswa" placeholder="🔍 Cari nama / NISN…" style="min-width:200px" oninput="filterSiswa(this.value)">
        <span class="sp"></span>
        <span style="font-size:12px;color:var(--mu)">{{ isset($siswa) ? $siswa->total() : 0 }} siswa ditemukan</span>
    </div>

    <div class="tw">
        <table id="tblSiswa">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>NISN</th>
                    <th>Kelas</th>
                    <th>Jenis Kelamin</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa ?? [] as $i => $s)
                <tr>
                    <td>{{ ($siswa->currentPage() - 1) * $siswa->perPage() + $i + 1 }}</td>
                    <td><b>{{ $s->nama_lengkap }}</b></td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->kelas }}</td>
                    <td>{{ $s->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    <td>
                        <a href="{{ route('guru.data-siswa.show', $s->id) }}" class="btn o" style="padding:4px 10px;font-size:11px">Lihat Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--mu);padding:24px">Belum ada data siswa pada kelas ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($siswa) && method_exists($siswa, 'links'))
    <div class="pgn">{{ $siswa->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function filterSiswa(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tblSiswa tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush

