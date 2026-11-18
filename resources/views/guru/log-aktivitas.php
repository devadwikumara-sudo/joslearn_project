@extends('layouts.app')

@section('title', 'Log Aktivitas — JosBook Guru')

@section('content')
<div class="bar" style="margin-bottom:16px">
    <div>
        <h2 style="margin:0">Log Aktivitas</h2>
        <p class="sub" style="margin:4px 0 0">Jejak riwayat aktivitas dan perubahan data pada akun Anda.</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('guru.setting') }}" class="btn o">← Kembali ke Pengaturan</a>
</div>

<div class="card">
    <div class="bar" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <b>Catatan Riwayat Sesi & Aktivitas</b>
        </div>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:200px">Waktu & Tanggal</th>
                    <th>Rincian Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:12.5px;color:var(--admin-text-secondary);font-feature-settings:'tnum'">
                            <span style="display:inline-flex;align-items:center;gap:6px;padding:3px 8px;background:#f1f5f9;border-radius:6px;border:1px solid #e2e8f0">
                                🕒 {{ $log->created_at?->format('d M Y · H:i:s') ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <b style="color:var(--admin-text);font-size:13.5px">{{ $log->keterangan ?: $log->aksi }}</b>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center;padding:36px;color:var(--mu)">
                            <div style="font-size:32px;margin-bottom:8px">📋</div>
                            <div style="font-size:14px;font-weight:600">Belum ada catatan aktivitas.</div>
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
@endsection
