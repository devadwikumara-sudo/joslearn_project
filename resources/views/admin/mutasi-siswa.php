@extends('layouts.app')

@section('title', 'Mutasi Siswa — JosBook Admin')

@section('content')
<h2>Mutasi Siswa</h2>
<p class="sub">Catat siswa pindah / keluar.</p>

{{-- Card 1: Form Catat Mutasi (Sesuai Prototype mutasiPg) --}}
<div class="card">
    <form action="{{ route('admin.mutasi-siswa.store') }}" method="POST">
        @csrf
        <div class="fl">
            <div>
                <label>Siswa</label>
                <select name="siswa_id" required>
                    <option value="">— Pilih siswa aktif —</option>
                    @foreach($siswaAktif as $s)
                        @php
                            $kName = $s->riwayatKelas->first() ? $s->riwayatKelas->first()->kelas : '-';
                        @endphp
                        <option value="{{ $s->id }}">{{ $s->nama_lengkap }} · {{ $kName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Jenis</label>
                <select name="jenis">
                    <option value="Pindah keluar">Pindah keluar</option>
                    <option value="Keluar">Keluar</option>
                </select>
            </div>
            <div>
                <label>Sekolah tujuan</label>
                <input type="text" name="sekolah_tujuan" placeholder="Nama sekolah tujuan">
            </div>
            <div>
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div class="bar" style="margin-top:14px">
            <button type="submit" class="btn a">Catat mutasi</button>
        </div>
    </form>
</div>

{{-- Card 2: Riwayat Mutasi & Pindahan Masuk --}}
<div class="card" style="margin-top:14px">
    <h3>Riwayat mutasi &amp; pindahan masuk</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Tujuan / asal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatMutasi as $m)
                @php
                    $rk = $m->riwayatKelas->first();
                    $kName = $rk ? $rk->kelas : ($m->kelas_keluar ?? '-');
                    $isMasuk = ($rk && $rk->status === 'Aktif') && ($m->jenis_registrasi === 'Pindahan');
                    $labelJenis = $isMasuk ? 'Pindahan masuk' : ($rk ? $rk->status : 'Pindah keluar');
                    $tujuanAsal = $isMasuk ? ($m->pindahan_dari_sekolah ?? $m->sekolah_asal ?? '-') : ($m->sekolah_tujuan ?? '-');
                @endphp
                <tr>
                    <td><b>{{ $m->nama_lengkap }}</b></td>
                    <td>{{ $kName }}</td>
                    <td><span class="bd w">{{ $labelJenis }}</span></td>
                    <td>{{ $tujuanAsal }}</td>
                    <td>
                        <a href="{{ route('admin.data-siswa.pdf', $m->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">⬇ PDF</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada riwayat siswa pindah atau keluar pada tahun ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
