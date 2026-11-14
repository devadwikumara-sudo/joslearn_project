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
