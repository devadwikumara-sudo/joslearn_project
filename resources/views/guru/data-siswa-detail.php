@extends('layouts.app')

@section('title', 'Detail Siswa — JosBook Guru')

@section('content')
@php
    $listRoute = $lingkup === 'wali' ? 'guru.data-siswa.wali' : 'guru.data-siswa';
@endphp
<div class="bar" style="margin-bottom:16px">
    <div>
        <h2 style="margin:0">Detail Siswa</h2>
        <p class="sub" style="margin:4px 0 0">{{ $siswa->nama_lengkap }} · NISN {{ $siswa->nisn }}</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route($listRoute) }}" class="btn o">← Kembali</a>
    <a href="{{ route('guru.data-siswa.pdf', ['id' => $siswa->id, 'tab' => $lingkup]) }}" target="_blank" rel="noopener" class="btn a">⬇ Unduh PDF</a>
</div>

<div class="student-profile" style="display:grid;gap:16px">
    {{-- Identity Hero Banner --}}
    <div style="display:flex;align-items:center;gap:18px;padding:22px;border-radius:18px;background:linear-gradient(135deg,#0e2f56,#1b4f88 55%,#2563eb);color:#fff;box-shadow:0 10px 24px -6px rgba(14,47,86,0.35);position:relative;overflow:hidden">
        <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,0.18) 0%,rgba(255,255,255,0) 70%);border-radius:50%;pointer-events:none"></div>
        
        @if($siswa->foto)
            <img src="{{ route('guru.data-siswa.foto', ['id' => $siswa->id, 'tab' => $lingkup]) }}" alt="Foto {{ $siswa->nama_lengkap }}" style="width:72px;height:72px;border-radius:18px;object-fit:cover;border:3px solid rgba(255,255,255,0.35);box-shadow:0 4px 12px rgba(0,0,0,0.2);flex-shrink:0">
        @else
            <div style="width:72px;height:72px;border-radius:18px;background:rgba(255,255,255,0.18);border:2px solid rgba(255,255,255,0.35);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15);flex-shrink:0">
                {{ mb_strtoupper(mb_substr($siswa->nama_lengkap ?: 'S', 0, 1)) }}
            </div>
        @endif

        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <h3 style="margin:0;font-size:20px;font-weight:800;color:#fff;letter-spacing:-0.02em">{{ $siswa->nama_lengkap }}</h3>
                <span class="bd" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35)">
                    {{ $siswa->riwayatKelas->first()?->kelas?->nama ?? 'Kelas -' }}
                </span>
            </div>
            <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:13px;display:flex;gap:12px;flex-wrap:wrap">
                <span><b>NISN:</b> {{ $siswa->nisn ?? '-' }}</span>
                <span><b>NIS:</b> {{ $siswa->nomor_induk ?? '-' }}</span>
                <span><b>Jenis Kelamin:</b> {{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : ($siswa->jenis_kelamin ?? '-')) }}</span>
            </p>
        </div>
    </div>

    {{-- Detail Biodata Ringkas --}}
    <div class="card" style="margin:0">
        <h3 style="margin:0 0 14px;font-size:15px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Identitas Siswa
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            <div style="background:#f8fafc;padding:12px 14px;border-radius:10px;border:1px solid #eef2f6">
                <span style="display:block;font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px">Tempat, Tanggal Lahir</span>
                <b style="color:var(--admin-text);font-size:13px">{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '-' }}</b>
            </div>
            <div style="background:#f8fafc;padding:12px 14px;border-radius:10px;border:1px solid #eef2f6">
                <span style="display:block;font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px">Agama</span>
                <b style="color:var(--admin-text);font-size:13px">{{ $siswa->agama ?? '-' }}</b>
            </div>
            <div style="background:#f8fafc;padding:12px 14px;border-radius:10px;border:1px solid #eef2f6">
                <span style="display:block;font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px">Orang Tua / Wali</span>
                <b style="color:var(--admin-text);font-size:13px">{{ $siswa->nama_ayah ?? $siswa->nama_ibu ?? $siswa->nama_wali ?? '-' }}</b>
            </div>
            <div style="background:#f8fafc;padding:12px 14px;border-radius:10px;border:1px solid #eef2f6">
                <span style="display:block;font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:3px">No. Handphone Siswa</span>
                <b style="color:var(--admin-text);font-size:13px">{{ $siswa->nomor_hp ?? '-' }}</b>
            </div>
        </div>
    </div>

    {{-- Riwayat Kelas --}}
    <div class="card" style="margin:0">
        <h3 style="margin:0 0 14px;font-size:15px;color:var(--admin-text);display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            Riwayat Kelas
        </h3>
        <div class="tw">
            <table>
                <thead>
                    <tr><th>Tahun Ajaran</th><th>Kelas</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($siswa->riwayatKelas as $riwayat)
                        <tr>
                            <td><b>{{ $riwayat->tahunAjaran?->nama ?? '-' }}</b></td>
                            <td>{{ $riwayat->kelas?->nama ?? '-' }}</td>
                            <td><span class="bd {{ in_array($riwayat->status, ['Aktif', 'Lulus']) ? 'ok' : 'w' }}">{{ $riwayat->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;padding:18px;color:var(--mu)">Belum ada riwayat kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Akademik Content --}}
    @include('partials.siswa-akademik-content', [
        'canManageAcademicRecords' => false,
        'academicManagePermissions' => $academicManagePermissions,
    ])
</div>
@endsection
