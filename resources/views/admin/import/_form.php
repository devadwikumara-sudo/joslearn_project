<div class="card" style="margin-top:14px">
    <h3>Import {{ $typeLabel }}</h3>
    <div class="fl">
        @if(in_array($type, ['siswa', 'guru'], true))
            <div>
                <label for="tahun_ajaran">Tahun ajaran</label>
                <input value="{{ $taAktif }}" name="tahun_ajaran" id="tahun_ajaran" readonly>
                <small class="sub">Sistem memakai tahun ajaran aktif jika tidak ditemukan di file.</small>
            </div>
        @endif

        @if($type === 'siswa')
            <div>
                <label for="mode">Mode</label>
                <select name="mode" id="mode" onchange="window.refreshImportPreviewOptions()">
                    <option value="kelas">Satu kelas</option>
                    <option value="semua" selected>Semua kelas (dari kolom kelas di Excel)</option>
                </select>
            </div>
            <div>
                <label for="kelas">Kelas (ketik baru = dibuat otomatis)</label>
                <input list="dlk" name="kelas" id="kelas" value="{{ $kelasList->first() ?? '' }}" onchange="window.refreshImportPreviewOptions()">
                <datalist id="dlk">
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas }}">
                    @endforeach
                </datalist>
            </div>
        @elseif(in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
            <div>
                <label for="kelas">Kelas / tingkat tujuan</label>
                <select name="kelas" id="kelas">
                    <option value="auto" selected>Deteksi dari Excel</option>
                    @if($isAdmin)
                        <option value="X">Kelas X (tingkat)</option>
                        <option value="XI">Kelas XI (tingkat)</option>
                        <option value="XII">Kelas XII (tingkat)</option>
                    @endif
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas }}">{{ $kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="semester">Semester</label>
                <select name="semester" id="semester">
                    <option value="auto" selected>Deteksi dari kolom Excel</option>
                    <option value="1">1 / Ganjil (tingkat terpilih)</option>
                    <option value="2">2 / Genap (tingkat terpilih)</option>
                </select>
            </div>
            <div>
                <label for="dup">Jika data akademik sudah ada</label>
                <select name="dup" id="dup">
                    <option value="skip" selected>Lewati data yang sudah ada</option>
                    <option value="timpa">Timpa data yang sudah ada</option>
                </select>
            </div>
            @if($isAdmin)
                <div style="min-width:100%">
                    <label style="display:flex;gap:8px;align-items:center">
                        <input type="checkbox" name="create_riwayat" id="create_riwayat" value="1">
                        Buat riwayat kelas bila belum ada
                    </label>
                    <div class="fl" style="margin-top:8px">
                        <div>
                            <label for="create_tahun_ajaran_id">Tahun ajaran riwayat baru</label>
                            <select name="create_tahun_ajaran_id" id="create_tahun_ajaran_id">
                                <option value="">Pilih tahun ajaran</option>
                                @foreach($tahunAjaranList as $tahunAjaran)
                                    <option value="{{ $tahunAjaran->id }}">{{ $tahunAjaran->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="create_kelas_id">Kelas riwayat baru</label>
                            <select name="create_kelas_id" id="create_kelas_id">
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
        @endif

        @if(in_array($type, ['siswa', 'guru'], true))
            <div>
                <label for="duplikat">{{ $type === 'siswa' ? 'Jika NISN/NIS sudah ada' : 'Jika NIP sudah ada' }}</label>
                <select name="duplikat" id="duplikat">
                    @if($type === 'siswa')
                        <option value="skip">Lewati perubahan biodata (riwayat tetap diproses)</option>
                        <option value="lengkapi" selected>Lengkapi kolom biodata yang kosong</option>
                        <option value="timpa">Perbarui dari kolom Excel yang terisi</option>
                    @else
                        <option value="skip">Lewati</option>
                        <option value="timpa" selected>Timpa</option>
                    @endif
                </select>
            </div>
        @endif
    </div>

    <div class="bar" style="margin-top:16px;flex-wrap:wrap;gap:10px;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
        <div style="flex:1;min-width:240px">
            <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, '{{ $typeJs }}')" style="width:100%;padding:8px">
        </div>
        <span class="sp"></span>
        @if($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
            <button type="button" class="btn o" onclick="loadDemo{{ $type === 'nilai' ? 'Nilai' : ($type === 'kehadiran' ? 'Hadir' : 'Ekskul') }}()">🧪 Pakai Data Contoh</button>
        @endif
        <a href="{{ route($templateRoute, ['jenis' => $type]) }}" class="btn o">⬇ Unduh Template Excel</a>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Setelah pratinjau muncul, tekan tombol {{ $type === 'siswa' ? 'Impor ke Data Siswa' : 'Impor' }} di atas tabel untuk menyimpan. Baris yang tidak valid atau tidak sesuai hak akses tidak disimpan.
    </p>
    @if($type === 'nilai')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, dan kode/nama mapel. Tahun ajaran tujuan mengikuti riwayat kelas siswa.
        </p>
    @elseif($type === 'kehadiran')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>sakit</code>, <code>izin</code>, <code>alpa</code>, dan <code>kokur</code>. Tahun ajaran tujuan mengikuti riwayat kelas siswa.
        </p>
    @elseif($type === 'ekskul')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>ekskul</code>, dan <code>predikat</code>. Tahun ajaran tujuan mengikuti riwayat kelas siswa.
        </p>
    @elseif($type === 'guru')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung mencakup NIP, NUPTK, nama, jenis kelamin, data lahir, pendidikan, status kepegawaian, nomor HP, dan alamat.
        </p>
    @elseif($type === 'siswa')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Format Excel dideteksi otomatis. Siswa dicocokkan berdasarkan NISN atau NIS; riwayat kelas tetap diproses untuk siswa yang sudah ada.
        </p>
    @endif
    @if($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Semester 1/Ganjil dan 2/Genap dipetakan menurut tingkat kelas; kolom semester yang tidak tersedia wajib dipilih sebelum import.
        </p>
    @endif
</div>

<div id="josbook-import-preview-area"></div>

@if($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
    @include('admin.import._demo-scripts', ['type' => $type])
@endif
