@extends('pdf.layout')

@section('title', 'Cetak Buku Induk & Dokumen Siswa — JosBook')
@section('doc-subtitle', $subTitle ?? 'Buku Induk Register & Laporan Capaian Pembelajaran')

@section('content')
    @php
        $adaKonten = false;
    @endphp

    {{-- 1. Sampul Kelas jika diminta atau jika mencetak satu kelas penuh --}}
    @if(!empty($showCover) && !empty($coverKelasList))
        @foreach($coverKelasList as $cov)
            @include('pdf.cover', [
                'sekolah'   => $sekolah,
                'kelasNama' => $cov['kelas'],
                'angkatan'  => $cov['angkatan'] ?? '',
            ])
            @php $adaKonten = true; @endphp
        @endforeach
    @endif

    {{-- 2. Daftar Dokumen Siswa --}}
    @forelse($siswaItems as $item)
        @php
            $s = $item['siswa'];
            $riwayatList = $item['riwayat']; // Koleksi riwayat kelas per TA
        @endphp

        {{-- Cetak Register Peserta Didik (Hal 1-3: A–I) --}}
        @if(!empty($optBio))
            @include('pdf.register', [
                'siswa'   => $s,
                'sekolah' => $sekolah,
            ])
            @php $adaKonten = true; @endphp
        @endif

        {{-- Cetak Laporan Capaian Pembelajaran (Hal 1-2: Nilai, Kokurikuler, Ekskul, Hadir, Kenaikan) --}}
        @if(!empty($optNilai) || !empty($optHadir) || !empty($optEkskul) || !empty($optNaik))
            @foreach($riwayatList as $rk)
                @include('pdf.capaian', [
                    'siswa'          => $s,
                    'sekolah'        => $sekolah,
                    'riwayatKelas'   => $rk['riwayatKelas'],
                    'taNama'         => $rk['taNama'],
                    'kktp'           => $kktp ?? 75,
                    'filterSem'      => $filterSem ?? 'Semua',
                    'nilaiMap'       => $rk['nilaiMap'] ?? [],
                    'kokurMap'       => $rk['kokurMap'] ?? [],
                    'ekskulList'     => $rk['ekskulList'] ?? [],
                    'hadirMap'       => $rk['hadirMap'] ?? [],
                    'waliKelasNama'  => $rk['waliKelasNama'] ?? null,
                    'waliKelasNip'   => $rk['waliKelasNip'] ?? null,
                    'nextKelasMap'   => $nextKelasMap ?? [],
                    'optNilai'       => $optNilai,
                    'optHadir'       => $optHadir,
                    'optEkskul'      => $optEkskul,
                    'optNaik'        => $optNaik,
                ])
                @php $adaKonten = true; @endphp
            @endforeach
        @endif

    @empty
        <div class="pg dc">
            <div style="text-align:center;padding:80px 20px;color:var(--mu)">
                <h3>Tidak ada data siswa ditemukan</h3>
                <p>Silakan periksa filter kelas, tahun ajaran, atau pilihan siswa yang akan dicetak.</p>
            </div>
        </div>
    @endforelse

    @if(!$adaKonten && count($siswaItems) > 0)
        <div class="pg dc">
            <div style="text-align:center;padding:80px 20px;color:var(--mu)">
                <h3>Tidak ada isi yang dipilih</h3>
                <p>Centang minimal satu pilihan pada toolbar di atas: Biodata, Nilai, Kokurikuler/Kehadiran, Ekskul, atau Kenaikan Kelas.</p>
            </div>
        </div>
    @endif
@endsection

