@extends('layouts.app')

@section('title', 'Beranda Guru - JosBook')

@section('content')
<h2>Beranda</h2>
<p class="sub">Selamat datang, <b>{{ $namaGuru }}</b> · {{ $tanggalHariIni }}</p>

<div class="bar" style="flex-wrap:wrap;margin-bottom:14px">
    @if($mapelDiajar->isNotEmpty())
        <span class="bd ok">Guru {{ $mapelDiajar->pluck('nama')->join(', ') }}</span>
    @endif
    @foreach($kelasWali as $kelas)
        <span class="bd ok">Wali Kelas {{ $kelas->nama }}</span>
    @endforeach
    <span class="bd">{{ $tahunAjaran?->nama ?? 'Tahun ajaran belum ditetapkan' }}</span>
    <span class="bd">{{ $semesterBerjalan ?? 'Semester belum ditetapkan' }}</span>
</div>

@unless($adaPenugasan)
    <div class="note" style="margin-bottom:14px">Belum ada penugasan, hubungi admin.</div>
@endunless

<div class="g g2">
    @if($mapelDiajar->isNotEmpty())
        <div class="card">
            <h3>Guru Mapel</h3>
            <p class="sub">Mata pelajaran: {{ $mapelDiajar->pluck('nama')->join(', ') }}</p>
            <div class="row">
                <div>
                    <b>Kelas diajar</b>
                    <div style="font-size:11px;color:var(--mu)">{{ $kelasDiajar->pluck('nama')->join(', ') }}</div>
                </div>
            </div>
            <a href="{{ route('guru.input-nilai-mapel') }}" class="btn" style="margin-top:12px">Input nilai mapel</a>
        </div>
    @endif

    @foreach($kelasWali as $kelas)
        <div class="card">
            <h3>Wali Kelas {{ $kelas->nama }}</h3>
            <p class="sub">{{ $kelas->jumlah_siswa }} siswa aktif pada tahun ajaran berjalan.</p>
            <a href="{{ route('guru.kenaikan-kelas', ['kelas' => $kelas->nama]) }}" class="btn">Kenaikan kelas</a>
        </div>
    @endforeach
</div>
@endsection
