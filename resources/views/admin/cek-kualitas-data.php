@extends('layouts.app')

@section('title', 'Cek Kualitas Data — JosBook Admin')

@section('content')
<h2>Cek Kualitas Data</h2>
<p class="sub">Pemeriksaan seluruh data sekaligus (format, tanggal, data dobel, data yatim, sinkron antar tahun). Aturan yang sama dipasang di server saat coding.</p>

{{-- 4 Stat Cards --}}
<div class="g g4">
    <div class="card st">
        <span>Kesalahan</span>
        <b style="color:{{ $jumlahKesalahan > 0 ? '#b03a2e' : '#1f7a4d' }}">{{ $jumlahKesalahan }}</b>
    </div>
    <div class="card st">
        <span>Peringatan</span>
        <b>{{ $jumlahPeringatan }}</b>
    </div>
    <div class="card st">
        <span>Siswa diperiksa</span>
        <b>{{ $totalSiswa }}</b>
    </div>
    <div class="card st">
        <span>Baris nilai / hadir / ekskul</span>
        <b>{{ $totalBaris }}</b>
    </div>
</div>

{{-- Bar Filter & Ekspor CSV --}}
<div class="bar" style="margin-top:12px">
    <select onchange="location.href='{{ route('admin.cek-kualitas-data') }}?jenis=' + this.value">
        <option value="semua" {{ $filter === 'semua' ? 'selected' : '' }}>Semua temuan</option>
        <option value="kesalahan" {{ $filter === 'kesalahan' ? 'selected' : '' }}>Kesalahan</option>
        <option value="peringatan" {{ $filter === 'peringatan' ? 'selected' : '' }}>Peringatan</option>
    </select>
    <span class="sp"></span>
    <a href="{{ route('admin.cek-kualitas-data') }}?export=1&jenis={{ $filter }}" class="btn">⬇ Ekspor CSV</a>
</div>

{{-- Card Tabel Temuan --}}
<div class="card" style="margin-top:12px">
    @if(count($masalah) > 0)
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:110px">Jenis</th>
                    <th style="width:130px">Data</th>
                    <th>Siswa / Baris</th>
                    <th>Masalah</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($masalah as $m)
                <tr>
                    <td>
                        <span class="bd {{ $m['jenis'] === 'Kesalahan' ? 'e' : 'w' }}">
                            {{ $m['jenis'] }}
                        </span>
                    </td>
                    <td><b>{{ $m['kolom'] }}</b></td>
                    <td>
                        <strong>{{ $m['siswa'] }}</strong>
                        @if($m['nisn'] && $m['nisn'] !== '-')
                            <br><small style="color:var(--mu)">NISN: {{ $m['nisn'] }}</small>
                        @endif
                    </td>
                    <td>{{ $m['masalah'] }}</td>
                    <td>
                        @if(!empty($m['id']))
                            <a href="{{ route('admin.data-siswa.edit', $m['id']) }}" class="btn a" style="padding:3px 8px;font-size:12px">Perbaiki</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="note ok" style="margin:0">
        ✔ Tidak ada temuan. Seluruh data lolos pemeriksaan.
    </div>
    @endif
</div>
@endsection
