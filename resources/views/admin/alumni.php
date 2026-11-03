@extends('layouts.app')

@section('title', 'Alumni & Kontrol - JosBook Admin')

@section('content')
<h2>Alumni & Kontrol</h2>
<p class="sub">Manajemen data alumni dan pelacakan lulusan</p>

<div class="g g2" style="margin-bottom:14px">
    <div class="card st"><span>Total Alumni</span><b>{{ $totalAlumni ?? '—' }}</b></div>
    <div class="card st"><span>Angkatan Terakhir</span><b>{{ $angkatanTerakhir ?? '—' }}</b></div>
</div>

<div class="card">
    <div class="bar">
        <input type="text" placeholder="🔍 Cari nama / NISN alumni…" id="qAlumni" style="min-width:220px" oninput="filterAlumni(this.value)">
        <select style="min-width:120px" id="filterAngkatan">
            <option value="">Semua Angkatan</option>
            @foreach($angkatanList ?? [] as $a)
                <option>{{ $a }}</option>
            @endforeach
        </select>
        <span class="sp"></span>
        <button class="btn o" onclick="exportAlumni()">⬇ Ekspor Data</button>
    </div>

    <div class="tw">
        <table id="tblAlumni">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>NISN</th>
                    <th>Angkatan</th>
                    <th>No. Ijazah</th>
                    <th>Status Lanjut</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alumni ?? [] as $i => $a)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $a->nama_lengkap }}</b></td>
                    <td>{{ $a->nisn }}</td>
                    <td>{{ $a->angkatan }}</td>
                    <td>{{ $a->no_ijazah ?? '-' }}</td>
                    <td>
                        <span class="bd {{ $a->status_lanjut === 'kuliah' ? 'ok' : ($a->status_lanjut === 'kerja' ? 'w' : '') }}">
                            {{ ucfirst($a->status_lanjut ?? 'Belum diisi') }}
                        </span>
                    </td>
                    <td>
                        <a href="/admin/alumni/{{ $a->id }}" class="btn o" style="padding:4px 10px;font-size:11px">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--mu);padding:24px">Belum ada data alumni.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterAlumni(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tblAlumni tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function exportAlumni() { alert('Fitur ekspor data alumni akan segera tersedia.'); }
</script>
@endpush

