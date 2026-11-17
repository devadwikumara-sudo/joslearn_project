<div class="card">
    <h3>Import Semua Data</h3>
    <p class="sub">
        Pilih satu workbook yang berisi beberapa sheet. Sistem akan mendeteksi sheet siswa, nilai, kehadiran, dan ekskul,
        lalu memeriksa semuanya sebelum menyimpan.
    </p>

    <div class="fl">
        <div>
            <label for="tahun_ajaran">Tahun ajaran default</label>
            <input value="{{ $taAktif }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
        </div>
        <div>
            <label for="semester">Semester default</label>
            <select name="semester" id="semester" onchange="window.refreshAllImportPreview()">
                <option value="auto" selected>Deteksi dari Excel / pengaturan sekolah</option>
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
            </select>
        </div>
        <div>
            <label for="mode">Kelas siswa default</label>
            <select name="mode" id="mode" onchange="window.refreshAllImportPreview()">
                <option value="semua" selected>Gunakan kelas dari Excel</option>
                <option value="kelas">Satu kelas untuk baris tanpa kelas</option>
            </select>
        </div>
        <div>
            <label for="kelas">Kelas default</label>
            <select name="kelas" id="kelas" onchange="window.refreshAllImportPreview()">
                <option value="*" selected>Deteksi dari Excel</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas }}">{{ $kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="duplikat">Jika siswa sudah ada</label>
            <select name="duplikat" id="duplikat" onchange="window.refreshAllImportPreview()">
                <option value="skip">Lewati perubahan biodata (riwayat tetap diproses)</option>
                <option value="lengkapi" selected>Lengkapi kolom biodata yang kosong</option>
                <option value="timpa">Perbarui dari kolom Excel yang terisi</option>
            </select>
        </div>
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
