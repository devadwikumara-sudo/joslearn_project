<div class="card">
    <div class="bar" style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
        <div>
            <h3 style="margin:0;font-size:17px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Tempat Sampah Data Terhapus
            </h3>
            <p class="sub" style="margin:2px 0 0">Data siswa dan tenaga pendidik yang dihapus tersimpan di sini. Anda dapat memulihkannya kembali atau membersihkannya secara permanen.</p>
        </div>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Informasi Entitas</th>
                    <th style="width:120px">Tipe Data</th>
                    <th style="width:170px">Waktu Dihapus</th>
                    <th style="width:230px;text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $adaData = false; @endphp

                @foreach($siswaTerhapus as $s)
                    @php $adaData = true; @endphp
                    <tr>
                        <td>
                            <strong style="color:var(--admin-text);font-size:13.5px">{{ $s->nama_lengkap }}</strong>
                            <br><small style="color:var(--mu);font-size:11.5px">NISN: <code>{{ $s->nisn }}</code> &middot; NIS: {{ $s->nomor_induk ?: '-' }}</small>
                        </td>
                        <td><span class="bd" style="background:#eff6ff;color:var(--admin-primary);border:1px solid #bfdbfe;font-weight:700">Siswa</span></td>
                        <td style="font-size:12.5px;color:var(--admin-text-secondary);font-feature-settings:'tnum'">{{ $s->deleted_at ? $s->deleted_at->format('d M Y · H:i') : '-' }}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <form method="POST" action="{{ route('admin.sampah.pulihkan') }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="tipe" value="siswa">
                                <input type="hidden" name="id" value="{{ $s->id }}">
                                <button type="submit" class="btn a" style="padding:4px 9px;font-size:11.5px">↩ Pulihkan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.sampah.hapus-permanen') }}" style="display:inline" onsubmit="return confirm('Hapus permanen data siswa {{ $s->nama_lengkap }}? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="tipe" value="siswa">
                                <input type="hidden" name="id" value="{{ $s->id }}">
                                <button type="submit" class="btn x" style="padding:4px 9px;font-size:11.5px">🗑 Hapus Permanen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach

                @foreach($guruTerhapus as $g)
                    @php $adaData = true; @endphp
                    <tr>
                        <td>
                            <strong style="color:var(--admin-text);font-size:13.5px">{{ $g->nama }}</strong>
                            <br><small style="color:var(--mu);font-size:11.5px">NIP: <code>{{ $g->nip ?: '-' }}</code> &middot; NUPTK: {{ $g->nuptk ?: '-' }}</small>
                        </td>
                        <td><span class="bd ok" style="font-weight:700">Guru</span></td>
                        <td style="font-size:12.5px;color:var(--admin-text-secondary);font-feature-settings:'tnum'">{{ $g->deleted_at ? $g->deleted_at->format('d M Y · H:i') : '-' }}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <form method="POST" action="{{ route('admin.sampah.pulihkan') }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="tipe" value="guru">
                                <input type="hidden" name="id" value="{{ $g->id }}">
                                <button type="submit" class="btn a" style="padding:4px 9px;font-size:11.5px">↩ Pulihkan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.sampah.hapus-permanen') }}" style="display:inline" onsubmit="return confirm('Hapus permanen data guru {{ $g->nama }}? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="tipe" value="guru">
                                <input type="hidden" name="id" value="{{ $g->id }}">
                                <button type="submit" class="btn x" style="padding:4px 9px;font-size:11.5px">🗑 Hapus Permanen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach

                @if(!$adaData)
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--mu);padding:36px">
                            <div style="font-size:32px;margin-bottom:8px">🗑</div>
                            <div style="font-size:14px;font-weight:600">Tempat sampah kosong.</div>
                            <p class="sub" style="margin:4px 0 0">Tidak ada data siswa atau guru yang sedang berada di dalam penampungan sampah.</p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
