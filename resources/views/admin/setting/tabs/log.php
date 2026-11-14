<h3>Log Aktivitas</h3>
<p class="sub">Jejak siapa mengubah apa. {{ $totalLogs }} catatan pada sistem.</p>

<form method="GET" action="{{ route('admin.setting') }}">
    <input type="hidden" name="tab" value="log">
    <div class="bar">
        <select name="lf" onchange="this.form.submit()">
            <option value="">Semua pengguna</option>
            @foreach($whoList as $user)
                <option value="{{ $user }}" {{ $lf === $user ? 'selected' : '' }}>{{ $user }}</option>
            @endforeach
        </select>
        <input type="text" name="lq" placeholder="Cari aktivitas" value="{{ $lq }}">
        <button type="submit" class="btn" style="display:none"></button>
        <span class="sp"></span>
        <a href="{{ route('admin.log-aktivitas.export', ['lf' => $lf, 'lq' => $lq]) }}" class="btn">⬇ Ekspor CSV</a>
    </div>
</form>

<div class="card" style="margin-top:14px">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:160px">Waktu</th>
                    <th style="width:140px">Pengguna</th>
                    <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;color:var(--mu)">
                            {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-' }}
                        </td>
                        <td><b>{{ $log->pengguna->username ?? $log->peran ?? 'Sistem' }}</b></td>
                        <td>{{ $log->keterangan ?: $log->aksi }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada catatan aktivitas yang cocok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="margin-top:14px;display:flex;justify-content:center">
            {{ $logs->links() }}
        </div>
    @endif
</div>
