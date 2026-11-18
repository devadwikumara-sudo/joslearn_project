<div class="bar" style="margin-bottom:14px;flex-wrap:wrap;gap:12px">
    <div>
        <h3 style="margin:0;font-size:17px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Audit Trail &amp; Log Aktivitas Sistem
        </h3>
        <p class="sub" style="margin:3px 0 0">Jejak rekam riwayat perubahan dan aktivitas pengguna. Tercatat <b>{{ number_format($totalLogs) }}</b> entri.</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.setting') }}" class="bar" style="margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <input type="hidden" name="tab" value="log">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <select name="lf" onchange="this.form.submit()" style="font-size:13px;font-weight:700;padding:7px 12px" aria-label="Saring Pengguna">
            <option value="">Semua Pengguna</option>
            @foreach($whoList as $user)
                <option value="{{ $user }}" {{ $lf === $user ? 'selected' : '' }}>{{ $user }}</option>
            @endforeach
        </select>
        <input type="text" name="lq" placeholder="🔍 Cari rincian aktivitas..." value="{{ $lq }}" style="min-width:240px;padding:7px 12px;font-size:13px">
        <button type="submit" class="btn" style="display:none"></button>
    </div>
    <span class="sp"></span>
    <a href="{{ route('admin.log-aktivitas.export', ['lf' => $lf, 'lq' => $lq]) }}" class="btn o">⬇ Ekspor CSV Log</a>
</form>

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:190px">Waktu &amp; Tanggal</th>
                    <th style="width:160px">Pelaksana</th>
                    <th>Rincian Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;color:var(--admin-text-secondary);font-feature-settings:'tnum'">
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0">
                                🕒 {{ $log->created_at ? $log->created_at->format('d M Y · H:i:s') : '-' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:inline-flex;align-items:center;gap:6px">
                                <span class="bd" style="font-size:11px;font-weight:700;background:#eff6ff;color:var(--admin-primary);border:1px solid #bfdbfe">
                                    {{ $log->pengguna->username ?? $log->peran ?? 'Sistem' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <b style="color:var(--admin-text);font-size:13px">{{ $log->keterangan ?: $log->aksi }}</b>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;padding:36px;color:var(--mu)">
                            <div style="font-size:32px;margin-bottom:8px">📋</div>
                            <div style="font-size:14px;font-weight:600">Belum ada catatan aktivitas yang cocok dengan kriteria pencarian.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="margin-top:16px;display:flex;justify-content:center">
            {{ $logs->links() }}
        </div>
    @endif
</div>
