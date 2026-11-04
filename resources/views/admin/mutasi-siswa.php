@extends('layouts.app')

@section('content')
<div class="hero2">
    <div>
        <h2>Mutasi Siswa</h2>
        <p class="sub">Proses perpindahan atau pengeluaran siswa dari sekolah</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="note er">{{ session('error') }}</div>
@endif

{{-- Form Mutasi --}}
<div class="card">
    <h3 style="margin:0 0 1rem;font-size:1rem;color:var(--p)">Proses Mutasi Siswa</h3>
    <form method="POST" action="{{ route('admin.mutasi-siswa.store') }}">
        @csrf
        <div class="fl">
            <div class="sp">
                <label>Siswa</label>
                <select name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    @foreach($siswaAktif as $s)
                    <option value="{{ $s->id }}" {{ old('siswa_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->nama }} — {{ $s->kelas }} ({{ $s->nis }})
                    </option>
                    @endforeach
                </select>
                @error('siswa_id')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp">
                <label>Jenis Mutasi</label>
                <select name="jenis_mutasi" id="jenisMutasi" required onchange="toggleSekolah()">
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Pindah keluar" {{ old('jenis_mutasi')=='Pindah keluar' ? 'selected' : '' }}>Pindah Keluar</option>
                    <option value="Keluar" {{ old('jenis_mutasi')=='Keluar' ? 'selected' : '' }}>Keluar</option>
                </select>
                @error('jenis_mutasi')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp" id="sekolahField">
                <label>Sekolah Tujuan</label>
                <input type="text" name="sekolah_tujuan" placeholder="Nama sekolah tujuan (jika pindah)" value="{{ old('sekolah_tujuan') }}">
                @error('sekolah_tujuan')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp">
                <label>Tanggal Mutasi</label>
                <input type="date" name="tanggal_mutasi" value="{{ old('tanggal_mutasi', date('Y-m-d')) }}" required>
                @error('tanggal_mutasi')<small class="er">{{ $message }}</small>@enderror
            </div>
            <div class="sp" style="grid-column:1/-1">
                <label>Keterangan / Alasan</label>
                <input type="text" name="keterangan" placeholder="Alasan mutasi (opsional)" value="{{ old('keterangan') }}">
            </div>
        </div>
        <div class="bar" style="margin-top:1rem">
            <button type="submit" class="btn a">⇄ Proses Mutasi</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>
</div>

{{-- Riwayat Mutasi --}}
<div class="card wide">
    <h3 style="margin:0 0 1rem;font-size:1rem;color:var(--p)">Riwayat Mutasi</h3>
    <table class="tw">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Jenis</th>
                <th>Sekolah Tujuan</th>
                <th>Tanggal</th>
                <th>Kelas Keluar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayatMutasi as $index => $m)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $m->siswa->nama ?? '-' }}</strong><br>
                    <small style="color:var(--mu)">{{ $m->siswa->nis ?? '' }}</small>
                </td>
                <td>
                    @if($m->jenis_mutasi === 'Pindah keluar')
                        <span class="bd w">Pindah Keluar</span>
                    @else
                        <span class="bd e">Keluar</span>
                    @endif
                </td>
                <td>{{ $m->sekolah_tujuan ?: '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($m->tanggal_mutasi)->format('d M Y') }}</td>
                <td>{{ $m->siswa->kelas ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:2rem;color:var(--mu)">
                    Belum ada riwayat mutasi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function toggleSekolah() {
    const jenis = document.getElementById('jenisMutasi').value;
    const field = document.getElementById('sekolahField');
    field.style.opacity = jenis === 'Keluar' ? '0.4' : '1';
}
toggleSekolah();
</script>
@endsection
