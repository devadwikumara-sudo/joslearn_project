@extends('layouts.app')

@section('title', 'Detail Siswa — JosBook Admin')

@push('styles')
<style>
    .student-profile{display:grid;gap:14px}
    .student-profile-identity{display:flex;align-items:center;gap:16px;padding:20px;border-radius:16px;background:linear-gradient(125deg,#12355b,#1d4f85);color:#fff}
    .student-profile-photo,.student-profile-avatar{display:grid;place-items:center;width:68px;height:68px;flex:none;border:2px solid #ffffff44;border-radius:18px;object-fit:cover}
    .student-profile-avatar{background:#ffffff20;font-size:26px;font-weight:800}
    .student-profile-identity h4{margin:0 0 5px;font-size:19px}
    .student-profile-identity p{margin:0;color:#d9e7f7;font-size:12px}
    .student-profile-section{padding:18px;border:1px solid #dce5ef;border-radius:15px;background:#fff}
    .student-profile-section h4{margin:0 0 14px;color:#12355b;font-size:13px}
    .student-profile-history{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
    .student-profile-history-item{display:grid;gap:5px;padding:12px;border:1px solid #e2e8f0;border-radius:11px;color:#475569;font-size:12px}
    .student-profile-history-item b{color:#1b2430}
    .student-profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 22px}
    .student-profile-item{min-width:0}
    .student-profile-item span{display:block;margin-bottom:4px;color:#64748b;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    .student-profile-item b{display:block;overflow-wrap:anywhere;color:#1b2430;font-size:12px;white-space:pre-wrap}
    .student-profile-item a{color:#1d4f85}
    .student-profile-note{grid-column:1/-1}
    .student-profile-empty{margin:0;color:#64748b;font-size:12px}
    @media(max-width:600px){.student-profile-grid{grid-template-columns:1fr}.student-profile-identity{align-items:flex-start;padding:15px}.student-profile-identity h4{font-size:16px}}
</style>
@endpush

@section('content')
<div class="bar">
    <div>
        <h2 style="margin:0">Detail Biodata Siswa</h2>
        <p class="sub" style="margin:4px 0 0">{{ $siswa->nama_lengkap }} · NISN {{ $siswa->nisn }}</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('admin.data-siswa') }}" class="btn o">Kembali</a>
    <a href="{{ route('admin.data-siswa.pdf', $siswa->id) }}" target="_blank" rel="noopener" class="btn" style="background:var(--p);color:#fff">⬇ Cetak PDF</a>
    <a href="{{ route('admin.form-pendataan.edit', $siswa->id) }}" class="btn a">Edit biodata</a>
</div>

<div style="margin-top:14px">
    @include('admin.siswa-detail-content', [
        'includeGraduationDetails' => true,
        'canManageAcademicRecords' => true,
        'academicMapelOptions' => $academicMapelOptions,
    ])
</div>
@endsection
