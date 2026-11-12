{{-- Laporan Hasil Capaian Pembelajaran Peserta Didik Sesuai Prototype (lap(s, ta, o)) --}}
@php
    $blnIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgi = function($val) use ($blnIndo) {
        if (!$val || $val === '-') return '-';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$val, $m)) {
            $mIdx = (int)$m[2];
            return ((int)$m[3]) . ' ' . ($blnIndo[$mIdx] ?? '') . ' ' . $m[1];
        }
        return $val;
    };
    $v = function($val) {
        $val = trim((string)$val);
        return ($val === '' || $val === '-') ? '-' : $val;
    };

    $kktp = $kktp ?? 75;
    $kelasNama = $riwayatKelas?->kelas?->nama ?? $siswa->kelas_aktif ?? '-';
    $tingkat = explode('-', $kelasNama)[0] ?? 'X';
    $isXII = strtoupper($tingkat) === 'XII';
    $semesterStart = ['X' => 1, 'XI' => 3, 'XII' => 5][strtoupper($tingkat)] ?? 1;
    $termSemesterNumbers = [
        'Ganjil' => $semesterStart,
        'Genap' => $semesterStart + 1,
    ];

    $semList = ($filterSem && $filterSem !== 'Semua') ? [$filterSem] : ['Ganjil', 'Genap'];
    $numSem = count($semList);

    // Kategori Mapel Kurikulum Merdeka per Tingkat
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
                'sos' => 'Sosiologi',
                'eko' => 'Ekonomi',
                'geo' => 'Geografi',
                'sjl' => 'Sejarah Tingkat Lanjut',
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

    // Title text
    $ttl = ($optNilai && $optHadir && $optEkskul && $optNaik)
        ? 'II. LAPORAN HASIL CAPAIAN PEMBELAJARAN'
        : 'II. LAPORAN HASIL CAPAIAN PEMBELAJARAN' .
            (!$optNilai && !$optNaik && $optEkskul && !$optHadir ? ' · EKSTRAKURIKULER' :
            (!$optNilai && !$optNaik && !$optEkskul && $optHadir ? ' · KEHADIRAN & KOKURIKULER' :
            ($optNilai && !$optHadir && !$optEkskul && !$optNaik ? ' · NILAI' : '')));
@endphp

