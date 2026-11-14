@php
    $sections = [
        'Identitas Siswa' => [
            ['Nama Lengkap', 'nama_lengkap'],
            ['Nama Panggilan', 'nama_panggilan'],
            ['NISN', 'nisn'],
            ['NIS', 'nomor_induk'],
            ['Jenis Kelamin', 'jenis_kelamin'],
            ['Tempat Lahir', 'tempat_lahir'],
            ['Tanggal Lahir', 'tanggal_lahir'],
            ['Agama', 'agama'],
            ['Kewarganegaraan', 'kewarganegaraan'],
            ['NIK', 'nik'],
            ['Anak Ke-', 'anak_ke'],
            ['Jumlah Saudara', 'jumlah_saudara'],
            ['Status Yatim/Piatu', 'status_yatim'],
            ['Bahasa Sehari-hari', 'bahasa_sehari_hari'],
        ],
        'Alamat dan Kontak' => [
            ['Alamat Jalan', 'alamat_jalan'],
            ['RT/RW', 'alamat_rt_rw'],
            ['Desa/Kelurahan', 'alamat_desa'],
            ['Kecamatan', 'alamat_kecamatan'],
            ['Kabupaten/Kota', 'alamat_kabupaten'],
            ['Provinsi', 'alamat_provinsi'],
            ['Kode Pos', 'alamat_kode_pos'],
            ['Nomor HP', 'nomor_hp'],
            ['Tinggal Bersama', 'tempat_tinggal'],
            ['Jarak ke Sekolah (KM)', 'jarak_ke_sekolah_km'],
        ],
        'Kesehatan' => [
            ['Golongan Darah', 'golongan_darah'],
            ['Tinggi Badan (cm)', 'tinggi_badan_cm'],
            ['Berat Badan (kg)', 'berat_badan_kg'],
            ['Penyakit', 'penyakit'],
            ['Kelainan Fisik', 'kelainan_fisik'],
        ],
        'Pendidikan dan Registrasi' => [
            ['Sekolah Asal', 'sekolah_asal'],
            ['Tanggal Ijazah SMP', 'tanggal_ijazah_smp'],
            ['Nomor Ijazah SMP', 'nomor_ijazah_smp'],
            ['Lama Belajar (Tahun)', 'lama_belajar_tahun'],
            ['Jenis Registrasi', 'jenis_registrasi'],
            ['Sekolah Asal Pindahan', 'pindahan_dari_sekolah'],
            ['Alasan Pindah', 'alasan_pindah'],
            ['Kelas Diterima', 'kelas_diterima'],
            ['Rombel Diterima', 'rombel_diterima'],
            ['Tanggal Diterima', 'tanggal_diterima'],
        ],
        'Data Ayah' => [
            ['Nama Ayah', 'nama_ayah'],
            ['Agama Ayah', 'agama_ayah'],
            ['Kewarganegaraan Ayah', 'kewarganegaraan_ayah'],
            ['Pendidikan Ayah', 'pendidikan_ayah'],
            ['Pekerjaan Ayah', 'pekerjaan_ayah'],
            ['Penghasilan Ayah', 'penghasilan_ayah'],
            ['Nomor HP Ayah', 'nomor_hp_ayah'],
            ['Keadaan Ayah', 'keadaan_ayah'],
        ],
        'Data Ibu' => [
            ['Nama Ibu', 'nama_ibu'],
            ['Agama Ibu', 'agama_ibu'],
            ['Kewarganegaraan Ibu', 'kewarganegaraan_ibu'],
            ['Pendidikan Ibu', 'pendidikan_ibu'],
            ['Pekerjaan Ibu', 'pekerjaan_ibu'],
            ['Penghasilan Ibu', 'penghasilan_ibu'],
            ['Nomor HP Ibu', 'nomor_hp_ibu'],
            ['Keadaan Ibu', 'keadaan_ibu'],
        ],
        'Alamat Orang Tua' => [
            ['Jalan', 'ortu_alamat_jalan'],
            ['RT/RW', 'ortu_alamat_rt_rw'],
            ['Desa/Kelurahan', 'ortu_alamat_desa'],
            ['Kecamatan', 'ortu_alamat_kecamatan'],
            ['Kabupaten/Kota', 'ortu_alamat_kabupaten'],
            ['Provinsi', 'ortu_alamat_provinsi'],
            ['Kode Pos', 'ortu_alamat_kode_pos'],
        ],
        'Data Wali' => [
            ['Nama Wali', 'nama_wali'],
            ['Agama Wali', 'agama_wali'],
            ['Kewarganegaraan Wali', 'kewarganegaraan_wali'],
            ['Pendidikan Wali', 'pendidikan_wali'],
            ['Pekerjaan Wali', 'pekerjaan_wali'],
            ['Penghasilan Wali', 'penghasilan_wali'],
            ['Nomor HP Wali', 'nomor_hp_wali'],
            ['Alamat Jalan Wali', 'wali_alamat_jalan'],
            ['RT/RW Wali', 'wali_alamat_rt_rw'],
            ['Desa/Kelurahan Wali', 'wali_alamat_desa'],
            ['Kecamatan Wali', 'wali_alamat_kecamatan'],
            ['Kabupaten/Kota Wali', 'wali_alamat_kabupaten'],
            ['Provinsi Wali', 'wali_alamat_provinsi'],
            ['Kode Pos Wali', 'wali_alamat_kode_pos'],
        ],
    ];

    if ($includeGraduationDetails) {
        $sections['Kelulusan dan Keluar Sekolah'] = [
            ['Tanggal Keluar', 'tanggal_keluar'],
            ['Alasan Keluar', 'alasan_keluar'],
            ['Kelas Keluar', 'kelas_keluar'],
            ['Sekolah Tujuan', 'sekolah_tujuan'],
            ['Nomor Peserta PSAJ', 'nomor_peserta_psaj'],
            ['Nomor SKL', 'nomor_skl'],
            ['Nomor Ijazah SMA', 'nomor_ijazah_sma'],
            ['Tanggal Ijazah SMA', 'tanggal_ijazah_sma'],
            ['Nomor Transkrip', 'nomor_transkrip'],
            ['Tahun Lulus', 'tahun_lulus'],
            ['Kegiatan Setelah Lulus', 'kegiatan_setelah_lulus'],
        ];
    }

    $lampiranBerkas = [
        'kk' => ['Kartu Keluarga', 'kk_ada'],
        'akta' => ['Akta Kelahiran', 'akta_ada'],
        'ijazah_smp' => ['Ijazah SMP', 'ijazah_smp_ada'],
        'surat_pindah' => ['Surat Pindah', 'surat_pindah_ada'],
    ];
