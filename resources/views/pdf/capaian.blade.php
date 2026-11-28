{{-- Laporan Hasil Capaian Pembelajaran Peserta Didik (100% Sesuai Format Sekolah) --}}
@php
    $blnIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgi = function($val) use ($blnIndo) {
        if (!$val || $val === '-') return '-';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$val, $m)) {
            $mIdx = (int)$m[2];
            $day = str_pad((string)(int)$m[3], 2, '0', STR_PAD_LEFT);
            return $day . ' ' . ($blnIndo[$mIdx] ?? '') . ' ' . $m[1];
        }
        return $val;
    };
    $v = function($val) {
        $val = trim((string)$val);
        return ($val === '' || $val === '-') ? '-' : $val;
    };

    $kktp = $kktp ?? 75;
    $kelasNama = $riwayatKelas?->kelas?->nama ?? $siswa->kelas_aktif ?? '-';
    $tingkat = \App\Support\Semester::tingkatDariNamaKelas($kelasNama) ?? 'X';
    $isXII = strtoupper($tingkat) === 'XII';
    $namaSekolahKelulusan = 'SMA NEGERI 1 REJOSO';

    $semList = isset($slotSemesters)
        ? $slotSemesters
        : (($filterSem && $filterSem !== 'Semua') ? [$filterSem] : ['Ganjil', 'Genap']);
    $emptySemesters = $emptySemesters ?? [];
    $isEmptySlot = count($semList) > 0 && count(array_intersect($semList, $emptySemesters)) === count($semList);
    $numSem = count($semList);

    // Kategori Mapel Kurikulum Merdeka per Tingkat (urutan sesuai standar sekolah)
    $curMapel = $tingkatMapel ?? [
        'X' => [
            'I. Mata Pelajaran Wajib' => [
                'agm' => 'Pendidikan Agama dan Budi Pekerti',
                'pnc' => 'Pendidikan Pancasila',
                'ind' => 'Bahasa Indonesia',
                'mtk' => 'Matematika',
                'ipa' => 'Ilmu Pengetahuan Alam (IPA)',
                'ips' => 'Ilmu Pengetahuan Sosial (IPS)',
                'big' => 'Bahasa Inggris',
                'pjk' => 'Pendidikan Jasmani Olahraga dan Kesehatan',
                'sbd' => 'Seni Budaya dan Prakarya/Seni dan Budaya',
                'inf' => 'Informatika',
            ],
            'II. Muatan Lokal' => [
                'jaw' => 'Bahasa Jawa',
            ],
        ],
        'XI' => [
            'I. Mata Pelajaran Wajib' => [
                'agm' => 'Pendidikan Agama dan Budi Pekerti',
                'pnc' => 'Pendidikan Pancasila',
                'ind' => 'Bahasa Indonesia',
                'mtk' => 'Matematika',
                'ipa' => 'Ilmu Pengetahuan Alam (IPA)',
                'ips' => 'Ilmu Pengetahuan Sosial (IPS)',
                'big' => 'Bahasa Inggris',
                'pjk' => 'Pendidikan Jasmani Olahraga dan Kesehatan',
                'sej' => 'Sejarah',
                'sbd' => 'Seni Budaya dan Prakarya/Seni dan Budaya',
            ],
            'II. Mata Pelajaran Pilihan' => [
                'mtl' => 'Matematika Tingkat Lanjut',
                'fis' => 'Fisika',
                'kim' => 'Kimia',
                'bio' => 'Biologi',
                'geo' => 'Geografi',
                'sjl' => 'Sejarah Tingkat Lanjut',
                'sos' => 'Sosiologi',
                'eko' => 'Ekonomi',
                'bil' => 'Bahasa Inggris Tingkat Lanjut',
                'inf' => 'Informatika',
                'pkw' => 'Prakarya dan Kewirausahaan',
            ],
            'III. Muatan Lokal' => [
                'jaw' => 'Bahasa Jawa',
            ],
        ],
    ];
    $layout = $curMapel[$tingkat] ?? ($curMapel['XI'] ?? []);

    // Kelas X: C. Kokurikuler di Halaman 1; Kelas XI/XII: C. Kokurikuler di Halaman 2
    $kokurOnPage1 = strtoupper($tingkat) === 'X';
@endphp

