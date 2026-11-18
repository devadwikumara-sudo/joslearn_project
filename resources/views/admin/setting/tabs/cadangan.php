<div class="g g2" style="gap:20px">
    {{-- Card 1: Backup --}}
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
            <div style="width:42px;height:42px;border-radius:12px;background:var(--admin-primary-lighter);color:var(--admin-primary);display:flex;align-items:center;justify-content:center;font-size:20px">
                💾
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Cadangan Data (Backup)</h3>
                <span class="sub" style="font-size:12px">Ekspor seluruh snapshot basis data JosBook</span>
            </div>
        </div>

        <p class="sub" style="margin:0 0 16px;font-size:13px;line-height:1.5">
            Cadangan berkas disimpan privat dan terenkripsi di server. Sistem juga menjalankan pembuatan cadangan terjadwal secara otomatis setiap hari pukul <b>02.00 WIB</b> dengan rotasi 7 berkas terakhir.
        </p>

        <form method="POST" action="{{ route('admin.setting.cadangan.unduh') }}">
            @csrf
            <button type="submit" class="btn a" style="width:100%;justify-content:center;padding:10px 16px">⬇ Buat &amp; Unduh Cadangan JSON Sekarang</button>
        </form>

        @if($cadanganList->isNotEmpty())
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #eef2f6;flex:1">
                <label style="display:block;font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Riwayat Cadangan Tersimpan</label>
                <div style="display:grid;gap:8px">
                    @foreach($cadanganList as $cadangan)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:12.5px">
                            <span style="font-family:monospace;font-weight:600;color:var(--admin-text)">{{ $cadangan->nama_file }}</span>
                            <span class="bd" style="font-size:11px;font-feature-settings:'tnum'">{{ number_format((int) $cadangan->ukuran_byte / 1024, 1) }} KB</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Card 2: Restore --}}
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
            <div style="width:42px;height:42px;border-radius:12px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:20px">
                🔄
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Pemulihan Data (Restore)</h3>
                <span class="sub" style="font-size:12px">Pulihkan sistem dari berkas cadangan JSON sebelumnya</span>
            </div>
        </div>

        <div class="note" style="margin-bottom:14px;font-size:12.5px">
            ⚠ <b>Penting:</b> Seluruh data saat ini akan disinkronkan sesuai file cadangan. Sistem otomatis membuat cadangan darurat sesaat sebelum proses pemulihan dieksekusi.
        </div>

        <form method="POST" action="{{ route('admin.setting.cadangan.pulihkan') }}" enctype="multipart/form-data" style="display:grid;gap:14px;flex:1" onsubmit="return confirm('Data saat ini akan ditimpa dengan berkas cadangan ini. Snapshot darurat akan dibuat otomatis. Lanjutkan pemulihan?')">
            @csrf
            <div>
                <label for="backup-file" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Pilih Berkas Cadangan JSON *</label>
                <input id="backup-file" type="file" name="backup_file" accept=".json,application/json" required style="width:100%;margin-top:4px;padding:8px">
            </div>

            <div>
                <label for="restore-confirmation" style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Konfirmasi Keamanan: Ketik <code style="background:#fee2e2;color:#991b1b;padding:2px 5px;border-radius:4px">PULIHKAN</code></label>
                <input id="restore-confirmation" type="text" name="konfirmasi" autocomplete="off" required style="width:100%;margin-top:4px" placeholder="PULIHKAN">
            </div>

            <div style="margin-top:auto;padding-top:14px">
                <button type="submit" class="btn o" style="width:100%;justify-content:center;padding:10px 16px;font-weight:700">🔄 Jalankan Pemulihan Basis Data</button>
            </div>
        </form>
    </div>
</div>
