@php
    $npsn = (string) (($sekolah->npsn ?? null) ?: '20538396');
    $npsnChars = str_split($npsn);
    $angkatan = (int) ($angkatan ?: 2023);
    $tahunLulus = $angkatan + 3;
    $namaSekolahSetting = trim((string) ($sekolah->nama ?? ''));
    $namaSekolah = str_contains(strtolower($namaSekolahSetting), 'contoh') || $namaSekolahSetting === ''
        ? 'SMA NEGERI 1 REJOSO NGANJUK'
        : strtoupper($namaSekolahSetting);
    $alamat = ($sekolah->alamat ?? null) ?: 'Jalan Yos Sudarso';
    $kelurahan = ($sekolah->desa ?? null) ?: (($sekolah->kelurahan ?? null) ?: 'Sidokare');
    $kecamatan = ($sekolah->kecamatan ?? null) ?: 'Rejoso';
    $kabupaten = ($sekolah->kabupaten ?? null) ?: 'Nganjuk';
    $provinsi = ($sekolah->provinsi ?? null) ?: 'Jawa Timur';

    $borderPath = public_path('images/cover-border-gold.png');
    $borderUri = is_file($borderPath)
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($borderPath))
        : null;

    $logoJpg = public_path('images/tut-wuri-handayani.jpg');
    $logoUri = is_file($logoJpg)
        ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($logoJpg))
        : null;
@endphp
<div class="pg dc cv cv-dynamic">
    @if($borderUri)
        <img class="cv-ornament-frame" src="{{ $borderUri }}" alt="">
    @else
        <div class="cv-frame-outer"></div>
        <div class="cv-frame-gold"></div>
        <div class="cv-frame-inner"></div>
    @endif

    <div class="cv-inner">
        <div class="cv-top">
            <div class="cvt">
                <div class="cvt-left">
                    <b>NOMOR POKOK SEKOLAH NASIONAL</b>
                    <div class="npsn">
                        @foreach($npsnChars as $c)<i>{{ $c }}</i>@endforeach
                    </div>
                </div>
                <div class="cvt-right">
                    <b>KELAS {{ $kelasNama ?? '-' }}</b>
                </div>
            </div>
        </div>

        <div class="cv-center">
            <div class="cv-crest">
                @if($logoUri)
                    <img src="{{ $logoUri }}" alt="Logo Tut Wuri Handayani">
                @endif
            </div>
            <div class="cvh">
                BUKU INDUK REGISTER PESERTA DIDIK<br>
                TAHUN ANGKATAN {{ $angkatan 
            
            
            
            
            
            
            
            
            
            
            }}<br>
            
                TAHUN LULUS {{ $tahunLulus }}
            </div>
            <div class="cvs">{{ $namaSekolah }}</div>
        </div>

        <div class="cv-bottom">
            <div class="cva-outer">
                <div class="cva">
                    <span class="cva-corner cva-tl"></span>
                    <span class="cva-corner cva-tr"></span>
                    <span class="cva-corner cva-bl"></span>
                    <span class="cva-corner cva-br"></span>
                    <table class="cva-table">
                        <tr><td>Alamat</td><td>:</td><td>{{ $alamat }}</td></tr>
                        <tr><td>Kelurahan</td><td>:</td><td>{{ $kelurahan ?: 'Sidokare' }}</td></tr>
                        <tr><td>Kecamatan</td><td>:</td><td>{{ $kecamatan }}</td></tr>
                        <tr><td>Kab/Kota</td><td>:</td><td>{{ $kabupaten }}</td></tr>
                        <tr><td>Provinsi</td><td>:</td><td>{{ $provinsi }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