{{-- ==================== HALAMAN 1 (Identitas, Intrakurikuler, Kokurikuler) ==================== --}}
<div class="pg dc">
    <div class="ptt" style="text-align:left">
        {{ $ttl }}<br>
        &nbsp;&nbsp;&nbsp;&nbsp;PESERTA DIDIK TAHUN AJARAN {{ $taNama }}
        @if(count($semList) === 1)
            · SEMESTER {{ $termSemesterNumbers[$semList[0]] ?? $semList[0] }}
        @endif
    </div>

    <div class="fh">A. IDENTITAS PESERTA DIDIK</div>
    <div class="fr in"><span></span><span>Nama Peserta Didik</span><span class="v">: {{ $v($siswa->nama_lengkap) }}</span></div>
    <div class="fr in"><span></span><span>NIS</span><span class="v">: {{ $v($siswa->nomor_induk) }}</span></div>
    <div class="fr in"><span></span><span>NISN</span><span class="v">: {{ $v($siswa->nisn) }}</span></div>

    @if($optNilai)
        <div class="fh" style="margin-top:14px">B. INTRAKURIKULER</div>
        <table class="lt">
            <thead>
                <tr>
                    <th rowspan="2" style="width:30px">No</th>
                    <th rowspan="2">Mata Pelajaran</th>
                    <th rowspan="2" style="width:50px">KKTP</th>
                    <th colspan="{{ $numSem }}">Kelas {{ $kelasNama }}</th>
                </tr>
                <tr>
                    @foreach($semList as $sm)
                        <th style="width:80px">Semester {{ $termSemesterNumbers[$sm] ?? $sm }}</th>
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
                        <tr>
                            <td>{{ $idx++ }}</td>
                            <td class="l">{{ $mNama }}</td>
                            <td>{{ $kktp }}</td>
                            @foreach($semList as $sm)
                                @php
                                    $score = $nilaiMap[$sm][$mCode] ?? null;
                                    if (is_numeric($score)) {
                                        $totalNilaiPerSem[$sm] += (float)$score;
                                        $countNilaiPerSem[$sm]++;
                                    } else {
                                        $adaMissing = true;
                                    }
                                @endphp
                                <td>{{ is_numeric($score) ? $score : 'X' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach

                <tr>
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

        @if($adaMissing)
            <div style="font-size:10px;color:#555;margin-top:4px">
                Keterangan:<br>&nbsp;X : Tidak diajarkan pada semester tersebut
            </div>
        @endif
    @endif

    @if($optHadir)
        <div class="fh" style="margin-top:14px">C. KOKURIKULER</div>
        @foreach($semList as $sm)
            <div class="kk">
                <div>No &nbsp; Deskripsi Nilai Kokurikuler (Semester {{ $termSemesterNumbers[$sm] ?? $sm }})</div>
                <div>{{ $termSemesterNumbers[$sm] ?? $sm }} &nbsp; {{ $kokurMap[$sm] ?? 'Terlaksana dengan baik sesuai dengan Projek Penguatan Profil Pelajar Pancasila (P5).' }}</div>
            </div>
        @endforeach
    @endif
</div>

{{-- ==================== HALAMAN 2 (Ekskul, Kehadiran, Kenaikan Kelas & TTD) ==================== --}}
@if($optEkskul || $optHadir || $optNaik)
<div class="pg dc">
    @if($optEkskul)
        <div class="fh">D. EKSTRAKURIKULER</div>
        <table class="lt">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Ekstrakurikuler</th>
                    @foreach($semList as $sm)
                        <th style="width:100px">Semester {{ $termSemesterNumbers[$sm] ?? $sm }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($ekskulList ?? [] as $i => $ek)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="l">{{ $ek['nama'] }}</td>
                        @foreach($semList as $sm)
                            <td>{{ $ek['nilai'][$sm] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td class="l">Pramuka (Wajib)</td>
                        @foreach($semList as $sm)
                            <td>B</td>
                        @endforeach
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if($optHadir)
        <div class="fh" style="margin-top:14px">E. KETIDAKHADIRAN</div>
        <table class="lt">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th>Keterangan</th>
                    @foreach($semList as $sm)
                        <th style="width:100px">Semester {{ $termSemesterNumbers[$sm] ?? $sm }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td class="l">Sakit</td>
                    @foreach($semList as $sm)
                        <td>{{ (int)($hadirMap[$sm]['sakit'] ?? 0) }} hari</td>
                    @endforeach
                </tr>
                <tr>
                    <td>2</td>
                    <td class="l">Izin</td>
                    @foreach($semList as $sm)
                        <td>{{ (int)($hadirMap[$sm]['izin'] ?? 0) }} hari</td>
                    @endforeach
                </tr>
                <tr>
                    <td>3</td>
                    <td class="l">Tanpa Keterangan (Alpha)</td>
                    @foreach($semList as $sm)
                        <td>{{ (int)($hadirMap[$sm]['alpa'] ?? 0) }} hari</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif

    @if($optNaik)
        @php
            $keputusan = $riwayatKelas?->keputusan_kenaikan;
            if (!$keputusan) {
                $keputusan = $isXII ? 'LULUS' : 'NAIK';
            }
            $pilihanKenaikan = in_array(strtoupper($keputusan), ['LULUS', 'TIDAK LULUS'], true)
                ? ['LULUS', 'TIDAK LULUS']
                : ['NAIK', 'TIDAK NAIK'];
            $kelasTujuan = $kelasTujuanNama ?? ($nextKelasMap[$kelasNama] ?? '-');
            $tglKeputusan = $riwayatKelas?->tanggal_keputusan_kenaikan ?? now()->toDateString();
            $namaSekolah = strtoupper($sekolah->nama ?? 'SMA NEGERI 1 NGANJUK');
            $namaKepsek = $sekolah->nama_kepala_sekolah ?? 'Drs. SUGIARTO, M.Pd.';
            $nipKepsek = $sekolah->nip_kepala_sekolah ?? '19670815 199412 1 003';
            $namaWali = $waliKelasNama ?? '..................................';
            $nipWali = $waliKelasNip ?? '';
        @endphp

        <div class="fh" style="margin-top:14px">F. KENAIKAN KELAS</div>
        <div class="fr in">
            <span></span>
            <span>Keputusan</span>
            <span class="v">: Berdasarkan Hasil Belajar Semester I dan II, Tahun Ajaran {{ $taNama }}</span>
        </div>
        <div class="fr in">
            <span></span>
            <span>Peserta Didik ditetapkan</span>
            <span>: 
                @foreach($pilihanKenaikan as $idx => $pk)
                    @if(strtoupper($keputusan) === $pk)
                        <b>{{ $pk }}</b>
                    @else
                        <s>{{ $pk }}</s>
                    @endif
                    @if($idx < count($pilihanKenaikan) - 1) / @endif
                @endforeach
            </span>
        </div>
        <div class="fr in">
            <span></span>
            <span>Dari</span>
            <span class="v">: {{ $isXII ? $namaSekolah : 'Kelas ' . $kelasNama . ' Ke Kelas ' . $kelasTujuan }}</span>
        </div>
        <div class="fr in">
            <span></span>
            <span>Tanggal</span>
            <span class="v">: {{ $tgi($tglKeputusan) }}</span>
        </div>

        <div class="ttd" style="margin-top:36px">
            <div>
                Wali Kelas,<br><br><br><br>
                <u>{{ $namaWali }}</u><br>
                @if($nipWali) NIP. {{ $nipWali }} @endif
            </div>
            @if($isXII)
            <div>
                Mengetahui,<br>
                Kepala Sekolah,<br><br><br><br>
                <u>{{ $namaKepsek }}</u><br>
                NIP. {{ $nipKepsek }}
            </div>
            @endif
        </div>
    @endif
</div>
@endif
