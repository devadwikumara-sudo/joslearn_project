@extends('layouts.app')

@section('title', 'Mutasi Siswa - JosBook Admin')

@section('content')
<h2>Mutasi Siswa</h2>
<p class="sub">Kelola perpindahan siswa masuk dan keluar sekolah</p>

<div class="g g2" style="margin-bottom:14px">
    <div class="card st">
        <span>Mutasi Masuk</span>
        <b style="color:var(--ok)">{{ $mutasiMasuk ?? 0 }}</b>
    </div>
    <div class="card st">
        <span>Mutasi Keluar</span>
        <b style="color:var(--er)">{{ $mutasiKeluar ?? 0 }}</b>
    </div>
</div>

<div class="card">
    <div class="bar">
        <div class="seg">
            <span class="{{ request('tab', 'masuk') === 'masuk' ? 'on' : '' }}" onclick="gantiTab('masuk')">Mutasi Masuk</span>
            <span class="{{ request('tab') === 'keluar' ? 'on' : '' }}" onclick="gantiTab('keluar')">Mutasi Keluar</span>
        </div>
        <span class="sp"></span>
        <button class="btn" onclick="document.getElementById('md-mutasi').style.display='grid'">+ Catat Mutasi</button>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>NISN</th>
                    <th>Dari / Ke Sekolah</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutasi ?? [] as $m)
                <tr>
                    <td><b>{{ $m->nama_siswa }}</b></td>
                    <td>{{ $m->nisn }}</td>
                    <td>{{ $m->sekolah_asal_tujuan }}</td>
                    <td>{{ \Carbon\Carbon::parse($m->tanggal_mutasi)->format('d M Y') }}</td>
                    <td>{{ $m->alasan }}</td>
                    <td><span class="bd {{ $m->status === 'disetujui' ? 'ok' : 'w' }}">{{ ucfirst($m->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--mu);padding:24px">Belum ada data mutasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Catat Mutasi --}}
<div id="md-mutasi" style="display:none;position:fixed;inset:0;background:#0b224088;z-index:9;place-items:center;padding:20px">
    <div class="mbox">
        <div class="bar"><h3 style="margin:0">Catat Mutasi Siswa</h3><span class="sp"></span>
            <button class="btn x" onclick="document.getElementById('md-mutasi').style.display='none'">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.mutasi-siswa.store') }}">
            @csrf
            <div style="display:grid;gap:12px">
                <div>
                    <label>Jenis Mutasi</label>
                    <select name="jenis" style="width:100%">
                        <option value="masuk">Mutasi Masuk</option>
                        <option value="keluar">Mutasi Keluar</option>
                    </select>
                </div>
                <div><label>Nama Siswa</label><input type="text" name="nama_siswa" style="width:100%" required></div>
                <div><label>NISN</label><input type="text" name="nisn" style="width:100%"></div>
                <div><label>Sekolah Asal / Tujuan</label><input type="text" name="sekolah_asal_tujuan" style="width:100%"></div>
                <div><label>Tanggal Mutasi</label><input type="date" name="tanggal_mutasi" style="width:100%" required></div>
                <div><label>Alasan</label><textarea name="alasan" style="width:100%;height:70px;font:inherit;border:1px solid var(--ln);border-radius:10px;padding:8px" placeholder="Alasan mutasi..."></textarea></div>
            </div>
            <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn o" onclick="document.getElementById('md-mutasi').style.display='none'">Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gantiTab(tab) {
    var url = new URL(window.location); url.searchParams.set('tab', tab); window.location = url;
}
</script>
@endpush

