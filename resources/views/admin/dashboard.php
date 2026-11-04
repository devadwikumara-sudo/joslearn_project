@extends('layouts.app')

@section('title', 'Dashboard — JosBook')

@section('content')
<h2>Dashboard</h2>
<p class="sub">Tahun ajaran {{ $taAktif ?? '2024/2025' }}</p>

{{-- 4 Stat Cards Utama (Prototype Row 1) --}}
<div class="g g4">
    <div class="card st">
        <span>Siswa aktif</span>
        <b>{{ number_format($totalSiswaAktif ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Guru</span>
        <b>{{ number_format($totalGuru ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Alumni</span>
        <b>{{ number_format($totalAlumni ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Data di sampah</span>
        <b>{{ number_format($totalSampah ?? 0) }}</b>
    </div>
</div>

{{-- 4 Stat Cards Rincian (Prototype Row 2) --}}
<div class="g g4" style="margin-top:12px">
    <div class="card st">
        <span>Laki-laki</span>
        <b>{{ number_format($totalLaki ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Perempuan</span>
        <b>{{ number_format($totalPerempuan ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Pindah / keluar {{ $taAktif ?? '' }}</span>
        <b>{{ number_format($totalMutasi ?? 0) }}</b>
    </div>
    <div class="card st">
        <span>Kelengkapan data</span>
        <b>{{ $persenLengkap ?? 100 }}%</b>
    </div>
</div>

{{-- Row 3: Komposisi per Kelas & Perlu Dilengkapi (Prototype Row 3) --}}
<div class="g g2" style="margin-top:12px">
    <div class="card">
        <h3>Komposisi per kelas</h3>
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>L</th>
                        <th>P</th>
                        <th>Total</th>
                        <th>Kelengkapan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($komposisiKelas ?? [] as $k)
                    <tr>
                        <td><strong>{{ $k['kelas'] }}</strong></td>
                        <td>{{ $k['l'] }}</td>
                        <td>{{ $k['p'] }}</td>
                        <td><b>{{ $k['total'] }}</b></td>
                        <td>
                            @if($k['total'] > 0)
                                <span class="bd {{ $k['persen'] >= 90 ? 'ok' : 'w' }}">{{ $k['persen'] }}%</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--mu);padding:20px">
                            Belum ada data kelas aktif pada tahun ajaran ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Perlu dilengkapi</h3>
        @if(!empty($perluDilengkapi))
            @foreach($perluDilengkapi as $item)
            <div class="row" style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--bd, #eef2f6)">
                <span>
                    <b>{{ $item['nama'] }}</b> · {{ $item['kelas'] }}<br>
                    <small style="color:var(--mu, #64748b)">
                        Kurang: {{ implode(', ', array_slice($item['missing'], 0, 3)) }}
                        @if(count($item['missing']) > 3)
                            +{{ count($item['missing']) - 3 }} lainnya
                        @endif
                    </small>
                </span>
                <a href="{{ route('admin.data-siswa.edit', $item['id']) }}" class="btn a" style="padding:4px 10px;font-size:12px">Edit</a>
            </div>
            @endforeach
        @else
            <span class="sub" style="display:block;padding:12px 0">Semua data siswa aktif sudah lengkap 🎉</span>
        @endif
    </div>
</div>

{{-- Row 4: Siswa per Angkatan & Alumni per Tahun Lulus + Log Aktivitas (Prototype Row 4) --}}
<div class="g g2" style="margin-top:12px">
    <div class="card">
        <h3>Siswa per angkatan</h3>
        <div class="angs" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-top:10px">
            <div class="ang" style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;text-align:center">
                <small style="display:block;color:#64748b;font-size:12px">Kelas X</small>
                <b style="font-size:18px;color:#1e293b">{{ number_format($angkatanX ?? 0) }} Siswa</b>
                <i style="display:block;font-size:11px;color:#94a3b8;margin-top:4px">◔ Aktif</i>
            </div>
            <div class="ang" style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;text-align:center">
                <small style="display:block;color:#64748b;font-size:12px">Kelas XI</small>
                <b style="font-size:18px;color:#1e293b">{{ number_format($angkatanXI ?? 0) }} Siswa</b>
                <i style="display:block;font-size:11px;color:#94a3b8;margin-top:4px">◔ Aktif</i>
            </div>
            <div class="ang" style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;text-align:center">
                <small style="display:block;color:#64748b;font-size:12px">Kelas XII</small>
                <b style="font-size:18px;color:#1e293b">{{ number_format($angkatanXII ?? 0) }} Siswa</b>
                <i style="display:block;font-size:11px;color:#94a3b8;margin-top:4px">◔ Aktif</i>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Alumni per tahun lulus</h3>
        <div class="bar" style="flex-wrap:wrap;gap:6px;margin:8px 0">
            @if(!empty($alumniPerTahun))
                @foreach($alumniPerTahun as $thn => $jml)
                    <span class="bd ok">{{ $thn }}: {{ $jml }}</span>
                @endforeach
            @else
                <span class="sub">Belum ada alumni</span>
            @endif
        </div>

        <h3 style="margin-top:16px">Aktivitas terbaru</h3>
        @if(!empty($aktivitasTerbaru) && count($aktivitasTerbaru) > 0)
            @foreach($aktivitasTerbaru as $log)
            <div class="row" style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13px">
                <b>{{ $log->keterangan ?? $log->aksi }}</b>
                <span style="color:#64748b">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
            </div>
            @endforeach
        @else
            <span class="sub">Belum ada aktivitas tercatat.</span>
        @endif
    </div>
</div>
@endsection
