@extends('layouts.app')

@section('title', 'Laporan - JosBook Admin')

@section('content')
<h2>Laporan</h2>
<p class="sub">Cetak dan unduh laporan buku induk siswa</p>

<div class="g g2">
    {{-- Generate Laporan --}}
    <div class="card">
        <h3>📄 Generate Laporan</h3>
        <form method="POST" action="{{ route('admin.laporan.cetak') }}" target="_blank">
            @csrf
            <div style="display:grid;gap:12px;margin-bottom:14px">
                <div>
                    <label>Jenis Laporan</label>
                    <select name="jenis_laporan" style="width:100%" onchange="gantiJenisLaporan(this.value)">
                        <option value="buku-induk">Buku Induk Siswa</option>
                        <option value="nilai">Rekap Nilai</option>
                        <option value="kehadiran">Rekap Kehadiran</option>
                        <option value="ekskul">Rekap Ekskul</option>
                        <option value="alumni">Data Alumni</option>
                        <option value="guru">Data Guru</option>
                    </select>
                </div>
                <div>
                    <label>Kelas / Rombel</label>
                    <select name="kelas" style="width:100%">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList ?? [] as $k)
                            <option>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Tahun Ajaran</label>
                    <select name="tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2023/2024</option><option>2022/2023</option>
                    </select>
                </div>
                <div>
                    <label>Semester</label>
                    <select name="semester" style="width:100%">
                        <option value="">Semua Semester</option>
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
                <div>
                    <label>Format Output</label>
                    <select name="format" style="width:100%">
                        <option value="pdf">PDF (untuk dicetak)</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn" style="flex:1">🖨️ Cetak / Unduh Laporan</button>
            </div>
        </form>
    </div>

    {{-- Riwayat Laporan --}}
    <div class="card">
        <h3>🕒 Laporan Terbaru</h3>
        @forelse($riwayatLaporan ?? [] as $l)
        <div class="row">
            <div>
                <b style="font-size:12px">{{ $l->nama_laporan }}</b>
                <div style="font-size:11px;color:var(--mu)">
                    {{ $l->kelas ?? 'Semua Kelas' }} · {{ $l->tahun_ajaran }}
                </div>
                <div style="font-size:10px;color:var(--mu)">
                    oleh {{ $l->pengguna->nama ?? '-' }} · {{ \Carbon\Carbon::parse($l->created_at)->format('d M Y H:i') }}
                </div>
            </div>
            <a href="{{ $l->url_file }}" class="btn o" style="padding:4px 10px;font-size:11px" target="_blank">⬇ Unduh</a>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada laporan digenerate.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function gantiJenisLaporan(jenis) {
    // Sesuaikan field yang tampil berdasarkan jenis laporan
}
</script>
@endpush

