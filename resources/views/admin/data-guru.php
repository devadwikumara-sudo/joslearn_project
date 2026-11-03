@extends('layouts.app')

@section('title', 'Data Guru - JosBook Admin')

@section('content')
<h2>Data Guru</h2>
<p class="sub">Daftar tenaga pendidik dan staf sekolah</p>

<div class="card">
    <div class="bar">
        <input type="text" id="qGuru" placeholder="🔍 Cari nama / NIP…" style="min-width:220px" oninput="filterGuru(this.value)">
        <select id="filterMapel" style="min-width:160px" onchange="filterGuru(document.getElementById('qGuru').value)">
            <option value="">Semua Mata Pelajaran</option>
            @foreach($mapelList ?? [] as $mp)
                <option value="{{ $mp }}">{{ $mp }}</option>
            @endforeach
        </select>
        <span class="sp"></span>
        <button class="btn" onclick="document.getElementById('md-tambah-guru').style.display='grid'">+ Tambah Guru</button>
    </div>

    <div class="tw">
        <table id="tblGuru">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>NIP</th>
                    <th>Mata Pelajaran</th>
                    <th>No. HP</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guru ?? [] as $i => $g)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $g->nama_lengkap }}</b></td>
                    <td style="font-family:monospace">{{ $g->nip }}</td>
                    <td>{{ $g->mata_pelajaran }}</td>
                    <td>{{ $g->no_hp ?? '-' }}</td>
                    <td><span class="bd ok">Aktif</span></td>
                    <td>
                        <a href="{{ route('admin.data-guru.edit', $g->id) }}" class="btn o" style="padding:4px 10px;font-size:11px">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--mu);padding:24px">Belum ada data guru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah Guru --}}
<div id="md-tambah-guru" style="display:none;position:fixed;inset:0;background:#0b224088;z-index:9;place-items:center;padding:20px">
    <div class="mbox w2">
        <div class="bar"><h3 style="margin:0">Tambah Data Guru</h3><span class="sp"></span>
            <button class="btn x" onclick="document.getElementById('md-tambah-guru').style.display='none'">✕ Tutup</button>
        </div>
        <form method="POST" action="{{ route('admin.data-guru.store') }}">
            @csrf
            <div class="fl">
                <div><label>Nama Lengkap</label><input type="text" name="nama_lengkap" required></div>
                <div><label>NIP</label><input type="text" name="nip" placeholder="18 digit NIP"></div>
                <div><label>Mata Pelajaran</label><input type="text" name="mata_pelajaran"></div>
                <div><label>Tempat Lahir</label><input type="text" name="tempat_lahir"></div>
                <div><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir"></div>
                <div><label>No. HP</label><input type="text" name="no_hp" placeholder="08xxx"></div>
                <div><label>Email</label><input type="email" name="email"></div>
                <div><label>Golongan</label>
                    <select name="golongan">
                        <option>III/a</option><option>III/b</option><option>III/c</option><option>III/d</option>
                        <option>IV/a</option><option>IV/b</option><option>IV/c</option>
                    </select>
                </div>
                <div><label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn o" onclick="document.getElementById('md-tambah-guru').style.display='none'">Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterGuru(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tblGuru tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush

