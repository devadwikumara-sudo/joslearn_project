@extends('layouts.app')

@section('title', 'Setting — JosBook Admin')

@section('content')
<h2>Setting</h2>
<p class="sub">Identitas sekolah, tahun ajaran berjalan, manajemen akun pengguna, dan tempat sampah.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="note err">{{ session('error') }}</div>
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
                    <input type="text" name="nama" style="width:100%" value="{{ $setting->nama ?? 'SMA Negeri 1' }}" required>
                </div>
                <div>
                    <label>NPSN</label>
                    <input type="text" name="npsn" style="width:100%" value="{{ $setting->npsn ?? '' }}">
                </div>
                <div>
                    <label>Alamat Sekolah</label>
                    <textarea name="alamat" style="width:100%;height:65px;font:inherit;border:1px solid var(--ln);border-radius:10px;padding:8px">{{ $setting->alamat ?? '' }}</textarea>
                </div>
                <div>
                    <label>Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah" style="width:100%" value="{{ $setting->nama_kepala_sekolah ?? '' }}">
                </div>
                <div>
                    <label>NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah" style="width:100%" value="{{ $setting->nip_kepala_sekolah ?? '' }}">
                </div>
                <div>
                    <label>Tahun Ajaran Berjalan</label>
                    <select name="tahun_ajaran_aktif_id" style="width:100%">
                        @foreach($tahunAjaranList ?? [] as $ta)
                            <option value="{{ $ta->id }}" {{ $ta->sedang_berjalan ? 'selected' : '' }}>
                                {{ $ta->nama }} {{ $ta->sedang_berjalan ? '(Sedang Berjalan)' : '' }}
                            </option>
                        @endforeach
                    </select>
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

        {{-- Cadangan & Tempat Sampah --}}
        <div class="card">
            <h3>🗑️ Tempat Sampah & Cadangan</h3>
            <p class="sub">Data yang terhapus dapat dipulihkan sewaktu-waktu di Tempat Sampah.</p>
            <div class="bar">
                <a href="{{ route('admin.sampah') }}" class="btn o">Lihat Tempat Sampah</a>
            </div>
        </div>
    </div>
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
@endsection
