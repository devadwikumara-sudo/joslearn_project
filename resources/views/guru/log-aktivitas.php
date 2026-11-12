@extends('layouts.app')

@section('title', 'Log Aktivitas - JosBook Guru')

@section('content')
<h2>Log Aktivitas</h2>
<p class="sub">Riwayat aktivitas pada akun Anda.</p>

<div class="card" style="margin-top:14px">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:180px">Waktu</th>
                    <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;color:var(--mu)">
                            {{ $log->created_at?->format('Y-m-d H:i:s') ?? '-' }}
                        </td>
                        <td>{{ $log->keterangan ?: $log->aksi }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada catatan aktivitas.
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
@endsection
