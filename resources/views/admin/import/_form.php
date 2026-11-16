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
                <label for="kelas">Kelas</label>
                <select name="kelas" id="kelas">
                    <option value="*" selected>Semua kelas yang diizinkan</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas }}">{{ $kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="semester">Semester</label>
                <select name="semester" id="semester">
                    <option value="auto" selected>Deteksi dari kolom Excel</option>
                    <option value="1">Semester 1 / Ganjil (sesuai tingkat kelas)</option>
                    <option value="2">Semester 2 / Genap (sesuai tingkat kelas)</option>
                    <option value="local-ganjil">Ganjil (sesuai tingkat kelas)</option>
                    <option value="local-genap">Genap (sesuai tingkat kelas)</option>
                    <option value="3">Semester global 3 (XI Ganjil)</option>
                    <option value="4">Semester global 4 (XI Genap)</option>
                    <option value="5">Semester global 5 (XII Ganjil)</option>
                    <option value="6">Semester global 6 (XII Genap)</option>
                </select>
            </div>
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

    <div class="bar" style="margin-top:14px">
        <input type="file" accept=".xlsx,.xls,.csv" id="excel-file-input" onchange="smartFile(event, '{{ $typeJs }}')">
        @if($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
            <button type="button" class="btn" onclick="loadDemo{{ $type === 'nilai' ? 'Nilai' : ($type === 'kehadiran' ? 'Hadir' : 'Ekskul') }}()">Pakai data contoh</button>
        @endif
        <a href="{{ route($templateRoute, ['jenis' => $type]) }}" class="btn">⬇ Template Excel</a>
    </div>

    <p class="sub" style="margin:8px 0 0;font-size:12px">
        Setelah pratinjau muncul, tekan tombol {{ $type === 'siswa' ? 'Impor ke Data Siswa' : 'Impor' }} di atas tabel untuk menyimpan. Baris yang tidak valid atau tidak sesuai hak akses tidak disimpan.
    </p>
    @if($type === 'nilai')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>kelas</code>, <code>sem</code>, dan kode/nama mapel. Format tabel lebar maupun panjang didukung.
        </p>
    @elseif($type === 'kehadiran')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>sakit</code>, <code>izin</code>, <code>alpa</code>, dan <code>kokur</code>.
        </p>
    @elseif($type === 'ekskul')
        <p class="sub" style="margin:8px 0 0;font-size:12px">
            Kolom didukung: <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>sem</code>, <code>ekskul</code>, dan <code>predikat</code>.
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
            Nilai semester 1–2 di dalam kelas atau label Ganjil/Genap dikenali untuk kelas X, XI, dan XII.
        </p>
    @endif
</div>

<div id="josbook-import-preview-area"></div>

@if($isAdmin && in_array($type, ['nilai', 'kehadiran', 'ekskul'], true))
    @include('admin.import._demo-scripts', ['type' => $type])
@endif
