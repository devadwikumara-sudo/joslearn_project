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
