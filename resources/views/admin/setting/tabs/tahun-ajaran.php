<div class="card" style="margin-bottom:18px">
    <div class="bar" style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
        <div>
            <h3 style="margin:0;font-size:17px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Kelola Tahun Ajaran
            </h3>
            <p class="sub" style="margin:3px 0 0">Tentukan tahun ajaran operasional aktif dan daftarkan periode tahun ajaran baru</p>
        </div>
    </div>

    {{-- Pills Daftar Tahun Ajaran --}}
    <label style="display:block;font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Daftar Periode Tersedia</label>
    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px 14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:18px;align-items:center">
        @forelse($tahunAjaranList as $tahunAjaran)
            <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:{{ $tahunAjaran->sedang_berjalan ? '#eff6ff' : '#fff' }};border:1.5px solid {{ $tahunAjaran->sedang_berjalan ? '#93c5fd' : '#cbd5e1' }};border-radius:20px;font-size:13px;font-weight:700;color:{{ $tahunAjaran->sedang_berjalan ? 'var(--admin-primary)' : 'var(--admin-text)' }};box-shadow:0 1px 3px rgba(0,0,0,0.04)">
                @if($tahunAjaran->sedang_berjalan)
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--admin-primary);display:inline-block;box-shadow:0 0 8px var(--admin-primary)"></span>
                @endif
                <span>{{ $tahunAjaran->nama }}</span>
                @if($tahunAjaran->sedang_berjalan)
                    <span style="font-size:11px;background:var(--admin-primary);color:#fff;padding:1px 6px;border-radius:10px">Aktif</span>
                @endif
            </div>
        @empty
            <span class="sub" style="font-size:13px">Belum ada data tahun ajaran.</span>
        @endforelse
    </div>

    <div class="g g2" style="gap:16px">
        {{-- Set Tahun Berjalan --}}
        <div style="background:#f8fafc;padding:16px;border-radius:12px;border:1px solid #e2e8f0">
            <h4 style="margin:0 0 6px;font-size:14px;color:var(--admin-text)">Pilih Tahun Ajaran Berjalan</h4>
            <p class="sub" style="margin:0 0 12px;font-size:12px">Mengubah tahun ajaran berjalan akan memindahkan fokus dashboard dan input nilai.</p>
            
            <form method="POST" action="{{ route('admin.setting.tahun-ajaran.aktif') }}">
                @csrf
                <div style="display:grid;gap:10px">
                    <select name="ta_id" required style="width:100%;font-weight:700">
                        <option value="">— Pilih Tahun Ajaran —</option>
                        @foreach($tahunAjaranList as $tahunAjaran)
                            <option value="{{ $tahunAjaran->id }}" {{ (string) old('ta_id', $tahunAjaran->sedang_berjalan ? $tahunAjaran->id : '') === (string) $tahunAjaran->id ? 'selected' : '' }}>
                                {{ $tahunAjaran->nama }} {{ $tahunAjaran->sedang_berjalan ? '★ (Sedang Berjalan)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn a" style="justify-content:center">★ Jadikan Tahun Berjalan</button>
                </div>
            </form>
        </div>

        {{-- Tambah Tahun Ajaran Baru --}}
        <div style="background:#f8fafc;padding:16px;border-radius:12px;border:1px solid #e2e8f0">
            <h4 style="margin:0 0 6px;font-size:14px;color:var(--admin-text)">Tambah Tahun Ajaran Baru</h4>
            <p class="sub" style="margin:0 0 12px;font-size:12px">Masukkan format tahun ajaran baru untuk persiapan kenaikan kelas.</p>

            <form method="POST" action="{{ route('admin.setting.tahun-ajaran.tambah') }}">
                @csrf
                <div style="display:grid;gap:10px">
                    <input type="text" name="nama_ta" placeholder="Contoh: 2026/2027" required style="width:100%">
                    <button type="submit" class="btn o" style="justify-content:center">➕ Daftarkan Tahun Ajaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Zona Berbahaya --}}
<div class="card" style="border:1.5px solid #f87171;background:#fffafb;border-radius:14px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:18px">
            ⚠️
        </div>
        <div>
            <h3 style="margin:0;font-size:16px;color:#991b1b">Zona Berbahaya: Pembersihan Data Tahun Ajaran</h3>
            <p class="sub" style="margin:2px 0 0;color:#7f1d1d;font-size:12.5px">Tindakan ini menghapus seluruh riwayat, nilai, kehadiran, ekskul, dan penugasan pada tahun ajaran terpilih. Data biodata siswa dan daftar tahun ajaran tetap aman. Cadangan otomatis dibuat sebelum penghapusan.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.setting.tahun-ajaran.hapus-data') }}" onsubmit="return confirm('Tindakan ini tidak dapat dibatalkan. Cadangan sistem otomatis dibuat terlebih dahulu. Lanjutkan penghapusan data?')">
        @csrf
        @method('DELETE')
        <div style="display:grid;gap:12px;max-width:540px;margin-top:14px">
            <div>
                <label for="delete-year" style="font-size:12px;font-weight:700;color:#991b1b">Pilih Tahun Ajaran yang Akan Dikosongkan:</label>
                <select id="delete-year" name="tahun_ajaran_id" required style="width:100%;margin-top:4px">
                    <option value="">— Pilih Tahun Ajaran —</option>
                    @foreach($tahunAjaranList as $tahunAjaran)
                        <option value="{{ $tahunAjaran->id }}" data-confirmation="HAPUS {{ $tahunAjaran->nama }}">{{ $tahunAjaran->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#991b1b">Konfirmasi: Ketik <code id="delete-confirmation-text" style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px">HAPUS ...</code> di bawah ini:</label>
                <input type="text" name="konfirmasi" autocomplete="off" required style="width:100%;margin-top:4px;border:1.5px solid #fca5a5" placeholder="HAPUS ...">
            </div>
            <div>
                <button type="submit" class="btn danger" style="padding:9px 18px">🗑 Hapus Semua Data Tahun Ajaran Terpilih</button>
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