@endphp

<div class="student-profile">
    <div class="student-profile-identity">
        @if($siswa->foto)
            <img src="{{ route('admin.data-siswa.foto', $siswa->id) }}" alt="Foto {{ $siswa->nama_lengkap }}" class="student-profile-photo">
        @else
            <div class="student-profile-avatar">{{ mb_strtoupper(mb_substr($siswa->nama_lengkap ?: 'S', 0, 1)) }}</div>
        @endif
        <div>
            <h4>{{ $siswa->nama_lengkap ?: 'Nama siswa belum diisi' }}</h4>
            <p>NISN {{ $siswa->nisn ?: '-' }} <span aria-hidden="true">·</span> NIS {{ $siswa->nomor_induk ?: '-' }}</p>
        </div>
    </div>

        @php
            $academicHistories = $siswa->relationLoaded('riwayatKelasAkademik')
                ? $siswa->riwayatKelasAkademik
                : $siswa->riwayatKelas;
            $academicHistories = $academicHistories->sortBy('tahun_ajaran_id');
        @endphp
        <section class="student-profile-section">
            <h4>Riwayat Kelas</h4>
            <div class="student-profile-history">
                @forelse($academicHistories as $riwayat)
                <div class="student-profile-history-item">
                    <b>{{ $riwayat->tahunAjaran?->nama ?? 'Tahun ajaran tidak tersedia' }}</b>
                    <span>{{ $riwayat->kelas?->nama ?? 'Kelas belum ditetapkan' }}</span>
                    <span class="bd">{{ $riwayat->status ?: 'Belum diatur' }}</span>
                </div>
            @empty
                <p class="student-profile-empty">Belum ada riwayat kelas.</p>
            @endforelse
        </div>
    </section>

    @include('partials.siswa-akademik-content', [
        'canManageAcademicRecords' => $canManageAcademicRecords ?? false,
        'academicManagePermissions' => $academicManagePermissions ?? [],
        'academicMapelOptions' => $academicMapelOptions ?? collect(),
    ])

    @foreach($sections as $sectionTitle => $fields)
        <section class="student-profile-section">
            <h4>{{ $sectionTitle }}</h4>
            <div class="student-profile-grid">
                @foreach($fields as [$label, $field])
                    <div class="student-profile-item">
                        <span>{{ $label }}</span>
                        <b>{{ filled($siswa->{$field}) ? $siswa->{$field} : '-' }}</b>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="student-profile-section">
        <h4>Foto, Dokumen, dan Catatan</h4>
        <div class="student-profile-grid">
            @foreach($lampiranBerkas as $jenis => [$label, $statusField])
                @php($berkas = $siswa->berkas->firstWhere('jenis', $jenis))
                <div class="student-profile-item">
                    <span>{{ $label }}</span>
                    @if($berkas)
                        <b><a href="{{ route('admin.data-siswa.berkas', [$siswa->id, $jenis]) }}">{{ $berkas->nama_file }}</a></b>
                    @else
                        <b>{{ $siswa->{$statusField} ? 'Sudah diterima, file belum diunggah' : 'Belum diterima' }}</b>
                    @endif
                </div>
            @endforeach
            <div class="student-profile-item student-profile-note">
                <span>Catatan</span>
                <b>{{ filled($siswa->catatan) ? $siswa->catatan : '-' }}</b>
            </div>
        </div>
    </section>
</div>
