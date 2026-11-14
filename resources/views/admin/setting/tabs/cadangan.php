<div class="card">
    <h3>💾 Cadangan Data</h3>
    <p class="sub">Cadangan JSON disimpan privat. Sistem membuat cadangan otomatis setiap hari pukul 02.00 dan menyimpan 7 cadangan terakhir.</p>
    <form method="POST" action="{{ route('admin.setting.cadangan.unduh') }}">
        @csrf
        <button type="submit" class="btn a">⬇ Buat dan Unduh Cadangan</button>
    </form>

    <form method="POST" action="{{ route('admin.setting.cadangan.pulihkan') }}" enctype="multipart/form-data" style="margin-top:14px" onsubmit="return confirm('Data yang ada akan dipulihkan dari file ini. Cadangan sebelum pemulihan dibuat otomatis. Lanjutkan?')">
        @csrf
        <label for="backup-file">Pulihkan dari file JSON</label>
        <input id="backup-file" type="file" name="backup_file" accept=".json,application/json" required style="width:100%">
        <label for="restore-confirmation" style="display:block;margin-top:8px">Ketik PULIHKAN untuk mengonfirmasi</label>
        <input id="restore-confirmation" type="text" name="konfirmasi" autocomplete="off" required style="width:100%">
        <button type="submit" class="btn o" style="margin-top:10px">Pulihkan Cadangan</button>
    </form>

    @if($cadanganList->isNotEmpty())
        <div style="margin-top:14px">
            <strong>Cadangan terbaru</strong>
            @foreach($cadanganList as $cadangan)
                <div class="sub" style="margin:5px 0">
                    {{ $cadangan->nama_file }} · {{ number_format((int) $cadangan->ukuran_byte) }} byte
                </div>
            @endforeach
        </div>
    @endif
</div>
