@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Guru')

@section('content')
@php
    $queryFilters = array_merge(request()->query(), ['tahun_ajaran' => $filters['tahun_ajaran']]);
@endphp

<div class="bar" style="align-items:flex-start">
    <div>
        <h2>Data Siswa</h2>
        <p class="sub">Pilih kelas atau cari siswa untuk melihat data.</p>
    </div>
    <span class="sp"></span>
    <a href="{{ route('guru.data-siswa.export', $queryFilters) }}" class="btn">Ekspor Excel</a>
</div>

<form method="GET" action="{{ route('guru.data-siswa') }}" class="bar">
    <input type="hidden" name="tahun_ajaran" value="{{ $filters['tahun_ajaran'] }}">
    <select name="kelas" aria-label="Pilih kelas">
        <option value="">Semua kelas</option>
        @foreach($kelasList as $kelas)
            <option value="{{ $kelas }}" {{ $filters['kelas'] === $kelas ? 'selected' : '' }}>{{ $kelas }}</option>
        @endforeach
    </select>

    <input type="text" name="q" placeholder="Cari nama / NISN" value="{{ $filters['q'] }}" style="width:200px">

    <select name="st" aria-label="Pilih status">
        <option value="">Semua status</option>
        @foreach(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'] as $status)
            <option value="{{ $status }}" {{ $filters['st'] === $status ? 'selected' : '' }}>{{ $status }}</option>
        @endforeach
    </select>

    <button type="submit" class="btn o">Cari</button>
</form>

<div class="card">
    <div class="bar">
        <b>{{ $siswa->total() }} siswa</b>
        <span class="sp"></span>
        <button type="submit" form="pdf-siswa-terpilih" class="btn o" id="pdf-selected-button" disabled>PDF terpilih</button>
        <a href="{{ route('guru.data-siswa.pdf-all', $queryFilters) }}" class="btn o">PDF semua tampil</a>
    </div>

    <form id="pdf-siswa-terpilih" method="POST" action="{{ route('guru.data-siswa.pdf-selected') }}">
        @csrf
        @foreach($queryFilters as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="pilih-semua" aria-label="Pilih semua siswa di halaman"></th>
                        <th>NISN</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>JK</th>
                        <th>TTL</th>
                        <th>Agama</th>
                        <th>Orang tua</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswa as $murid)
                        @php
                            $riwayat = $murid->riwayatKelas->first();
                            $kelasNama = $riwayat?->kelas?->nama ?? 'Belum ditetapkan';
                            $statusNama = $riwayat?->status ?? 'Belum diatur';
                            $badgeClass = in_array($statusNama, ['Aktif', 'Lulus'], true)
                                ? 'ok'
                                : (in_array($statusNama, ['Pindah keluar', 'Belum diatur'], true) ? 'w' : 'e');
                            $studentFilters = array_merge($queryFilters, ['q' => $filters['q'], 'st' => $filters['st'], 'kelas' => $filters['kelas']]);
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="siswa_ids[]" value="{{ $murid->id }}" class="pilih-siswa" aria-label="Pilih {{ $murid->nama_lengkap }}"></td>
                            <td><code>{{ $murid->nisn ?? '-' }}</code></td>
                            <td>{{ $murid->nomor_induk ?? '-' }}</td>
                            <td><b>{{ $murid->nama_lengkap ?? '-' }}</b></td>
                            <td>{{ $kelasNama }}</td>
                            <td>{{ $murid->jenis_kelamin ?? '-' }}</td>
                            <td>{{ $murid->tempat_lahir ?? '-' }}, {{ $murid->tanggal_lahir ? \Carbon\Carbon::parse($murid->tanggal_lahir)->translatedFormat('d M Y') : '-' }}</td>
                            <td>{{ $murid->agama ?? '-' }}</td>
                            <td>{{ $murid->nama_ayah ?? $murid->nama_ibu ?? '-' }}</td>
                            <td><span class="bd {{ $badgeClass }}">{{ $statusNama }}</span></td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('guru.data-siswa.show', $murid->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat</a>
                                <a href="{{ route('guru.data-siswa.pdf', array_merge(['id' => $murid->id], $studentFilters)) }}" class="btn" style="padding:3px 8px;font-size:12px">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center;padding:28px;color:var(--mu)">
                                Tidak ada data siswa untuk kriteria ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($siswa->hasPages())
        <div style="margin-top:14px">{{ $siswa->links() }}</div>
    @endif
</div>

<script>
    const selectAllStudents = document.getElementById('pilih-semua');
    const studentCheckboxes = document.querySelectorAll('.pilih-siswa');
    const selectedPdfButton = document.getElementById('pdf-selected-button');

    const updateSelectedPdfButton = () => {
        selectedPdfButton.disabled = !document.querySelector('.pilih-siswa:checked');
    };

    selectAllStudents?.addEventListener('change', () => {
        studentCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAllStudents.checked;
        });
        updateSelectedPdfButton();
    });

    studentCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelectedPdfButton));
</script>
@endsection
