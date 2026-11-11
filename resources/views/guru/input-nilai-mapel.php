@extends('layouts.app')

@section('title', 'Input Nilai Mapel - JosBook Guru')

@section('content')
<h2>Input Nilai Mapel</h2>
<p class="sub">
    {{ $mapelTerpilih?->nama ?? 'Pilih mapel' }} - hanya mapel dan kelas yang Anda ajar
</p>

<div class="card">
    <form method="GET" action="{{ route('guru.input-nilai-mapel') }}" class="bar">
        <select name="kelas" style="min-width:160px" onchange="this.form.submit()">
            <option value="">Pilih Kelas</option>
            @foreach($kelasList as $kelas)
                <option value="{{ $kelas->nama }}" {{ $kelasTerpilih?->id === $kelas->id ? 'selected' : '' }}>
                    {{ $kelas->nama }}
                </option>
            @endforeach
        </select>

        @if($kelasTerpilih)
            <select name="mapel_id" style="min-width:180px" onchange="this.form.submit()">
                <option value="">Pilih Mapel</option>
                @foreach($mapelList as $mapel)
                    <option value="{{ $mapel->id }}" {{ $mapelTerpilih?->id === $mapel->id ? 'selected' : '' }}>
                        {{ $mapel->nama }}
                    </option>
                @endforeach
            </select>
        @endif

        <select name="semester" style="min-width:160px" onchange="this.form.submit()">
            <option value="Ganjil" {{ $semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
            <option value="Genap" {{ $semester === 'Genap' ? 'selected' : '' }}>Genap</option>
        </select>
    </form>

    @if($kelasTerpilih && $mapelTerpilih)
        <form method="POST" action="{{ route('guru.input-nilai-mapel.store') }}">
            @csrf
            <input type="hidden" name="kelas" value="{{ $kelasTerpilih->nama }}">
            <input type="hidden" name="mapel_id" value="{{ $mapelTerpilih->id }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <div class="tw" style="margin-top:16px">
                <table id="tblNilai">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswa as $riwayat)
                            @php($nilaiTersimpan = $riwayat->nilai->first()?->nilai_angka)
                            <tr>
                                <td><b>{{ $riwayat->siswa->nama_lengkap }}</b></td>
                                <td>{{ $riwayat->siswa->nisn }}</td>
                                <td>
                                    <input
                                        type="number"
                                        name="nilai[{{ $riwayat->id }}]"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="{{ old('nilai.'.$riwayat->id, $nilaiTersimpan) }}"
                                        style="width:100px;text-align:center"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center;color:var(--mu);padding:24px">
                                    Tidak ada siswa aktif pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($siswa->isNotEmpty())
                <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
                    <button type="submit" class="btn">Simpan nilai</button>
                </div>
            @endif
        </form>
    @elseif($kelasTerpilih && $mapelList->isEmpty())
        <p class="note" style="margin-top:16px">Tidak ada mapel yang Anda ajar di kelas ini.</p>
    @endif
</div>
@endsection
