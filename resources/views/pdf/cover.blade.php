{{-- Sampul Kelas Buku Induk Register Sesuai Prototype (cover(k, ss)) --}}
@php
    $npsn = (string)($sekolah->npsn ?? '20539744');
    $npsnChars = str_split($npsn);
    $angkatan = $angkatan ?? '';
    $tahunLulus = $angkatan ? ((int)$angkatan + 3) : '';
    $namaSekolah = strtoupper($sekolah->nama ?? 'SMA NEGERI 1 REJOSO NGANJUK');
    $alamat = $sekolah->alamat ?? 'Jl. Raya Rejoso No. 1, Rejoso, Nganjuk';
    $kelurahan = $sekolah->desa ?? $sekolah->kelurahan ?? '-';
    $kecamatan = $sekolah->kecamatan ?? '-';
    $kabupaten = $sekolah->kabupaten ?? '-';
    $provinsi = $sekolah->provinsi ?? '-';
@endphp
<div class="pg dc cv">
    {{-- Frame Ornaments --}}
    <div class="cv-frame"></div>
    <div class="cv-frame-inner"></div>
    <div class="cv-corner tl"></div>
    <div class="cv-corner tr"></div>
    <div class="cv-corner bl"></div>
    <div class="cv-corner br"></div>

    {{-- Top National / School Bar --}}
    <div>
        <div class="cvt">
            <span><b>NOMOR POKOK SEKOLAH NASIONAL</b></span>
            <span class="cvt-tag"><b>KELAS {{ $kelasNama ?? '-' }}</b></span>
        </div>

        <div class="npsn-wrap">
            <div class="npsn" style="margin-top:6px">
                @foreach($npsnChars as $c)
                    <i>{{ $c }}</i>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Center Crest & Title --}}
    <div style="margin: auto 0; padding: 20px 0;">
        <div class="cv-crest">
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                <path d="M6 6h10"></path>
                <path d="M6 10h10"></path>
                <path d="M6 14h7"></path>
            </svg>
        </div>

        <div class="cvh">
            BUKU INDUK REGISTER PESERTA DIDIK<br>
            <span style="font-size: 15px; font-weight: 700; color: var(--jb-navy-700); letter-spacing: 1px;">TAHUN ANGKATAN {{ $angkatan ?: '—' }} &bull; TAHUN LULUS {{ $tahunLulus ?: '—' }}</span>
        </div>

        <div class="cvh-sub">KURIKULUM MERDEKA</div>

        <div style="margin-top: 24px;">
            <div class="cvs">{{ $namaSekolah }}</div>
        </div>
    </div>

    {{-- Bottom School Address Card --}}
    <div>
        <div class="cva">
            <table class="cva-table">
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $alamat }}</td>
                </tr>
                <tr>
                    <td>Kelurahan</td>
                    <td>:</td>
                    <td>{{ $kelurahan }}</td>
                </tr>
                <tr>
                    <td>Kecamatan</td>
                    <td>:</td>
                    <td>{{ $kecamatan }}</td>
                </tr>
                <tr>
                    <td>Kab/Kota</td>
                    <td>:</td>
                    <td>{{ $kabupaten }}</td>
                </tr>
                <tr>
                    <td>Provinsi</td>
                    <td>:</td>
                    <td>{{ $provinsi }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
