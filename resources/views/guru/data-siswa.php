@extends('layouts.app')

@section('title', 'Data Siswa — JosBook Guru')

@section('content')
@php
    $queryFilters = array_merge(request()->query(), [
        'tahun_ajaran' => $filters['tahun_ajaran'],
        'tab' => $filters['tab'],
    ]);
    $listRoute = $lingkup === 'wali' ? 'guru.data-siswa.wali' : 'guru.data-siswa';
    $bookPdfFilters = [
        'target_kelas' => $filters['kelas'] !== '' ? $filters['kelas'] : '*',
        'target_ta' => (string) $filters['tahun_ajaran'],
        'q' => $filters['q'],
        'st' => $filters['st'],
        'tab' => $filters['tab'],
    ];
@endphp

<div class="bar" style="margin-bottom:14px">
    <div>
        <h2 style="margin:0">{{ $lingkup === 'wali' ? 'Data Siswa Kelas Wali' : 'Data Siswa Kelas Mapel' }}</h2>
        <p class="sub" style="margin:4px 0 0">
            {{ $lingkup === 'wali' ? 'Menampilkan siswa dari kelas perwalian Anda.' : 'Menampilkan siswa dari kelas yang Anda ajar.' }}
        </p>
    </div>
</div>

<div class="student-toolbar">
    <form method="GET" action="{{ route($listRoute) }}" class="student-filters">
        <input type="hidden" name="tahun_ajaran" value="{{ $filters['tahun_ajaran'] }}">
        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
        <select name="kelas" aria-label="Pilih kelas">
            <option value="">Semua kelas</option>
            @foreach($kelasList as $kelas)
                <option value="{{ $kelas }}" {{ $filters['kelas'] === $kelas ? 'selected' : '' }}>{{ $kelas }}</option>
            @endforeach
        </select>

        <input type="text" name="q" placeholder="Cari nama / NIS / NISN / NIK" value="{{ $filters['q'] }}" style="width:220px">

        <select name="st" aria-label="Pilih status">
            <option value="">Semua status</option>
            @foreach(['Aktif' => 'Aktif', 'Lulus' => 'Alumni', 'Pindah keluar' => 'Mutasi'] as $value => $label)
                <option value="{{ $value }}" {{ $filters['st'] === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn">🔍 Cari</button>
    </form>

    <div class="student-page-actions">
        @if($lingkup === 'mapel' && $adaKelasWali)
            <a href="{{ route('guru.data-siswa.wali') }}" class="btn o">👥 Data Siswa Kelas Wali</a>
        @elseif($lingkup === 'wali' && $adaKelasMapel)
            <a href="{{ route('guru.data-siswa') }}" class="btn o">📚 Kelas Mapel</a>
        @endif
        <a href="{{ route('guru.data-siswa.export', $queryFilters) }}" class="btn o">📊 Ekspor Excel</a>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="bar">
        <span class="student-counter-badge">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <b>{{ $siswa->total() }}</b> Siswa
        </span>
        <div class="student-table-actions">
            <button type="submit" form="pdf-siswa-terpilih" class="btn o" id="pdf-selected-button" disabled>📄 PDF terpilih</button>
            <a href="{{ route('guru.data-siswa.pdf-all', $queryFilters) }}" class="btn o">📑 PDF semua tampil</a>
            <button type="button" class="btn o" id="book-pdf-selected-button" disabled onclick="cetakBukuIndukTerpilih()">📚 Buku induk terpilih</button>
            <a href="{{ route('guru.data-siswa.pdf-batch', [
                'target_kelas' => $filters['kelas'] !== '' ? $filters['kelas'] : '*',
                'target_ta' => (string) $filters['tahun_ajaran'],
                'q' => $filters['q'],
                'st' => $filters['st'],
                'tab' => $filters['tab'],
            ]) }}" class="btn o">📖 Buku induk filter</a>
            <a href="{{ route('guru.data-siswa.pdf-batch', ['target_kelas' => '*', 'target_ta' => (string) $filters['tahun_ajaran'], 'tab' => $filters['tab']]) }}" class="btn o">📚 Semua siswa</a>
        </div>
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
                            $statusLabel = ['Lulus' => 'Alumni', 'Pindah keluar' => 'Mutasi'][$statusNama] ?? $statusNama;
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
                            <td><span class="bd {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('guru.data-siswa.show', array_merge(['id' => $murid->id], $queryFilters)) }}" class="btn o" style="padding:4px 9px;font-size:12px">👁 Lihat</a>
                                <a href="{{ route('guru.data-siswa.pdf', array_merge(['id' => $murid->id], $studentFilters)) }}" class="btn a" style="padding:4px 9px;font-size:12px">⬇ PDF</a>
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
    const selectedBookPdfButton = document.getElementById('book-pdf-selected-button');
    const bookPdfBatchUrl = @json(route('guru.data-siswa.pdf-batch'));
    const bookPdfFilters = @json($bookPdfFilters);

    const updateSelectedPdfButton = () => {
        const hasSelectedStudents = Boolean(document.querySelector('.pilih-siswa:checked'));
        selectedPdfButton.disabled = !hasSelectedStudents;
        selectedBookPdfButton.disabled = !hasSelectedStudents;
    };

    selectAllStudents?.addEventListener('change', () => {
        studentCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAllStudents.checked;
        });
        updateSelectedPdfButton();
    });

    studentCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelectedPdfButton));

    function cetakBukuIndukTerpilih() {
        const ids = Array.from(document.querySelectorAll('.pilih-siswa:checked')).map((checkbox) => checkbox.value);
        if (!ids.length) return;

        const parameters = new URLSearchParams({ ...bookPdfFilters, ids: ids.join(',') });
        window.open(bookPdfBatchUrl + '?' + parameters.toString(), '_blank');
    }
</script>
@endsection