{{-- ==================== HALAMAN 1 (Identitas, Intrakurikuler[, Kokurikuler untuk Kelas X]) ==================== --}}
<div class="pg dc capaian">
    <div class="capaian-title">
        II. LAPORAN HASIL CAPAIAN PEMBELAJARAN
        <span>PESERTA DIDIK TAHUN AJARAN {{ $taNama }}</span>
    </div>

    <div class="capaian-fh">A. IDENTITAS PESERTA DIDIK</div>
    <div class="fr"><span></span><span>Nama Peserta Didik</span><span class="c">:</span><span class="v">{{ $v($siswa->nama_lengkap) }}</span></div>
    <div class="fr"><span></span><span>NIS</span><span class="c">:</span><span class="v">{{ $v($siswa->nomor_induk) }}</span></div>
    <div class="fr"><span></span><span>NISN</span><span class="c">:</span><span class="v">{{ $v($siswa->nisn) }}</span></div>

    @if($optNilai)
        <div class="capaian-fh">B. INTRAKURIKULER</div>
        <table class="lt lt-intra">
            <thead>
                <tr>
                    <th rowspan="2" style="width:28px">No</th>
                    <th rowspan="2">Mata Pelajaran</th>
                    <th rowspan="2" style="width:48px">KKTP</th>
                    <th colspan="{{ $numSem }}">Kelas {{ $kelasNama }}</th>
                </tr>
                <tr>
                    @foreach($semList as $sm)
                        <th style="width:76px">Semester {{ $sm === 'Genap' ? 2 : 1 }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $adaMissing = false;
                    $totalNilaiPerSem = array_fill_keys($semList, 0);
                    $countNilaiPerSem = array_fill_keys($semList, 0);
                @endphp

                @foreach($layout as $groupName => $mapelGroup)
                    <tr class="gh">
                        <td colspan="{{ 3 + $numSem }}">{{ $groupName }}</td>
                    </tr>
                    @php $idx = 1; @endphp
                    @foreach($mapelGroup as $mCode => $mNama)
                        <tr @if($idx % 2 === 0) class="alt" @endif>
                            <td>{{ $idx++ }}</td>
                            <td class="l">{{ $mNama }}</td>
                            <td>{{ $kktp }}</td>
                            @foreach($semList as $sm)
                                @php
                                    $score = $nilaiMap[$sm][$mCode] ?? null;
                                    if (is_numeric($score)) {
                                        $totalNilaiPerSem[$sm] += (float)$score;
                                        $countNilaiPerSem[$sm]++;
                                    } elseif (!in_array($sm, $emptySemesters, true)) {
                                        $adaMissing = true;
                                    }
                                @endphp
                                <td>{{ in_array($sm, $emptySemesters, true) ? 'X' : (is_numeric($score) ? $score : 'X') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach

                <tr class="summary-row">
                    <td colspan="3"><b>Rata-rata</b></td>
                    @foreach($semList as $sm)
                        @php
                            $avg = ($countNilaiPerSem[$sm] > 0)
                                ? number_format($totalNilaiPerSem[$sm] / $countNilaiPerSem[$sm], 2, ',', '.')
                                : '-';
                        @endphp
                        <td><b>{{ $avg }}</b></td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        @if($adaMissing || count($emptySemesters) > 0)
            <div class="capaian-ket">
                <b>Keterangan:</b><br>
                <span><i>X : Tidak diajarkan pada semester tersebut</i></span>
            </div>
        @endif
    @endif

    @if($optHadir && $kokurOnPage1)
        <div class="capaian-fh">C. KOKURIKULER</div>
        @foreach($semList as $kkIdx => $sm)
            <table class="lt kk-table">
                <thead>
                    <tr>
                        <th style="width:28px">No</th>
                        <th>Deskripsi Nilai Kokurikuler</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="kk-no">{{ $sm === 'Genap' ? 2 : 1 }}</td>
                        <td class="l kk-desc">{{ in_array($sm, $emptySemesters, true) ? '-' : ($kokurMap[$sm] ?? 'Terlaksana dengan baik sesuai dengan Projek Penguatan Profil Pelajar Pancasila (P5).') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif
</div>

{{-- ==================== HALAMAN 2 ([Kokurikuler XI/XII,] Ekskul, Kehadiran, Kenaikan Kelas & TTD) ==================== --}}
@if($optEkskul || $optHadir || $optNaik)
<div class="pg dc capaian">
    @if($optHadir && !$kokurOnPage1)
        <div class="capaian-fh" style="margin-top:0">C. KOKURIKULER</div>
        @foreach($semList as $kkIdx => $sm)
            <table class="lt kk-table">
                <thead>
                    <tr>
                        <th style="width:28px">No</th>
                        <th>Deskripsi Nilai Kokurikuler</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="kk-no">{{ $sm === 'Genap' ? 2 : 1 }}</td>
                        <td class="l kk-desc">{{ in_array($sm, $emptySemesters, true) ? '-' : ($kokurMap[$sm] ?? 'Terlaksana dengan baik sesuai dengan Projek Penguatan Profil Pelajar Pancasila (P5).') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    @if($optEkskul)
        <div class="capaian-fh">D. EKSTRAKURIKULER</div>
        <table class="lt lt-blue-head">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th>Ekstrakurikuler</th>
                    @foreach($semList as $sm)
                        <th style="width:105px">Semester {{ $sm === 'Genap' ? 2 : 1 }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $ekRows = $ekskulList ?? [];
                    $totalEkRows = max(3, count($ekRows));
                @endphp
                @for($i = 0; $i < $totalEkRows; $i++)
                    @php $ek = $ekRows[$i] ?? null; @endphp
                    <tr @if($i % 2 === 1) class="alt" @endif>
                        <td>{{ $ek ? ($i + 1) : '' }}&nbsp;</td>
                        <td class="l">{{ $ek['nama'] ?? '' }}</td>
                        @foreach($semList as $sm)
                            <td>{{ $ek ? ($ek['nilai'][$sm] ?? '-') : '' }}</td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    @endif

    @if($optHadir)
        <div class="capaian-fh">E. KETIDAKHADIRAN</div>
        <table class="lt lt-blue-head">
            <thead>
                <tr>
                    <th style="width:28px">No</th>
                    <th>Keterangan</th>
                    @foreach($semList as $sm)
                        <th style="width:105px">Semester {{ $sm === 'Genap' ? 2 : 1 }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td class="l">Sakit</td>
                    @foreach($semList as $sm)
                        <td>{{ in_array($sm, $emptySemesters, true) ? '-' : (int)($hadirMap[$sm]['sakit'] ?? 0).' hari' }}</td>
                    @endforeach
                </tr>
                <tr class="alt">
                    <td>2</td>
                    <td class="l">Izin</td>
                    @foreach($semList as $sm)
                        <td>{{ in_array($sm, $emptySemesters, true) ? '-' : (int)($hadirMap[$sm]['izin'] ?? 0).' hari' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>3</td>
                    <td class="l">Alpha</td>
                    @foreach($semList as $sm)
                        <td>{{ in_array($sm, $emptySemesters, true) ? '-' : (int)($hadirMap[$sm]['alpa'] ?? 0).' hari' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif

    @if($optNaik)
        @php
            $keputusan = $riwayatKelas?->keputusan_kenaikan;
            $pilihanKenaikan = in_array(strtoupper((string) $keputusan), ['LULUS', 'TIDAK LULUS'], true)
                ? ['LULUS', 'TIDAK LULUS']
                : ['NAIK', 'TIDAK NAIK'];
            $kelasTujuan = $kelasTujuanNama ?? '-';
            $tglKeputusan = $riwayatKelas?->tanggal_keputusan_kenaikan;
            $namaKepsek = $sekolah->nama_kepala_sekolah ?? '-';
            $nipKepsek = $sekolah->nip_kepala_sekolah ?? '-';
            $namaWali = $waliKelasNama ?? '..................................';
            $nipWali = $waliKelasNip ?? '';
        @endphp

        <div class="capaian-fh">F. KENAIKAN KELAS</div>
        <div class="kenaikan-keputusan"><b>Keputusan :</b></div>
        <table class="kenaikan-table">
            <tr>
                <td class="kl">Berdasarkan Hasil Belajar Semester I dan II,</td>
                <td class="kv">Tahun Ajaran {{ $taNama }}</td>
            </tr>
            <tr>
                <td class="kl">Peserta Didik ditetapkan :</td>
                <td class="kv">
                    @if($isEmptySlot || !filled($keputusan))
                        -
                    @else
                        <b>
                            @foreach($pilihanKenaikan as $idx => $pk)
                                @if(strtoupper((string) $keputusan) === $pk)
                                    <b>{{ $pk }}</b>
                                @else
                                    <s>{{ $pk }}</s>
                                @endif
                                @if($idx < count($pilihanKenaikan) - 1) / @endif
                            @endforeach
                        </b>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="kl">Dari :</td>
                <td class="kv"><b>{{ $isXII ? $namaSekolahKelulusan : $kelasNama . ' ke ' . $kelasTujuan }}</b></td>
            </tr>
            <tr>
                <td class="kl">Tanggal :</td>
                <td class="kv"><b>{{ $tgi($tglKeputusan) }}</b></td>
            </tr>
        </table>

        <div class="ttd {{ $isXII ? 'ttd-dual' : 'ttd-single' }}">
            @if($isXII)
                <div class="ttd-left">
                    <div class="ttd-title">Mengetahui,<br>Kepala Sekolah,</div>
                    <div class="ttd-name">{{ $namaKepsek }}</div>
                    <div class="ttd-nip">NIP. {{ $nipKepsek }}</div>
                </div>
                <div class="ttd-right">
                    <div class="ttd-title">&nbsp;<br>Wali Kelas,</div>
                    <div class="ttd-name">{{ $namaWali }}</div>
                    @if($nipWali) <div class="ttd-nip">NIP. {{ $nipWali }}</div> @endif
                </div>
            @else
                <div class="ttd-right">
                    <div class="ttd-title">Wali Kelas,</div>
                    <div class="ttd-name">{{ $namaWali }}</div>
                    @if($nipWali) <div class="ttd-nip">NIP. {{ $nipWali }}</div> @endif
                </div>
            @endif
        </div>
    @endif
</div>
@endif
