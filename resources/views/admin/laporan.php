@extends('layouts.app')

@section('title', 'Laporan & Statistik — JosBook Admin')

@section('content')
<h2>Laporan &amp; Statistik</h2>
<p class="sub">Rekap {{ $ta }}</p>

<div class="bar">
    <form method="GET" action="{{ route('admin.laporan') }}" style="display:inline-flex;gap:8px">
        <select name="ta" onchange="this.form.submit()">
            @foreach($taList as $item)
            <option value="{{ $item }}" {{ $ta === $item ? 'selected' : '' }}>Tahun Ajaran {{ $item }}</option>
            @endforeach
        </select>
    </form>
    <span class="sp"></span>
    <a href="{{ route('admin.laporan.cetak', ['ta' => $ta]) }}" target="_blank" class="btn a">🖨 Cetak laporan</a>
</div>

<div class="card" style="margin-top:14px">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Siswa</th>
                    <th>Rata-rata nilai</th>
                    <th>Ketidakhadiran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr>
                    <td><b>{{ $r['kelas'] }}</b></td>
                    <td>{{ $r['siswa_count'] }}</td>
                    <td>{{ $r['rata_nilai'] }}</td>
                    <td>{{ $r['kehadiran'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                        Tidak ada data kelas pada tahun ajaran ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
