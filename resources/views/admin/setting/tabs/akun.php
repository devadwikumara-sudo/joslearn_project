<div class="card">
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
                @forelse($pengguna as $p)
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
