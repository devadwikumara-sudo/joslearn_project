<div class="card">
    <h3>Catat mutasi siswa</h3>
    <p class="sub" style="margin-bottom:12px">Catat siswa pindah atau keluar dari kelas yang dipilih.</p>
    <form action="{{ route('admin.mutasi-siswa.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tahun_ajaran" value="{{ $taAktifId }}">
        <input type="hidden" name="kelas" value="{{ $kelasAsal === '*' ? '' : $kelasAsal }}">
        <div class="fl">
            <div>
                <label for="mutasi-siswa">Siswa</label>
                <select id="mutasi-siswa" name="siswa_id" required>
                    <option value="">— Pilih siswa aktif —</option>
                    @foreach($siswaAktif as $s)
                        @php($kelasNama = $s->riwayatKelas->first()?->kelas?->nama ?? '-')
                        <option value="{{ $s->id }}" {{ (string) old('siswa_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->nama_lengkap }} · {{ $kelasNama }}
                        </option>
                    @endforeach
                </select>
                @error('siswa_id') <small class="err">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="mutasi-jenis">Jenis</label>
                <select id="mutasi-jenis" name="jenis">
                    <option value="Pindah keluar" {{ old('jenis') === 'Pindah keluar' ? 'selected' : '' }}>Pindah keluar</option>
                    <option value="Keluar" {{ old('jenis') === 'Keluar' ? 'selected' : '' }}>Keluar</option>
                </select>
            </div>
            <div>
                <label for="mutasi-sekolah-tujuan">Sekolah tujuan</label>
                <input id="mutasi-sekolah-tujuan" type="text" name="sekolah_tujuan" value="{{ old('sekolah_tujuan') }}" placeholder="Nama sekolah tujuan" maxlength="255">
            </div>
            <div>
                <label for="mutasi-tanggal">Tanggal</label>
                <input id="mutasi-tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required>
            </div>
        </div>

        <div class="bar" style="margin:14px 0 0">
            <button type="submit" class="btn a">Catat mutasi</button>
        </div>
    </form>
</div>