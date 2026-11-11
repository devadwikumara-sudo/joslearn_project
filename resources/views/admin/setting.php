@extends('layouts.app')

@section('title', 'Setting — JosBook Admin')

@section('content')
<h2>Setting</h2>
<p class="sub">Identitas sekolah, tahun ajaran dan semester berjalan, cadangan, zona berbahaya, serta akun pengguna.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="note err">{{ session('error') }}</div>
@endif

@if($errors->any())
<div class="note err">
    <ul style="margin:0;padding-left:20px">
        @foreach($errors->all() as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="g g2">
    {{-- Profil Sekolah --}}
    <div class="card">
        <h3>🏫 Identitas Sekolah</h3>
        <p class="sub">Data ini akan muncul pada sampul buku induk dan laporan capaian hasil belajar.</p>
        <form method="POST" action="{{ route('admin.setting.update') }}">
            @csrf
            @method('PUT')
            <div style="display:grid;gap:12px">
                <div>
                    <label>Nama Sekolah</label>
                    <input type="text" name="nama" maxlength="150" style="width:100%" value="{{ old('nama', $setting->nama ?? 'SMA Negeri 1') }}" required>
                </div>
                <div>
                    <label>NPSN</label>
                    <input type="text" name="npsn" maxlength="20" style="width:100%" value="{{ old('npsn', $setting->npsn ?? '') }}">
                </div>
                <div>
                    <label>Alamat Sekolah</label>
                    <textarea name="alamat" maxlength="255" style="width:100%;height:65px;font:inherit;border:1px solid var(--ln);border-radius:10px;padding:8px">{{ old('alamat', $setting->alamat ?? '') }}</textarea>
                </div>
                <div>
                    <label>Kelurahan / Desa</label>
                    <input type="text" name="desa" maxlength="100" style="width:100%" value="{{ old('desa', $setting->desa ?? '') }}">
                </div>
                <div>
                    <label>Kecamatan</label>
                    <input type="text" name="kecamatan" maxlength="100" style="width:100%" value="{{ old('kecamatan', $setting->kecamatan ?? '') }}">
                </div>
                <div>
                    <label>Kabupaten / Kota</label>
                    <input type="text" name="kabupaten" maxlength="100" style="width:100%" value="{{ old('kabupaten', $setting->kabupaten ?? '') }}">
                </div>
                <div>
                    <label>Provinsi</label>
                    <input type="text" name="provinsi" maxlength="100" style="width:100%" value="{{ old('provinsi', $setting->provinsi ?? '') }}">
                </div>
                <div>
                    <label>Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah" maxlength="150" style="width:100%" value="{{ old('nama_kepala_sekolah', $setting->nama_kepala_sekolah ?? '') }}">
                </div>
                <div>
                    <label>NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah" maxlength="30" style="width:100%" value="{{ old('nip_kepala_sekolah', $setting->nip_kepala_sekolah ?? '') }}">
                </div>
                <div>
                    <label>Tahun Ajaran Berjalan</label>
                    <select name="tahun_ajaran_aktif_id" style="width:100%" required>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach($tahunAjaranList ?? [] as $ta)
                            <option value="{{ $ta->id }}" {{ (string) old('tahun_ajaran_aktif_id', $ta->sedang_berjalan ? $ta->id : '') === (string) $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }} {{ $ta->sedang_berjalan ? '(Sedang Berjalan)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Semester Berjalan</label>
                    <select name="semester_berjalan" style="width:100%" required>
                        <option value="Ganjil" {{ old('semester_berjalan', $semesterBerjalan) === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="Genap" {{ old('semester_berjalan', $semesterBerjalan) === 'Genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                    <small class="sub">Menentukan semester berjalan untuk impor dan akses data siswa di aplikasi mobile.</small>
                </div>
                <div>
                    <label>Nilai KKTP</label>
                    <input type="number" name="kktp" min="0" max="100" step="0.01" style="width:100%" value="{{ old('kktp', $kktp) }}" required>
                </div>
            </div>
            <div style="margin-top:14px">
                <button type="submit" class="btn a">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <div>
        {{-- Tahun Ajaran --}}
        <div class="card" style="margin-bottom:14px">
            <h3>📅 Tahun Ajaran</h3>
            <p class="sub">Daftar tahun ajaran yang terdaftar di sistem:</p>
            <div class="bar" style="flex-wrap:wrap;gap:8px;margin-bottom:12px">
                @foreach($tahunAjaranList ?? [] as $ta)
                    <span class="bd {{ $ta->sedang_berjalan ? 'ok' : '' }}">
                        {{ $ta->nama }} @if($ta->sedang_berjalan) ★ @endif
                    </span>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.setting.tahun-ajaran.tambah') }}">
                @csrf
                <div class="bar">
                    <input type="text" name="nama_ta" placeholder="Format: 2026/2027" required style="flex:1">
                    <button type="submit" class="btn a">+ Tambah Tahun Ajaran</button>
                </div>
            </form>
        </div>

        {{-- Tempat Sampah & Log Aktivitas --}}
        <div class="card">
            <h3>🗑️ Tempat Sampah & Log Aktivitas</h3>
            <p class="sub">Pulihkan data yang terhapus atau telusuri riwayat perubahan oleh pengguna.</p>
            <div class="bar" style="flex-wrap:wrap">
                <a href="{{ route('admin.sampah') }}" class="btn o">Lihat Tempat Sampah</a>
                <a href="{{ route('admin.log-aktivitas') }}" class="btn o">Lihat Log Aktivitas</a>
            </div>
        </div>

        {{-- Cadangan Data --}}
        <div class="card" style="margin-top:14px">
            <h3>💾 Cadangan Data</h3>
            <p class="sub">Cadangan JSON disimpan privat. Sistem membuat cadangan otomatis setiap hari dan menyimpan 7 cadangan terakhir.</p>
            <form method="POST" action="{{ route('admin.setting.cadangan.unduh') }}">
                @csrf
                <button type="submit" class="btn a">⬇ Unduh Cadangan</button>
            </form>

            <form method="POST" action="{{ route('admin.setting.cadangan.pulihkan') }}" enctype="multipart/form-data" style="margin-top:14px" onsubmit="return confirm('Data yang ada akan dipulihkan dari file ini. Cadangan sebelum pemulihan dibuat otomatis. Lanjutkan?')">
                @csrf
                <label for="backup-file">Pulihkan dari file JSON</label>
                <input id="backup-file" type="file" name="backup_file" accept=".json,application/json" required style="width:100%">
                <label for="restore-confirmation" style="display:block;margin-top:8px">Ketik PULIHKAN untuk mengonfirmasi</label>
                <input id="restore-confirmation" type="text" name="konfirmasi" autocomplete="off" required style="width:100%">
                <button type="submit" class="btn o" style="margin-top:10px">Pulihkan Cadangan</button>
            </form>

            @if(($cadanganList ?? collect())->isNotEmpty())
                <div style="margin-top:14px">
                    <strong>Cadangan terbaru</strong>
                    @foreach($cadanganList as $cadangan)
                        <div class="sub" style="margin:5px 0">
                            {{ $cadangan->nama_file }} · {{ number_format((int) $cadangan->ukuran_byte) }} byte
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Zona Berbahaya --}}
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
                    @foreach($tahunAjaranList ?? [] as $ta)
                        <option value="{{ $ta->id }}" data-confirmation="HAPUS {{ $ta->nama }}">{{ $ta->nama }}</option>
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

{{-- Manajemen Akun Pengguna --}}
<div class="card" style="margin-top:14px">
    <div class="bar" style="margin-bottom:10px">
        <div>
            <h3 style="margin:0">👥 Manajemen Akun Pengguna</h3>
            <p class="sub" style="margin:0">Akun admin dan guru yang memiliki hak akses login ke sistem</p>
        </div>
        <span class="sp"></span>
        <button class="btn a" onclick="document.getElementById('md-akun').style.display='grid'">+ Tambah Akun</button>
    </div>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Peran</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengguna ?? [] as $p)
                <tr>
                    <td><code>{{ $p->username }}</code></td>
                    <td>{{ $p->nama }}</td>
                    <td><span class="bd {{ $p->peran === 'admin' ? '' : 'ok' }}">{{ strtoupper($p->peran) }}</span></td>
                    <td>
                        @if($p->id !== Auth::id())
                        <form method="POST" action="{{ route('admin.setting.akun.hapus', $p->id) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x" style="padding:3px 8px;font-size:11px"
                                onclick="return confirm('Hapus akun {{ $p->username }}?')">Hapus</button>
                        </form>
                        @else
                        <span style="font-size:11px;color:var(--mu)">(Akun Anda)</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:var(--mu)">Tidak ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah Akun --}}
<div id="md-akun" style="display:none;position:fixed;inset:0;background:#0b224088;z-index:9;place-items:center;padding:20px">
    <div class="mbox">
        <div class="bar">
            <h3 style="margin:0">Tambah Akun Pengguna</h3>
            <span class="sp"></span>
            <button class="btn x" onclick="document.getElementById('md-akun').style.display='none'">✕</button>
        </div>
        <form method="POST" action="{{ route('daftar.post') }}">
            @csrf
            <div style="display:grid;gap:12px;margin-top:12px">
                <div><label>Nama Lengkap</label><input type="text" name="nama" style="width:100%" required></div>
                <div><label>Username</label><input type="text" name="username" style="width:100%" required></div>
                <div><label>Peran</label>
                    <select name="peran" style="width:100%">
                        <option value="guru">Guru</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div><label>NIP (khusus Guru)</label><input type="text" name="nip" style="width:100%"></div>
                <div><label>Password Sementara</label><input type="password" name="password" style="width:100%" required></div>
            </div>
            <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn o" onclick="document.getElementById('md-akun').style.display='none'">Batal</button>
                <button type="submit" class="btn a">Buat Akun</button>
            </div>
        </form>
    </div>
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
@endsection
