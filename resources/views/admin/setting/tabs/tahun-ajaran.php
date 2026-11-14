<div class="card" style="margin-bottom:14px">
    <h3>📅 Tahun Ajaran</h3>
    <p class="sub">Daftar tahun ajaran dan pilihan tahun yang sedang berjalan.</p>
    <div class="bar" style="flex-wrap:wrap;gap:8px;margin-bottom:12px">
        @forelse($tahunAjaranList as $tahunAjaran)
            <span class="bd {{ $tahunAjaran->sedang_berjalan ? 'ok' : '' }}">
                {{ $tahunAjaran->nama }} @if($tahunAjaran->sedang_berjalan) ★ @endif
            </span>
        @empty
            <span class="sub">Belum ada tahun ajaran.</span>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.setting.tahun-ajaran.aktif') }}" style="margin-bottom:16px">
        @csrf
        <div class="bar">
            <select name="ta_id" required style="flex:1">
                <option value="">Pilih tahun ajaran aktif</option>
                @foreach($tahunAjaranList as $tahunAjaran)
                    <option value="{{ $tahunAjaran->id }}" {{ (string) old('ta_id', $tahunAjaran->sedang_berjalan ? $tahunAjaran->id : '') === (string) $tahunAjaran->id ? 'selected' : '' }}>
                        {{ $tahunAjaran->nama }} {{ $tahunAjaran->sedang_berjalan ? '(Sedang Berjalan)' : '' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn a">Jadikan Tahun Berjalan</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.setting.tahun-ajaran.tambah') }}">
        @csrf
        <div class="bar">
            <input type="text" name="nama_ta" placeholder="Format: 2026/2027" required style="flex:1">
            <button type="submit" class="btn a">+ Tambah Tahun Ajaran</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top:14px;border:1px solid #d97706">
    <h3>⚠️ Zona Berbahaya</h3>
    <p class="sub">Hapus seluruh riwayat, nilai, kehadiran, ekskul, dan penugasan pada tahun ajaran terpilih. Data siswa dan daftar tahun ajaran tetap tersimpan. Cadangan otomatis dibuat sebelum penghapusan.</p>
    <form method="POST" action="{{ route('admin.setting.tahun-ajaran.hapus-data') }}" onsubmit="return confirm('Tindakan ini tidak dapat dibatalkan. Cadangan akan dibuat terlebih dahulu. Lanjutkan?')">
        @csrf
        @method('DELETE')
        <div style="display:grid;gap:10px;max-width:480px">
            <div>
                <label for="delete-year">Tahun ajaran</label>
                <select id="delete-year" name="tahun_ajaran_id" required>
                    <option value="">Pilih tahun ajaran</option>
                    @foreach($tahunAjaranList as $tahunAjaran)
                        <option value="{{ $tahunAjaran->id }}" data-confirmation="HAPUS {{ $tahunAjaran->nama }}">{{ $tahunAjaran->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Ketik <code id="delete-confirmation-text">HAPUS ...</code> untuk melanjutkan</label>
                <input type="text" name="konfirmasi" autocomplete="off" required style="width:100%">
            </div>
            <div>
                <button type="submit" class="btn x">Hapus Semua Data Tahun Ajaran</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var yearSelect = document.getElementById('delete-year');
        var confirmationText = document.getElementById('delete-confirmation-text');

        if (!yearSelect || !confirmationText) {
            return;
        }

        var updateConfirmationText = function () {
            var selectedOption = yearSelect.options[yearSelect.selectedIndex];
            confirmationText.textContent = selectedOption && selectedOption.dataset.confirmation
                ? selectedOption.dataset.confirmation
                : 'HAPUS ...';
        };

        yearSelect.addEventListener('change', updateConfirmationText);
        updateConfirmationText();
    });
</script>
@endpush
