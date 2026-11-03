@extends('layouts.app')

@section('title', 'Setting - JosBook Admin')

@section('content')
<h2>Setting</h2>
<p class="sub">Konfigurasi sistem dan manajemen akun pengguna</p>

<div class="g g2">
    {{-- Informasi Sekolah --}}
    <div class="card">
        <h3>🏫 Profil Sekolah</h3>
        <form method="POST" action="{{ route('admin.setting.update') }}">
            @csrf @method('PUT')
            <div style="display:grid;gap:12px">
                <div><label>Nama Sekolah</label><input type="text" name="nama_sekolah" style="width:100%" value="{{ $setting->nama_sekolah ?? 'SMA Negeri 1' }}"></div>
                <div><label>NPSN</label><input type="text" name="npsn" style="width:100%" value="{{ $setting->npsn ?? '' }}"></div>
                <div><label>Alamat</label><textarea name="alamat" style="width:100%;height:70px;font:inherit;border:1px solid var(--ln);border-radius:10px;padding:8px">{{ $setting->alamat ?? '' }}</textarea></div>
                <div><label>Kepala Sekolah</label><input type="text" name="kepala_sekolah" style="width:100%" value="{{ $setting->kepala_sekolah ?? '' }}"></div>
                <div><label>NIP Kepala Sekolah</label><input type="text" name="nip_kepala" style="width:100%" value="{{ $setting->nip_kepala ?? '' }}"></div>
                <div><label>Tahun Ajaran Aktif</label>
                    <select name="tahun_ajaran_aktif" style="width:100%">
                        <option {{ ($setting->tahun_ajaran_aktif ?? '') === '2024/2025' ? 'selected' : '' }}>2024/2025</option>
                        <option {{ ($setting->tahun_ajaran_aktif ?? '') === '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                    </select>
                </div>
                <div><label>Kurikulum</label>
                    <select name="kurikulum" style="width:100%">
                        <option {{ ($setting->kurikulum ?? '') === 'Merdeka' ? 'selected' : '' }}>Merdeka</option>
                        <option {{ ($setting->kurikulum ?? '') === 'K13' ? 'selected' : '' }}>K13</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:14px"><button type="submit" class="btn">💾 Simpan Perubahan</button></div>
        </form>
    </div>

    {{-- Manajemen Akun --}}
    <div class="card">
        <h3>👥 Manajemen Akun Pengguna</h3>
        <div class="bar" style="margin-bottom:10px">
            <span class="sp"></span>
            <button class="btn" onclick="document.getElementById('md-akun').style.display='grid'">+ Tambah Akun</button>
        </div>
        <div class="tw">
            <table>
                <thead><tr><th>Username</th><th>Nama</th><th>Peran</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($pengguna ?? [] as $p)
                    <tr>
                        <td><code>{{ $p->username }}</code></td>
                        <td>{{ $p->nama }}</td>
                        <td><span class="bd {{ $p->peran === 'admin' ? '' : 'ok' }}">{{ strtoupper($p->peran) }}</span></td>
                        <td>
                            @if($p->id !== Auth::id())
                            <form method="POST" action="{{ route('admin.setting.akun.hapus', $p->id) }}" style="display:inline">
                                @csrf @method('DELETE')
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
</div>

{{-- Modal Tambah Akun --}}
<div id="md-akun" style="display:none;position:fixed;inset:0;background:#0b224088;z-index:9;place-items:center;padding:20px">
    <div class="mbox">
        <div class="bar"><h3 style="margin:0">Tambah Akun Pengguna</h3><span class="sp"></span>
            <button class="btn x" onclick="document.getElementById('md-akun').style.display='none'">✕</button>
        </div>
        <form method="POST" action="{{ route('daftar.post') }}">
            @csrf
            <div style="display:grid;gap:12px">
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
                <button type="submit" class="btn">Buat Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection

