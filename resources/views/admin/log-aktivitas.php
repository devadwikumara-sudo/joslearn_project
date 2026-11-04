@extends('layouts.app')

@section('title', 'Log Aktivitas — JosBook Admin')

@section('content')
<h2>Log Aktivitas</h2>
<p class="sub">Jejak siapa mengubah apa. {{ $totalLogs }} catatan pada sistem.</p>

<form method="GET" action="{{ route('admin.log-aktivitas') }}">
    <div class="bar">
        <select name="lf" onchange="this.form.submit()">
            <option value="">Semua pengguna</option>
            @foreach($whoList as $u)
            <option value="{{ $u }}" {{ $lf === $u ? 'selected' : '' }}>{{ $u }}</option>
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
                @forelse($logs as $l)
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:var(--mu)">
                        {{ $l->created_at ? $l->created_at->format('Y-m-d H:i:s') : '-' }}
                    </td>
                    <td>
                        <b>{{ $l->pengguna->username ?? $l->peran ?? 'Sistem' }}</b>
                    </td>
                    <td>
                        {{ $l->keterangan ?: $l->aksi }}
                    </td>
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
@endsection
