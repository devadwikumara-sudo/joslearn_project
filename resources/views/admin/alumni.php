@extends('layouts.app')

@section('title', 'Alumni & Kontrol Induk — JosBook Admin')

@section('content')
<h2>Alumni &amp; Kontrol Induk</h2>
<p class="sub">Registrasi masuk dan keluar (Kontrol Induk), kelulusan, dan arsip alumni dalam satu tempat · {{ $totalFiltered }} data · klik nama untuk biodata lengkap, nilai, dan unduhan</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.alumni') }}">
    <div class="bar">
        <select name="aj" onchange="this.form.submit()">
            @foreach($JN as $item)
            <option value="{{ $item }}" {{ $aj === $item ? 'selected' : '' }}>{{ $item }}</option>
            @endforeach
        </select>
        <select name="ay" onchange="this.form.submit()">
            <option value="">Semua tahun</option>
            @foreach($ys as $y)
            <option value="{{ $y }}" {{ (string)$ay === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <input type="text" name="aq" placeholder="Cari nama / induk / NISN" value="{{ $aq }}">
        <button type="submit" class="btn" style="display:none"></button>
        <span class="sp"></span>
        <a href="{{ route('admin.alumni.export', ['aj' => $aj, 'ay' => $ay, 'aq' => $aq]) }}" class="btn">⬇ Ekspor Excel (Kontrol Induk + biodata)</a>
    </div>
</form>

<div class="note">
    <b>Lihat</b> hanya untuk melihat. <b>✏ Edit</b> membuka editor lengkap: biodata, Kontrol Induk (registrasi, keluar, SKL, ijazah), nilai, kehadiran, dan ekskul. Data pribadi berlaku ke semua tahun ajaran siswa yang sama. Geser tabel ke kanan untuk kolom lain (SKL, ijazah, transkrip, dst.); tombol aksi tetap terlihat di sisi kanan.
</div>

<div class="card wide">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Induk</th>
                    <th>Nama Peserta Didik</th>
                    <th>NISN</th>
                    <th>Jenis Registrasi</th>
                    <th>Tanggal Masuk</th>
                    <th>Masuk Kelas</th>
                    <th>Sekolah Asal SMP</th>
                    <th>Keluar Karena</th>
                    <th>Tanggal Keluar</th>
                    <th>Keluar Kelas</th>
                    <th>Sekolah Tujuan</th>
                    <th>Nomor Peserta PSAJ</th>
                    <th>Nomor SKL</th>
                    <th>Nomor Ijazah Nasional</th>
                    <th>Tgl Pengesahan Ijazah</th>
                    <th>Nomor Transkrip Nilai</th>
                    <th>Melanjutkan Studi / Bekerja</th>
                    <th>Tahun Lulus</th>
                    <th>Status</th>
                    <th>Kelengkapan</th>
                    <th style="min-width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alumni as $idx => $s)
                @php
                    $latestRiwayat = $s->riwayatKelas->first();
                    $status = $s->tahun_lulus ? 'Lulus' : ($latestRiwayat->status ?? ($s->alasan_keluar ?: 'Alumni'));
                    $statusClass = $status === 'Lulus' ? 'ok' : (in_array($status, ['Pindah keluar', 'Keluar', 'Pindah']) ? 'w' : '');
                    
                    // Cek kelengkapan data kontrol induk
                    $checkFields = ['alasan_keluar', 'tanggal_keluar', 'kelas_keluar'];
                    if ($status === 'Lulus') {
                        $checkFields = array_merge($checkFields, ['nomor_peserta_psaj', 'nomor_skl', 'nomor_ijazah_sma', 'tanggal_ijazah_sma', 'nomor_transkrip']);
                    } else {
                        $checkFields[] = 'sekolah_tujuan';
                    }
                    $filled = 0;
                    foreach ($checkFields as $f) {
                        if (!empty($s->{$f})) $filled++;
                    }
                    $pct = count($checkFields) > 0 ? round(($filled / count($checkFields)) * 100) : 100;
                    $thnLulusDisplay = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                    $masukKelas = trim(($s->kelas_diterima ?: '') . ($s->rombel_diterima ? '-' . $s->rombel_diterima : ''));
                @endphp
                <tr>
                    <td>{{ $alumni->firstItem() + $idx }}</td>
                    <td>{{ $s->nomor_induk ?: '-' }}</td>
                    <td>
                        <a href="{{ route('admin.data-siswa.show', $s->id) }}" style="color:var(--p);font-weight:700;text-decoration:none">
                            {{ $s->nama_lengkap }}
                        </a>
                    </td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->jenis_registrasi ?: '-' }}</td>
                    <td>{{ $s->tanggal_diterima ? \Carbon\Carbon::parse($s->tanggal_diterima)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $masukKelas ?: '-' }}</td>
                    <td>{{ $s->sekolah_asal ?: '-' }}</td>
                    <td>{{ $s->alasan_keluar ?: '-' }}</td>
                    <td>{{ $s->tanggal_keluar ? \Carbon\Carbon::parse($s->tanggal_keluar)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $s->kelas_keluar ?: '-' }}</td>
                    <td>{{ $s->sekolah_tujuan ?: '-' }}</td>
                    <td>{{ $s->nomor_peserta_psaj ?: '-' }}</td>
                    <td>{{ $s->nomor_skl ?: '-' }}</td>
                    <td>{{ $s->nomor_ijazah_sma ?: '-' }}</td>
                    <td>{{ $s->tanggal_ijazah_sma ? \Carbon\Carbon::parse($s->tanggal_ijazah_sma)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $s->nomor_transkrip ?: '-' }}</td>
                    <td>{{ $s->kegiatan_setelah_lulus ?: '-' }}</td>
                    <td>{{ $thnLulusDisplay }}</td>
                    <td><span class="bd {{ $statusClass }}">{{ $status }}</span></td>
                    <td>
                        @if($pct >= 100)
                            <span class="bd ok">Lengkap</span>
                        @else
                            <span class="bd w">{{ $pct }}%</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('admin.data-siswa.show', $s->id) }}" class="btn">Lihat</a>
                        <a href="{{ route('admin.form-pendataan.edit', $s->id) }}" class="btn a">✏ Edit</a>
                        <a href="{{ route('admin.data-siswa.pdf', $s->id) }}" class="btn" target="_blank">⬇ PDF…</a>
                        <form method="POST" action="{{ route('admin.data-siswa.destroy', $s->id) }}" style="display:inline" onsubmit="return confirm('Pindahkan data ke tempat sampah?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="22" style="text-align:center;padding:24px;color:var(--mu)">
                        Tidak ada data siswa yang cocok dengan filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($alumni->hasPages())
    <div style="margin-top:14px;display:flex;justify-content:center">
        {{ $alumni->links() }}
    </div>
    @endif
</div>
@endsection
