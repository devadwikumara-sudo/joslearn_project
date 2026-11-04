@extends('layouts.app')

@section('title', 'Data Guru — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Data Guru</h2>
        <p class="sub">Total {{ count($guru) }} guru terdaftar dalam sistem</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="note er">{{ session('error') }}</div>
@endif

<div class="bar">
    <form method="GET" action="{{ route('admin.data-guru') }}" style="display:contents">
        <input type="text" name="search" placeholder="Cari NIP atau nama guru..." value="{{ request('search') }}">
        <button type="submit" class="btn">Cari</button>
    </form>
    <button class="btn a" onclick="toggleForm()">+ Tambah Guru</button>
</div>

{{-- Form tambah/edit guru --}}
<div class="card" id="formGuru" style="{{ $errors->any() || isset($editGuru) ? '' : 'display:none' }}">
    <div class="note" style="border-left:4px solid var(--a);background:#e8f0fb;color:#1a4a8a;margin-bottom:1rem">
        @if(isset($editGuru))
            ✏️ Mode Edit — mengubah data guru <strong>{{ $editGuru->nama }}</strong>
        @else
            ℹ️ Isi form berikut untuk menambahkan data guru baru ke sistem.
        @endif
    </div>
    <form method="POST" action="{{ isset($editGuru) ? route('admin.data-guru.update', $editGuru->id) : route('admin.data-guru.store') }}">
        @csrf
        @if(isset($editGuru))
            @method('PUT')
        @endif
        <div class="fl">
            <div class="sp">
                <label>NIP</label>
                <input type="text" name="nip" placeholder="Nomor Induk Pegawai" value="{{ old('nip', $editGuru->nip ?? '') }}">
                @error('nip')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" placeholder="Nama lengkap guru" value="{{ old('nama_lengkap', $editGuru->nama ?? '') }}" required>
                @error('nama_lengkap')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="L" {{ old('jenis_kelamin', $editGuru->jenis_kelamin ?? '') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="P" {{ old('jenis_kelamin', $editGuru->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>
            <div class="sp">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $editGuru->tempat_lahir ?? '') }}">
            </div>
            <div class="sp">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $editGuru->tanggal_lahir ?? '') }}">
            </div>
            <div class="sp">
                <label>Nomor HP</label>
                <input type="text" name="no_hp" placeholder="08xxxxxxxx" value="{{ old('no_hp', $editGuru->nomor_hp ?? '') }}">
            </div>
            <div class="sp">
                <label>Email</label>
                <input type="email" name="email" placeholder="guru@sekolah.sch.id" value="{{ old('email', $editGuru->email ?? '') }}">
            </div>
            <div class="sp">
                <label>Status Kepegawaian</label>
                <select name="golongan">
                    <option value="PNS" {{ old('golongan', $editGuru->status_kepegawaian ?? '') == 'PNS' ? 'selected' : '' }}>PNS</option>
                    <option value="PPPK" {{ old('golongan', $editGuru->status_kepegawaian ?? '') == 'PPPK' ? 'selected' : '' }}>PPPK</option>
                    <option value="GTT" {{ old('golongan', $editGuru->status_kepegawaian ?? '') == 'GTT' ? 'selected' : '' }}>GTT / Honorer</option>
                </select>
            </div>
        </div>
        <div class="bar" style="margin-top:1rem">
            <button type="submit" class="btn a">{{ isset($editGuru) ? 'Simpan Perubahan' : 'Tambah Guru' }}</button>
            <a href="{{ route('admin.data-guru') }}" class="btn o">Batal</a>
        </div>
    </form>
</div>

<div class="card wide">
    <table class="tw">
        <thead>
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Status Kepegawaian</th>
                <th>Nomor HP</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($guru as $index => $g)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $g->nip ?? '-' }}</td>
                <td><strong>{{ $g->nama ?? '-' }}</strong></td>
                <td>
                    <span class="bd {{ ($g->status_kepegawaian ?? '') === 'PNS' || ($g->status_kepegawaian ?? '') === 'PPPK' ? 'ok' : '' }}">
                        {{ $g->status_kepegawaian ?? '-' }}
                    </span>
                </td>
                <td>{{ $g->nomor_hp ?? '-' }}</td>
                <td>
                    <a href="{{ route('admin.data-guru.edit', $g->id) }}" class="btn o">Edit</a>
                    <form method="POST" action="{{ route('admin.data-guru.destroy', $g->id) }}" style="display:inline" onsubmit="return confirm('Hapus data guru {{ $g->nama }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn x">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:2rem;color:var(--mu)">
                    Belum ada data guru
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function toggleForm() {
    const f = document.getElementById('formGuru');
    f.style.display = f.style.display === 'none' ? '' : 'none';
}
</script>
@endsection
