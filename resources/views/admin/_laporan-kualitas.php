<div style="margin-bottom:14px">
    <h3 style="margin:0;font-size:17px;color:var(--admin-text)">Pemeriksaan Kualitas Data</h3>
    <p class="sub" style="margin:3px 0 0">Validasi otomatis integritas data siswa aktif, deteksi duplikasi NISN/NIS, format penulisan, dan kelengkapan dokumen persyaratan.</p>
</div>

{{-- 4 Stat Cards --}}
<div class="g g4" style="gap:14px;margin-bottom:16px">
    <div class="card st" style="border-left:4px solid {{ $jumlahKesalahan > 0 ? '#ef4444' : '#10b981' }}">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Kesalahan Validasi</span>
            <div style="width:28px;height:28px;border-radius:8px;background:{{ $jumlahKesalahan > 0 ? '#fef2f2' : '#ecfdf5' }};color:{{ $jumlahKesalahan > 0 ? '#ef4444' : '#10b981' }};display:flex;align-items:center;justify-content:center;font-size:14px">
                {{ $jumlahKesalahan > 0 ? '✕' : '✔' }}
            </div>
        </div>
        <b style="font-size:24px;font-weight:800;font-feature-settings:'tnum';color:{{ $jumlahKesalahan > 0 ? '#dc2626' : '#059669' }}">{{ $jumlahKesalahan }}</b>
    </div>

    <div class="card st" style="border-left:4px solid #f59e0b">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Peringatan Berkas</span>
            <div style="width:28px;height:28px;border-radius:8px;background:#fffbeb;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:14px">
                ⚠
            </div>
        </div>
        <b style="font-size:24px;font-weight:800;font-feature-settings:'tnum';color:#d97706">{{ $jumlahPeringatan }}</b>
    </div>

    <div class="card st" style="border-left:4px solid var(--admin-primary)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Siswa Diperiksa</span>
            <div style="width:28px;height:28px;border-radius:8px;background:#eff6ff;color:var(--admin-primary);display:flex;align-items:center;justify-content:center;font-size:14px">
                👥
            </div>
        </div>
        <b style="font-size:24px;font-weight:800;font-feature-settings:'tnum';color:var(--admin-primary)">{{ $totalSiswa }}</b>
    </div>

    <div class="card st" style="border-left:4px solid #6366f1">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Baris Data Terbaca</span>
            <div style="width:28px;height:28px;border-radius:8px;background:#e0e7ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:14px">
                📑
            </div>
        </div>
        <b style="font-size:24px;font-weight:800;font-feature-settings:'tnum';color:#4f46e5">{{ $totalBaris }}</b>
    </div>
</div>

{{-- Bar Filter & Ekspor CSV --}}
<div class="bar" style="margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Saring Temuan:</span>
        <select onchange="location.href='{{ route('admin.laporan', ['tab' => 'kualitas']) }}&jenis=' + this.value" style="font-weight:700;padding:7px 12px">
            <option value="semua" {{ $filter === 'semua' ? 'selected' : '' }}>Semua Temuan</option>
            <option value="kesalahan" {{ $filter === 'kesalahan' ? 'selected' : '' }}>Hanya Kesalahan ({{ $jumlahKesalahan }})</option>
            <option value="peringatan" {{ $filter === 'peringatan' ? 'selected' : '' }}>Hanya Peringatan ({{ $jumlahPeringatan }})</option>
        </select>
    </div>
    <span class="sp"></span>
    <a href="{{ route('admin.laporan', ['tab' => 'kualitas', 'jenis' => $filter, 'export' => 1]) }}" class="btn o">⬇ Ekspor CSV Temuan</a>
</div>

{{-- Card Tabel Temuan --}}
<div class="card">
    @if(count($masalah) > 0)
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:120px">Tingkat Risiko</th>
                    <th style="width:150px">Bidang Kolom</th>
                    <th>Nama Siswa / Objek</th>
                    <th>Detail Temuan Permasalahan</th>
                    <th style="width:110px;text-align:right">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($masalah as $m)
                <tr>
                    <td>
                        <span class="bd {{ $m['jenis'] === 'Kesalahan' ? 'e' : 'w' }}" style="font-weight:700;font-size:11.5px">
                            {{ $m['jenis'] === 'Kesalahan' ? '✕ Kesalahan' : '⚠ Peringatan' }}
                        </span>
                    </td>
                    <td><b style="color:var(--admin-text);font-size:13px">{{ $m['kolom'] }}</b></td>
                    <td>
                        <strong style="color:var(--admin-text);font-size:13.5px">{{ $m['siswa'] }}</strong>
                        @if($m['nisn'] && $m['nisn'] !== '-')
                            <br><small style="color:var(--mu);font-size:11px">NISN: <code>{{ $m['nisn'] }}</code></small>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--admin-text)">{{ $m['masalah'] }}</td>
                    <td style="text-align:right;white-space:nowrap">
                        @if(!empty($m['id']))
                            <a href="{{ route('admin.data-siswa.edit', $m['id']) }}" class="btn a" style="padding:4px 9px;font-size:11.5px">Perbaiki</a>
                        @elseif(!empty($m['siswa_ids']))
                            @foreach($m['siswa_ids'] as $siswaId)
                                <a href="{{ route('admin.data-siswa.edit', $siswaId) }}" class="btn a" style="padding:4px 9px;font-size:11.5px">Perbaiki #{{ $siswaId }}</a>
                            @endforeach
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="note ok" style="margin:0;padding:24px;text-align:center">
        <div style="font-size:32px;margin-bottom:8px">🎉</div>
        <b style="font-size:15px;color:#059669;display:block">Kualitas Data Sempurna!</b>
        <p class="sub" style="margin:4px 0 0;color:#065f46">Tidak ditemukan kesalahan format maupun dokumen yang belum lengkap pada basis data siswa aktif.</p>
    </div>
    @endif
</div>
