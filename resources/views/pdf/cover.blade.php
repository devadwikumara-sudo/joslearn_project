{{-- Sampul Kelas Buku Induk Register Sesuai Prototype (cover(k, ss)) --}}
@php
    $npsn = (string)($sekolah->npsn ?? '20538338');
    $npsnChars = str_split($npsn);
    $angkatan = $angkatan ?? '';
    $tahunLulus = $angkatan ? ((int)$angkatan + 3) : '';
    $namaSekolah = strtoupper($sekolah->nama ?? 'SMA NEGERI 1 NGANJUK');
    $alamat = $sekolah->alamat ?? 'Jl. Panglima Sudirman No. 77';
    $kelurahan = $sekolah->desa ?? $sekolah->kelurahan ?? 'Mangundikaran';
    $kecamatan = $sekolah->kecamatan ?? 'Nganjuk';
    $kabupaten = $sekolah->kabupaten ?? 'Kab. Nganjuk';
    $provinsi = $sekolah->provinsi ?? 'Jawa Timur';
@endphp
<div class="pg dc cv">
    <div class="cvt">
        <b>NOMOR POKOK SEKOLAH NASIONAL</b>
        <b>KELAS {{ $kelasNama ?? '-' }}</b>
    </div>

    <div class="npsn" style="margin-top:6px">
        @foreach($npsnChars as $c)
            <i>{{ $c }}</i>
        @endforeach
    </div>

    <div class="cvh">
        BUKU INDUK REGISTER PESERTA DIDIK<br>
        TAHUN ANGKATAN {{ $angkatan ?: '—' }}<br>
        TAHUN LULUS {{ $tahunLulus ?: '—' }}
    </div>

    <div class="cvs">{{ $namaSekolah }}</div>

    <div class="cva">
        Alamat &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $alamat }}<br>
        Kelurahan : {{ $kelurahan }}<br>
        Kecamatan : {{ $kecamatan }}<br>
        Kab/Kota &nbsp;: {{ $kabupaten }}<br>
        Provinsi &nbsp;&nbsp;&nbsp;: {{ $provinsi }}
    </div>
</div>

