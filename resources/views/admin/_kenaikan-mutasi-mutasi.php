<div class="card">
    <div class="bar" style="margin-bottom:14px">
        <div>
            <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Catat Mutasi Siswa</h3>
            <p class="sub" style="margin:2px 0 0">Catat kepindahan siswa keluar sekolah atau pengunduran diri</p>
        </div>
    </div>

    <form action="{{ route('admin.mutasi-siswa.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tahun_ajaran" value="{{ $taAktifId }}">
        <input type="hidden" name="kelas" value="{{ $kelasAsal === '*' ? '' : $kelasAsal }}">
        
        <div class="fl" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            <div>
                <label for="mutasi-siswa" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Pilih Siswa *</label>
                <select id="mutasi-siswa" name="siswa_id" required style="width:100%">
                    <option value="">— Pilih siswa aktif —</option>
                    @foreach($siswaAktif as $s)
                        @php($kelasNama = $s->riwayatKelas->first()?->kelas?->nama ?? '-')
                        <option value="{{ $s->id }}" {{ (string) old('siswa_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->nama_lengkap }} · Kelas {{ $kelasNama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="mutasi-jenis" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Jenis Mutasi *</label>
                <select id="mutasi-jenis" name="jenis" style="width:100%">
                    <option value="Pindah keluar" {{ old('jenis') === 'Pindah keluar' ? 'selected' : '' }}>Pindah Keluar (Ke Sekolah Lain)</option>
                    <option value="Keluar" {{ old('jenis') === 'Keluar' ? 'selected' : '' }}>Keluar (Berhenti / Mengundurkan Diri)</option>
                </select>
            </div>
            <div>
                <label for="mutasi-sekolah-tujuan" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Sekolah Tujuan</label>
                <input id="mutasi-sekolah-tujuan" type="text" name="sekolah_tujuan" value="{{ old('sekolah_tujuan') }}" placeholder="mis. SMA Negeri 2 ..." maxlength="255" style="width:100%">
            </div>
            <div>
                <label for="mutasi-tanggal" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Tanggal Mutasi *</label>
                <input id="mutasi-tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required style="width:100%">
            </div>
        </div>

        <div class="bar" style="margin-top:16px;padding-top:14px;border-top:1px solid #eef2f6">
            <span class="sub">Data siswa yang dimutasi akan otomatis diperbarui status riwayatnya.</span>
            <span class="sp"></span>
            <button type="submit" class="btn a">⚡ Simpan Catatan Mutasi</button>
        </div>
    </form>
</div>
