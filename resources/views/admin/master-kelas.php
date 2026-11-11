@extends('layouts.app')

@section('title', 'Master Kelas & Mapel — JosBook Admin')

@section('content')
<h2>Master Kelas &amp; Mapel</h2>
<p class="sub">Daftar ini menjadi tabel <code>kelas</code> dan <code>mapel</code>. Kelas dan mapel baru langsung dipakai di seluruh aplikasi (filter, editor nilai, import, PDF). Hanya yang ditambahkan di sini yang bisa dihapus, dan hanya bila belum dipakai.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="note err">{{ session('error') }}</div>
@endif

<div class="g g2">
    {{-- Card Kiri: Master Kelas / Rombel --}}
    <div class="card">
        <h3>Kelas / rombel</h3>
        <div class="bar" style="flex-wrap:wrap;gap:8px">
            @forelse($kelasList as $k)
            <span style="display:inline-flex;align-items:center;gap:4px">
                <span class="bd">{{ $k->nama }}</span>
                <form method="POST" action="{{ route('admin.master-kelas.destroy', $k->id) }}" style="display:inline" onsubmit="return confirm('Hapus kelas {{ $k->nama }}?')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="type" value="kelas">
                    <button type="submit" class="btn x" style="padding:2px 6px;font-size:10px" title="Hapus kelas">🗑</button>
                </form>
            </span>
            @empty
            <span style="color:var(--mu)">Belum ada data kelas.</span>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-top:16px">
            @csrf
            <input type="hidden" name="type" value="kelas">
            <div class="bar">
                <input type="text" name="nama_kelas" placeholder="mis. X-2, XI-3, XII-2" required style="flex:1">
                <button type="submit" class="btn a">Tambah kelas</button>
            </div>
            <small style="color:var(--mu);display:block;margin-top:4px">Format: Tingkat (X, XI, XII) diikuti tanda strip dan angka (misal X-1, XI-2).</small>
        </form>
    </div>

    {{-- Card Kanan: Mata Pelajaran --}}
    <div class="card">
        <h3>Mata pelajaran</h3>
        <div class="tw" style="max-height:280px;overflow-y:auto">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">Kode</th>
                        <th>Nama Mapel</th>
                        <th style="width:90px">Tingkat</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapelList as $m)
                    <tr>
                        <td><code>{{ $m->kode ?: strtolower(substr($m->nama, 0, 3)) }}</code></td>
                        <td>{{ $m->nama }}</td>
                        <td><small>{{ $m->tingkat ?: 'X,XI,XII' }}</small></td>
                        <td>
                            <form method="POST" action="{{ route('admin.master-mapel.destroy', $m->id) }}" style="display:inline" onsubmit="return confirm('Hapus mapel {{ $m->nama }}?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="type" value="mapel">
                                <button type="submit" class="btn x" style="padding:2px 6px;font-size:10px" title="Hapus mapel">🗑</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--mu)">Belum ada data mata pelajaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-top:12px">
            @csrf
            <input type="hidden" name="type" value="mapel">
            <div class="fl" style="grid-template-columns:1fr 2fr;margin-bottom:8px">
                <div>
                    <label>Kode (3 huruf)</label>
                    <input type="text" name="kode_mapel" maxlength="5" placeholder="mtk" style="width:100%">
                </div>
                <div>
                    <label>Nama mapel</label>
                    <input type="text" name="nama_mapel" required placeholder="mis. Fisika Terapan" style="width:100%">
                </div>
            </div>
            <div class="bar">
                <label style="display:flex;align-items:center;gap:4px;margin:0">
                    <input type="checkbox" name="tingkat_mapel[]" value="X" checked> X
                </label>
                <label style="display:flex;align-items:center;gap:4px;margin:0">
                    <input type="checkbox" name="tingkat_mapel[]" value="XI" checked> XI
                </label>
                <label style="display:flex;align-items:center;gap:4px;margin:0">
                    <input type="checkbox" name="tingkat_mapel[]" value="XII" checked> XII
                </label>
                <span class="sp"></span>
                <button type="submit" class="btn a">Tambah mapel</button>
            </div>
        </form>
    </div>
</div>
@endsection
