@extends('layouts.app')

@section('title', 'Kenaikan Kelas - JosBook Admin')

@section('content')
<h2>Kenaikan Kelas</h2>
<p class="sub">Proses kenaikan kelas siswa berdasarkan nilai dan kehadiran</p>

<div class="note">
    ⚠️ Proses kenaikan kelas bersifat <b>permanen</b>. Pastikan semua data nilai dan kehadiran sudah lengkap sebelum melanjutkan.
</div>

<div class="g g2">
    <div class="card">
        <h3>⚙️ Konfigurasi Kenaikan Kelas</h3>
        <form method="POST" action="{{ route('admin.kenaikan-kelas.proses') }}">
            @csrf
            <div style="display:grid;gap:12px;margin-bottom:14px">
                <div>
                    <label>Dari Tahun Ajaran</label>
                    <select name="dari_tahun_ajaran" style="width:100%">
                        <option>2023/2024</option><option>2024/2025</option>
                    </select>
                </div>
                <div>
                    <label>Ke Tahun Ajaran</label>
                    <select name="ke_tahun_ajaran" style="width:100%">
                        <option>2024/2025</option><option>2025/2026</option>
                    </select>
                </div>
                <div>
                    <label>Kelas yang Diproses</label>
                    <select name="kelas" style="width:100%">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList ?? [] as $k)
                            <option>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Nilai Minimum Kelulusan</label>
                    <input type="number" name="nilai_minimum" value="75" min="0" max="100" style="width:100%">
                </div>
            </div>
            <button type="submit" class="btn" style="width:100%" onclick="return confirm('Yakin proses kenaikan kelas? Tindakan ini tidak bisa dibatalkan.')">
                🎓 Proses Kenaikan Kelas
            </button>
        </form>
    </div>

    <div class="card">
        <h3>📊 Status Kenaikan</h3>
        @forelse($statusKelas ?? [] as $kelas => $data)
        <div style="padding:10px;border:1px solid var(--ln);border-radius:10px;margin-bottom:10px">
            <b>{{ $kelas }}</b>
            <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">
                <span class="bd ok">Naik: {{ $data['naik'] ?? 0 }}</span>
                <span class="bd e">Tidak Naik: {{ $data['tidak_naik'] ?? 0 }}</span>
                <span class="bd w">Belum Diproses: {{ $data['pending'] ?? 0 }}</span>
            </div>
        </div>
        @empty
        <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">Belum ada data kenaikan kelas.</p>
        @endforelse
    </div>
</div>

{{-- Tabel siswa tidak naik kelas --}}
@if(isset($siswaTidakNaik) && count($siswaTidakNaik) > 0)
<div class="card">
    <h3>❌ Siswa Tidak Naik Kelas ({{ count($siswaTidakNaik) }})</h3>
    <div class="tw">
        <table>
            <thead><tr><th>Nama</th><th>NISN</th><th>Kelas</th><th>Alasan</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($siswaTidakNaik as $s)
                <tr>
                    <td><b>{{ $s->nama_lengkap }}</b></td>
                    <td>{{ $s->nisn }}</td>
                    <td>{{ $s->kelas }}</td>
                    <td style="color:var(--er)">{{ $s->alasan_tidak_naik }}</td>
                    <td><a href="/admin/data-siswa/{{ $s->id }}" class="btn o" style="padding:4px 10px;font-size:11px">Lihat Detail</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

