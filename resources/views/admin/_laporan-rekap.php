<div class="bar" style="margin-bottom:14px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Pilih Periode:</span>
        <form method="GET" action="{{ route('admin.laporan') }}" style="display:inline-flex;margin:0">
            <input type="hidden" name="tab" value="laporan">
            <select name="ta" onchange="this.form.submit()" style="font-size:13px;font-weight:700;padding:7px 12px">
                @foreach($taList as $item)
                <option value="{{ $item }}" {{ $ta === $item ? 'selected' : '' }}>Tahun Ajaran {{ $item }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <span class="sp"></span>
    <a href="{{ route('admin.laporan.cetak', ['ta' => $ta]) }}" target="_blank" class="btn a">🖨 Cetak Laporan Resmi</a>
</div>

<div class="card">
    <div class="bar" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <b>Rekapitulasi Kelas &amp; Performa Akademik ({{ $ta }})</b>
        </div>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:160px">Rombongan Belajar</th>
                    <th style="width:140px;text-align:center">Jumlah Siswa</th>
                    <th style="width:180px;text-align:center">Rata-rata Nilai</th>
                    <th>Total Ketidakhadiran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr>
                    <td>
                        <span class="bd" style="font-size:12px;font-weight:700;background:#eff6ff;color:var(--admin-primary);border:1px solid #bfdbfe">
                            {{ $r['kelas'] }}
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:700;font-feature-settings:'tnum'">{{ $r['siswa_count'] }} Siswa</td>
                    <td style="text-align:center;font-feature-settings:'tnum'">
                        <span style="font-weight:800;font-size:13.5px;color:{{ (float)$r['rata_nilai'] >= 75 ? '#059669' : '#d97706' }}">
                            {{ $r['rata_nilai'] }}
                        </span>
                    </td>
                    <td style="font-size:13px;color:var(--admin-text)">{{ $r['kehadiran'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:36px;color:var(--mu)">
                        <div style="font-size:32px;margin-bottom:8px">📊</div>
                        <div style="font-size:14px;font-weight:600">Tidak ada data kelas pada tahun ajaran ini.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
