<div class="card">
    <div class="bar" style="margin-bottom:14px">
        <div>
            <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Master Mata Pelajaran</h3>
            <p class="sub" style="margin:2px 0 0">Kurikulum mata pelajaran yang diajarkan dan tercatat di rapor buku induk</p>
        </div>
        <span class="sp"></span>
        <span class="bd ok" style="font-weight:700">{{ $mapelList->count() }} Mapel Terdaftar</span>
    </div>

    <div class="tw" style="max-height:340px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:10px">
        <table>
            <thead>
                <tr>
                    <th style="width:90px">Kode</th>
                    <th>Nama Mata Pelajaran</th>
                    <th style="width:140px">Berlaku di Tingkat</th>
                    <th style="width:60px;text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mapelList as $m)
                    <tr>
                        <td><code style="font-size:12px;font-weight:800;color:var(--admin-primary);padding:3px 8px;background:#eff6ff;border-radius:6px;border:1px solid #bfdbfe">{{ strtoupper($m->kode ?: substr($m->nama, 0, 3)) }}</code></td>
                        <td><b style="color:var(--admin-text);font-size:13.5px">{{ $m->nama }}</b></td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                @php
                                    $tingkats = explode(',', $m->tingkat ?: 'X,XI,XII');
                                @endphp
                                @foreach($tingkats as $t)
                                    <span class="bd" style="font-size:10.5px;padding:2px 6px">{{ trim($t) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.master-mapel.destroy', $m->id) }}" style="display:inline" onsubmit="return confirm('Hapus mapel {{ $m->nama }}?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="type" value="mapel">
                                <button type="submit" class="btn x" style="padding:4px 7px;font-size:11px;border-radius:6px" title="Hapus mapel {{ $m->nama }}">🗑</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:28px;color:var(--mu)">
                            Belum ada data mata pelajaran yang didaftarkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-top:16px;padding-top:16px;border-top:1px solid #eef2f6">
        @csrf
        <input type="hidden" name="type" value="mapel">
        <label style="display:block;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Tambah Mata Pelajaran Baru</label>
        
        <div class="fl" style="grid-template-columns:140px 1fr;gap:12px;margin-bottom:12px">
            <div>
                <label style="font-size:11.5px;font-weight:600">Kode Mapel</label>
                <input type="text" name="kode_mapel" maxlength="10" placeholder="mis. MTK" style="width:100%;text-transform:uppercase">
            </div>
            <div>
                <label style="font-size:11.5px;font-weight:600">Nama Mata Pelajaran *</label>
                <input type="text" name="nama_mapel" required placeholder="mis. Matematika Wajib" style="width:100%">
            </div>
        </div>

        <div class="bar" style="flex-wrap:wrap;gap:14px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text)">Tingkat Kelas:</span>
            <label style="display:inline-flex;align-items:center;gap:5px;margin:0;font-size:13px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="tingkat_mapel[]" value="X" checked> Kelas X
            </label>
            <label style="display:inline-flex;align-items:center;gap:5px;margin:0;font-size:13px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="tingkat_mapel[]" value="XI" checked> Kelas XI
            </label>
            <label style="display:inline-flex;align-items:center;gap:5px;margin:0;font-size:13px;font-weight:600;cursor:pointer">
                <input type="checkbox" name="tingkat_mapel[]" value="XII" checked> Kelas XII
            </label>
            <span class="sp"></span>
            <button type="submit" class="btn a">➕ Tambah Mapel</button>
        </div>
    </form>
</div>
