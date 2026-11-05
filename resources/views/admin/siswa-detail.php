@extends('layouts.app')

@section('title', 'Detail Siswa — JosBook Admin')

@section('content')
<div class="bar">
    <div>
        <h2 style="margin:0">Detail Biodata Siswa</h2>
        <p class="sub" style="margin:4px 0 0">{{ $siswa->nama_lengkap }} · NISN {{ $siswa->nisn }}</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('admin.data-siswa') }}" class="btn o">Kembali</a>
    <a href="{{ route('admin.data-siswa.pdf', $siswa->id) }}" target="_blank" class="btn" style="background:var(--p);color:#fff">⬇ Cetak PDF</a>
    <a href="{{ route('admin.form-pendataan.edit', $siswa->id) }}" class="btn a">Edit biodata</a>
</div>

<div class="card" style="margin-top:14px">
    <h3>Riwayat Kelas</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Tahun Ajaran</th>
                    <th>Kelas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa->riwayatKelas as $riwayat)
                    <tr>
                        <td>{{ $riwayat->tahunAjaran?->nama ?? '-' }}</td>
                        <td>{{ $riwayat->kelas?->nama ?? '-' }}</td>
                        <td>{{ $riwayat->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;padding:18px;color:var(--mu)">Belum ada riwayat kelas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

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
        'Kelulusan dan Keluar Sekolah' => [
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
        ],
    ];
@endphp

@foreach($sections as $sectionTitle => $fields)
    <div class="card" style="margin-top:14px">
        <h3>{{ $sectionTitle }}</h3>
        <div class="tw">
            <table>
                <tbody>
                    @foreach($fields as [$label, $field])
                        <tr>
                            <th style="width:30%">{{ $label }}</th>
                            <td>{{ filled($siswa->{$field}) ? $siswa->{$field} : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<div class="card" style="margin-top:14px">
    <h3>Foto, Dokumen, dan Catatan</h3>
    @if($siswa->foto)
        <img src="{{ route('admin.data-siswa.foto', $siswa->id) }}" alt="Foto {{ $siswa->nama_lengkap }}" style="width:96px;height:128px;object-fit:cover;border-radius:6px">
    @else
        <p class="sub">Belum ada foto.</p>
    @endif
    <div class="tw" style="margin-top:12px">
        <table>
            <tbody>
                <tr><th>Kartu Keluarga</th><td>{{ $siswa->kk_ada ? 'Sudah diterima' : 'Belum diterima' }}</td></tr>
                <tr><th>Akta Kelahiran</th><td>{{ $siswa->akta_ada ? 'Sudah diterima' : 'Belum diterima' }}</td></tr>
                <tr><th>Ijazah SMP</th><td>{{ $siswa->ijazah_smp_ada ? 'Sudah diterima' : 'Belum diterima' }}</td></tr>
                <tr><th>Surat Pindah</th><td>{{ $siswa->surat_pindah_ada ? 'Sudah diterima' : 'Belum diterima' }}</td></tr>
                <tr><th>Catatan</th><td>{{ filled($siswa->catatan) ? $siswa->catatan : '-' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
