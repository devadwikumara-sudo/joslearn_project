<div class="card">
    <div class="bar" style="margin-bottom:14px">
        <div>
            <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Master Kelas &amp; Rombel</h3>
            <p class="sub" style="margin:2px 0 0">Daftar rombongan belajar aktif yang digunakan pada penugasan, filter, dan buku induk</p>
        </div>
        <span class="sp"></span>
        <span class="bd ok" style="font-weight:700">{{ $kelasList->count() }} Kelas Terdaftar</span>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;min-height:56px;align-items:center">
        @forelse($kelasList as $k)
            <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all 0.15s ease">
                <span style="font-weight:700;font-size:13px;color:var(--admin-primary)">{{ $k->nama }}</span>
                <form method="POST" action="{{ route('admin.master-kelas.destroy', $k->id) }}" style="display:inline" onsubmit="return confirm('Hapus kelas {{ $k->nama }}?')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="type" value="kelas">
                    <button type="submit" class="btn x" style="padding:2px 5px;font-size:10px;line-height:1;border-radius:4px" title="Hapus rombel {{ $k->nama }}">✕</button>
                </form>
            </div>
        @empty
            <span style="color:var(--mu);font-size:13px">Belum ada data kelas yang didaftarkan.</span>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-top:16px;padding-top:14px;border-top:1px solid #eef2f6">
        @csrf
        <input type="hidden" name="type" value="kelas">
        <label style="display:block;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Tambah Rombongan Belajar Baru</label>
        <div class="bar" style="gap:10px">
            <input type="text" name="nama_kelas" placeholder="mis. X-1, XI-IPA-2, XII-3" required style="flex:1;font-size:13.5px;padding:9px 12px">
            <button type="submit" class="btn a">➕ Tambah Kelas</button>
        </div>
        <small style="color:var(--mu);display:block;margin-top:6px;font-size:12px">Format penamaan: Awali dengan tingkat (X, XI, XII) diikuti rombongan belajar (misal: <code>X-1</code>, <code>XI-2</code>, <code>XII-IPA-1</code>).</small>
    </form>
</div>
