@extends('layouts.app')

@section('title', 'Log Aktivitas - JosBook Admin')

@section('content')
<h2>Log Aktivitas</h2>
<p class="sub">Rekam jejak semua aktivitas pengguna di sistem</p>

<div class="card">
    <div class="bar">
        <select style="min-width:140px" id="filterUser">
            <option value="">Semua Pengguna</option>
            @foreach($penggunaList ?? [] as $p)
                <option value="{{ $p->id }}">{{ $p->nama ?? $p->username }}</option>
            @endforeach
        </select>
        <select style="min-width:140px" id="filterAksi">
            <option value="">Semua Aksi</option>
            <option value="login">Login</option>
            <option value="import">Import</option>
            <option value="edit">Edit Data</option>
            <option value="hapus">Hapus</option>
            <option value="cetak">Cetak Laporan</option>
        </select>
        <input type="date" id="filterTanggal">
        <span class="sp"></span>
        <input type="text" placeholder="🔍 Cari log…" id="qLog" oninput="filterLog(this.value)" style="min-width:180px">
    </div>

    <div class="tw">
        <table id="tblLog" class="wide">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Aksi</th>
                    <th>Keterangan</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs ?? [] as $log)
                <tr>
                    <td style="white-space:nowrap;font-size:11px">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td>
                    <td><b>{{ $log->pengguna->nama ?? $log->pengguna->username ?? '-' }}</b></td>
                    <td><span class="bd">{{ strtoupper($log->pengguna->peran ?? '-') }}</span></td>
                    <td>
                        <span class="bd {{ in_array($log->aksi, ['hapus','gagal']) ? 'e' : ($log->aksi === 'import' ? 'w' : 'ok') }}">
                            {{ $log->aksi }}
                        </span>
                    </td>
                    <td>{{ $log->keterangan }}</td>
                    <td style="font-family:monospace;font-size:11px">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--mu);padding:24px">Belum ada log aktivitas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($logs) && method_exists($logs, 'links'))
    <div class="pgn">{{ $logs->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function filterLog(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tblLog tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush

