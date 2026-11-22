<div class="card">
    <div class="bar" style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
        <div>
            <h3 style="margin:0;font-size:17px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Manajemen Akun Pengguna &amp; Akses
            </h3>
            <p class="sub" style="margin:3px 0 0">Lihat data akun administrator dan tenaga pendidik yang terdaftar di JosBook</p>
        </div>
        <span class="sp"></span>
        <button class="btn a" onclick="document.getElementById('md-akun').style.display='flex'">+ Tambah Akun Baru</button>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:200px">Username Akses</th>
                    <th>Nama Pengguna</th>
                    <th>Email</th>
                    <th style="width:140px">Peran (Role)</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="width:110px;text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengguna as $p)
                    <tr>
                        <td>
                            <code style="font-size:12.5px;font-weight:700;color:var(--admin-primary);padding:3px 8px;background:#eff6ff;border-radius:6px;border:1px solid #bfdbfe">{{ $p->username }}</code>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="width:30px;height:30px;border-radius:8px;background:{{ $p->peran === 'admin' ? '#eff6ff' : '#ecfdf5' }};color:{{ $p->peran === 'admin' ? 'var(--admin-primary)' : '#059669' }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0">
                                    {{ mb_strtoupper(mb_substr($p->nama ?: 'U', 0, 1)) }}
                                </div>
                                <b style="color:var(--admin-text);font-size:13.5px">{{ $p->nama }}</b>
                            </div>
                            @if($p->peran === 'admin')
                                <form method="POST" action="{{ route('admin.setting.akun.nama', $p->id) }}" style="display:flex;gap:5px;margin-top:8px">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="nama" value="{{ $p->getRawOriginal('nama') }}" maxlength="150" required placeholder="Nama admin" aria-label="Nama admin {{ $p->username }}" style="min-width:120px;padding:4px 7px;font-size:12px">
                                    <button type="submit" class="btn o" style="padding:4px 7px;font-size:11px">Simpan</button>
                                </form>
                            @endif
                        </td>
                        <td>{{ $p->email ?: '-' }}</td>
                        <td>
                            <span class="bd {{ $p->peran === 'admin' ? '' : 'ok' }}" style="font-weight:700;font-size:11px">
                                {{ $p->peran === 'admin' ? '🛡 ADMIN' : '👨‍🏫 GURU' }}
                            </span>
                        </td>
                        <td>
                            <span class="bd {{ $p->aktif ? 'ok' : 'w' }}">{{ $p->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td>{{ $p->terakhir_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td>
                        <td style="text-align:right">
                            @if($p->id !== Auth::id())
                                <form method="POST" action="{{ route('admin.setting.akun.hapus', $p->id) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn x" style="padding:4px 8px;font-size:11.5px"
                                        onclick="return confirm('Hapus akses akun {{ $p->username }}?')">🗑 Hapus</button>
                                </form>
                            @else
                                <span class="bd" style="font-size:11px;background:#f1f5f9;color:var(--mu)">(Akun Anda)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--mu);padding:32px">
                            Tidak ada akun pengguna yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="bar" style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
        <div>
            <h3 style="margin:0;font-size:17px;color:var(--admin-text)">Akun Siswa Mobile</h3>
            <p class="sub" style="margin:3px 0 0">Data akun siswa yang terdaftar di aplikasi mobile JosBook</p>
        </div>
    </div>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>NISN</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($akunSiswa as $akun)
                    <tr>
                        <td>{{ $akun->siswa?->nama_lengkap ?? '-' }}</td>
                        <td><code>{{ $akun->username }}</code></td>
                        <td>{{ $akun->email ?: '-' }}</td>
                        <td><span class="bd {{ $akun->aktif ? 'ok' : 'w' }}">{{ $akun->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $akun->terakhir_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--mu);padding:32px">Belum ada akun siswa mobile terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($akunSiswa->hasPages())
        <div style="margin-top:16px;display:flex;justify-content:center">
            {{ $akunSiswa->links() }}
        </div>
    @endif
</div>

{{-- MODAL TAMBAH AKUN --}}
<div id="md-akun" style="display:none;position:fixed;inset:0;background:rgba(9,24,43,0.65);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;padding:20px">
    <div class="card" style="max-width:520px;width:100%;margin:auto;background:#fff;border-radius:18px;box-shadow:0 20px 40px -10px rgba(9,24,43,0.4);border:1px solid #dce5ef;padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
            <div>
                <h3 style="margin:0;font-size:17px;color:var(--admin-text)">Tambah Akun Pengguna</h3>
                <p class="sub" style="margin:2px 0 0">Buat akun untuk admin atau staf pengajar baru</p>
            </div>
            <button type="button" class="btn x" onclick="document.getElementById('md-akun').style.display='none'" style="padding:4px 8px;font-size:14px">✕</button>
        </div>

        <form method="POST" action="{{ route('daftar.post') }}">
            @csrf
            <div style="display:grid;gap:12px">
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nama Lengkap *</label>
                    <input type="text" name="nama" style="width:100%" required placeholder="Nama lengkap staf / guru">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Username Login *</label>
                    <input type="text" name="username" style="width:100%" required placeholder="mis. budi_santoso">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Peran Akses (Role) *</label>
                    <select name="peran" style="width:100%">
                        <option value="guru">Guru (Akses Nilai, Kehadiran, &amp; Ekskul)</option>
                        <option value="admin">Administrator (Akses Penuh Sistem)</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">NIP (Khusus Guru)</label>
                    <input type="text" name="nip" style="width:100%" placeholder="Kosongkan jika bukan PNS/Guru">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kata Sandi Sementara *</label>
                    <input type="password" name="password" style="width:100%" required placeholder="Minimal 8 karakter">
                </div>
            </div>

            <div class="bar" style="margin-top:20px;padding-top:14px;border-top:1px solid #eef2f6">
                <span class="sp"></span>
                <button type="button" class="btn o" onclick="document.getElementById('md-akun').style.display='none'">Batal</button>
                <button type="submit" class="btn a">💾 Daftarkan Akun</button>
            </div>
        </form>
    </div>
</div>
