<div class="card">
    <h3>Import Semua Data</h3>
    <p class="sub">
        Pilih satu workbook yang berisi beberapa sheet. Sistem akan mendeteksi sheet siswa, nilai, kehadiran, ekskul, dan kokurikuler,
        lalu memeriksa semuanya sebelum menyimpan.
    </p>

    <div class="fl">
        <div>
            <label for="kelas">Kelas / tingkat default</label>
            <select name="kelas" id="kelas" onchange="window.refreshAllImportPreview()">
                <option value="auto" selected>Deteksi dari Excel</option>
                <option value="X">Kelas X (tingkat)</option>
                <option value="XI">Kelas XI (tingkat)</option>
                <option value="XII">Kelas XII (tingkat)</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas }}">{{ $kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="semester">Semester default</label>
            <select name="semester" id="semester" onchange="window.refreshAllImportPreview()">
                <option value="auto" selected>Deteksi dari Excel</option>
                <option value="1">1 / Ganjil (tingkat terpilih)</option>
                <option value="2">2 / Genap (tingkat terpilih)</option>
            </select>
        </div>
        <div>
            <label for="dup">Jika data akademik sudah ada</label>
            <select name="dup" id="dup" onchange="window.refreshAllImportPreview()">
                <option value="skip" selected>Lewati data yang sudah ada</option>
                <option value="timpa">Timpa data yang sudah ada</option>
            </select>
        </div>
        <div>
            <label for="duplikat">Jika biodata siswa sudah ada</label>
            <select name="duplikat" id="duplikat" onchange="window.refreshAllImportPreview()">
                <option value="skip">Lewati perubahan biodata (riwayat tetap diproses)</option>
                <option value="lengkapi" selected>Lengkapi kolom biodata yang kosong</option>
                <option value="timpa">Perbarui dari kolom Excel yang terisi</option>
            </select>
        </div>
        <div>
            <label for="mode">Mode kelas biodata siswa</label>
            <select name="mode" id="mode" onchange="window.refreshAllImportPreview()">
                <option value="semua" selected>Gunakan kelas dari Excel</option>
                <option value="kelas">Satu kelas untuk baris tanpa kelas</option>
            </select>
        </div>
        <div>
            <label for="kelas-siswa">Kelas biodata siswa default</label>
            <select name="kelas_siswa" id="kelas-siswa" onchange="window.refreshAllImportPreview()">
                <option value="" selected>Pilih kelas bila diperlukan</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas }}">{{ $kelas }}</option>
                @endforeach
            </select>
        </div>
        @if($isAdmin)
            <div style="min-width:100%">
                <label style="display:flex;gap:8px;align-items:center">
                    <input type="checkbox" name="create_riwayat" id="create_riwayat" value="1" onchange="window.refreshAllImportPreview()">
                    Buat riwayat kelas bila belum ada
                </label>
                <div class="fl" style="margin-top:8px">
                    <div>
                        <label for="create_tahun_ajaran_id">Tahun ajaran riwayat baru</label>
                        <select name="create_tahun_ajaran_id" id="create_tahun_ajaran_id" onchange="window.refreshAllImportPreview()">
                            <option value="">Pilih tahun ajaran</option>
                            @foreach($tahunAjaranList as $tahunAjaran)
                                <option value="{{ $tahunAjaran->id }}">{{ $tahunAjaran->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_kelas_id">Kelas riwayat baru</label>
                        <select name="create_kelas_id" id="create_kelas_id" onchange="window.refreshAllImportPreview()">
                            <option value="">Pilih kelas</option>
                            @foreach($kelasOptions as $kelasOption)
                                <option value="{{ $kelasOption->id }}">{{ $kelasOption->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <small class="sub">Riwayat hanya dibuat saat diperlukan, maksimal tiga riwayat per siswa dan satu per tahun ajaran.</small>
            </div>
        @endif
    </div>

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, '{{ $typeJs }}')">
    </div>
    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Semua sheet harus dipetakan atau diabaikan dengan sengaja. Semua kategori ditahan jika ada error validasi.
    </p>
    <p class="sub" style="margin:6px 0 0;font-size:12px">
        Kategori dikirim berurutan; jika server gagal di tengah proses, kategori yang sudah berhasil tidak dibatalkan dan kategori berikutnya dihentikan.
    </p>
</div>

<div id="josbook-import-preview-area"></div>
