@extends('layouts.app')

@section('title', 'Setting - JosBook Guru')

@section('content')
<h2>Setting</h2>
<p class="sub">Kelola nama tampilan dan kata sandi akun Anda.</p>

@if($errors->any())
    <div class="note err" style="margin-bottom:14px">
        <ul style="margin:0;padding-left:20px">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="g g2">
    <div class="card">
        <h3>Nama Tampilan</h3>
        <form method="POST" action="{{ route('guru.setting.profil') }}">
            @csrf
            @method('PUT')
            <label for="nama">Nama</label>
            <input id="nama" type="text" name="nama" maxlength="150" value="{{ old('nama', $guru->nama) }}" required style="width:100%">
            <button type="submit" class="btn a" style="margin-top:12px">Simpan Nama</button>
        </form>
    </div>

    <div class="card">
        <h3>Ganti Kata Sandi</h3>
        <form method="POST" action="{{ route('guru.setting.password') }}">
            @csrf
            @method('PUT')
            <div style="display:grid;gap:12px">
                <div>
                    <label for="password-lama">Kata sandi saat ini</label>
                    <input id="password-lama" type="password" name="password_lama" autocomplete="current-password" required style="width:100%">
                </div>
                <div>
                    <label for="password-baru">Kata sandi baru</label>
                    <input id="password-baru" type="password" name="password_baru" autocomplete="new-password" minlength="8" required style="width:100%">
                </div>
                <div>
                    <label for="password-baru-confirmation">Konfirmasi kata sandi baru</label>
                    <input id="password-baru-confirmation" type="password" name="password_baru_confirmation" autocomplete="new-password" minlength="8" required style="width:100%">
                </div>
            </div>
            <button type="submit" class="btn a" style="margin-top:12px">Simpan Kata Sandi</button>
        </form>
    </div>
</div>
@endsection
