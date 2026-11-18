@extends('layouts.app')

@section('title', 'Pengaturan Akun — JosBook Guru')

@section('content')
<div class="bar" style="margin-bottom:16px">
    <div>
        <h2 style="margin:0">Pengaturan Akun</h2>
        <p class="sub" style="margin:4px 0 0">Kelola identitas profil pengajar dan keamanan kata sandi akun Anda.</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('guru.log-aktivitas') }}" class="btn o">📜 Log Aktivitas Saya</a>
</div>

@if(session('success'))
    <div class="note ok" style="margin-bottom:16px">
        ✔ {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="note err" style="margin-bottom:16px">
        <b style="display:block;margin-bottom:4px">Terdapat kesalahan pengisian:</b>
        <ul style="margin:0;padding-left:20px">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="g g2" style="gap:20px">
    {{-- Card Profil Guru --}}
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #eef2f6">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--admin-primary-lighter);color:var(--admin-primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px">
                {{ mb_strtoupper(mb_substr($guru->nama ?: 'G', 0, 1)) }}
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Profil Tenaga Pendidik</h3>
                <span class="sub" style="font-size:12px">NIP: {{ $guru->nip ?? '-' }} &middot; {{ $guru->status_kepegawaian ?? 'Guru' }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('guru.setting.profil') }}" style="display:flex;flex-direction:column;flex:1">
            @csrf
            @method('PUT')
            
            <div style="display:grid;gap:12px;flex:1">
                <div>
                    <label for="nama" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nama Lengkap & Gelar</label>
                    <input id="nama" type="text" name="nama" maxlength="150" value="{{ old('nama', $guru->nama) }}" required style="width:100%;font-size:14px;padding:9px 12px;border-radius:9px">
                    <small class="sub" style="display:block;margin-top:5px;font-size:11.5px">Nama ini akan tercantum di rapor siswa dan penugasan mata pelajaran.</small>
                </div>
            </div>

            <div style="margin-top:20px;padding-top:14px;border-top:1px solid #eef2f6">
                <button type="submit" class="btn a">💾 Simpan Perubahan Profil</button>
            </div>
        </form>
    </div>

    {{-- Card Ganti Password --}}
    <div class="card" style="display:flex;flex-direction:column">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #eef2f6">
            <div style="width:44px;height:44px;border-radius:12px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Keamanan Kata Sandi</h3>
                <span class="sub" style="font-size:12px">Perbarui kata sandi secara berkala untuk menjaga akun</span>
            </div>
        </div>

        <form method="POST" action="{{ route('guru.setting.password') }}" style="display:flex;flex-direction:column;flex:1">
            @csrf
            @method('PUT')
            
            <div style="display:grid;gap:14px;flex:1">
                <div>
                    <label for="password-lama" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kata Sandi Saat Ini</label>
                    <input id="password-lama" type="password" name="password_lama" autocomplete="current-password" required style="width:100%;padding:9px 12px;border-radius:9px" placeholder="••••••••">
                </div>
                <div>
                    <label for="password-baru" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kata Sandi Baru</label>
                    <input id="password-baru" type="password" name="password_baru" autocomplete="new-password" minlength="8" required style="width:100%;padding:9px 12px;border-radius:9px" placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label for="password-baru-confirmation" style="display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Konfirmasi Kata Sandi Baru</label>
                    <input id="password-baru-confirmation" type="password" name="password_baru_confirmation" autocomplete="new-password" minlength="8" required style="width:100%;padding:9px 12px;border-radius:9px" placeholder="Ulangi kata sandi baru">
                </div>
            </div>

            <div style="margin-top:20px;padding-top:14px;border-top:1px solid #eef2f6">
                <button type="submit" class="btn a">🔒 Perbarui Kata Sandi</button>
            </div>
        </form>
    </div>
</div>
@endsection
