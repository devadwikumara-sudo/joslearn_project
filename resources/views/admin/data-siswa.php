@extends('layouts.app')

@section('title', 'Data Siswa - JosBook Admin')

@section('content')
<h2>Data Siswa</h2>
<p class="sub">Daftar seluruh peserta didik aktif · Klik baris untuk detail</p>

<div class="card">
    <div class="bar">
        <select name="kelas" onchange="this.form && this.form.submit()" style="min-width:140px">
            <option value="">Semua Kelas</option>
            @foreach($kelasList ?? [] as $k)
                <option value="{{ $k }}" {{ request('kelas') === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>
        <select name="angkatan" style="min-width:120px">
            <option value="">Semua Angkatan</option>
            @foreach($angkatanList ?? [] as $a)
                <option value="{{ $a }}" {{ request('angkatan') == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <input type="text" id="qSiswa" placeholder="🔍 Cari nama / NISN…" style="min-width:200px" oninput="filterSiswa(this.value)">
        <span class="sp"></span>
        <a href="{{ route('admin.import.nilai') }}" class="btn o">⬆ Import Data</a>
        <button class="btn" onclick="document.getElementById('md-tambah').classList.add('on')">+ Tambah Siswa</button>
    </div>

    <div class="tw">
        <table id="tblSiswa">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>NISN</th>
                    <th>Kelas</th>
                    <th>Angkatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa ?? [] as $i => $s)
                <tr class="cc" onclick="bukaDetail('{{ $s->id }}')">
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $s->nama_lengkap }}</b></td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->kelas }}</td>
                    <td>{{ $s->angkatan }}</td>
                    <td>
                        <span class="bd {{ $s->status === 'aktif' ? 'ok' : ($s->status === 'mutasi' ? 'w' : 'e') }}">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="#" class="btn o" style="padding:4px 10px;font-size:11px"
                           onclick="bukaDetail('{{ $s->id }}'); return false">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--mu);padding:24px">Belum ada data siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(isset($siswa) && method_exists($siswa, 'links'))
    <div class="pgn">{{ $siswa->links() }}</div>
    @endif
</div>

{{-- Modal Tambah Siswa --}}
<div id="md-tambah" class="on" style="display:none;position:fixed;inset:0;background:#0b224088;z-index:9;display:none;place-items:center;padding:20px">
    <div class="mbox w2">
        <div class="bar"><h3 style="margin:0">Tambah Siswa Baru</h3><span class="sp"></span>
            <button class="btn x" onclick="document.getElementById('md-tambah').style.display='none'">✕ Tutup</button>
        </div>
        <form method="POST" action="{{ route('admin.data-siswa.store') }}">
            @csrf
            <div class="fl">
                <div><label>Nama Lengkap</label><input type="text" name="nama_lengkap" required placeholder="Nama sesuai ijazah"></div>
                <div><label>NISN</label><input type="text" name="nisn" placeholder="10 digit NISN"></div>
                <div><label>NIS</label><input type="text" name="nis" placeholder="NIS sekolah"></div>
                <div><label>Tempat Lahir</label><input type="text" name="tempat_lahir"></div>
                <div><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir"></div>
                <div><label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div><label>Kelas</label><input type="text" name="kelas" placeholder="X IPA 1"></div>
                <div><label>Angkatan</label><input type="number" name="angkatan" placeholder="2024"></div>
                <div><label>Agama</label>
                    <select name="agama">
                        <option>Islam</option><option>Kristen</option><option>Katolik</option>
                        <option>Hindu</option><option>Budha</option><option>Konghucu</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn o" onclick="document.getElementById('md-tambah').style.display='none'">Batal</button>
                <button type="submit" class="btn">Simpan Siswa</button>
            </div>
        </form>
    </div>
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
function bukaDetail(id) {
    window.location.href = '/admin/data-siswa/' + id;
}
</script>
@endpush

