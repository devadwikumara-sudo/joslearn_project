<h3>Cek Kualitas Data</h3>
<p class="sub">Pemeriksaan data siswa aktif (format, data dobel, dan kelengkapan dokumen).</p>

{{-- 4 Stat Cards --}}
<div class="g g4">
    <div class="card st">
        <span>Kesalahan</span>
        <b style="color:{{ $jumlahKesalahan > 0 ? '#b03a2e' : '#1f7a4d' }}">{{ $jumlahKesalahan }}</b>
    </div>
    <div class="card st">
        <span>Peringatan</span>
        <b>{{ $jumlahPeringatan }}</b>
    </div>
    <div class="card st">
        <span>Siswa diperiksa</span>
        <b>{{ $totalSiswa }}</b>
    </div>
    <div class="card st">
        <span>Baris nilai / hadir / ekskul</span>
        <b>{{ $totalBaris }}</b>
    </div>
</div>

{{-- Bar Filter & Ekspor CSV --}}
<div class="bar" style="margin-top:12px">
    <select onchange="location.href='{{ route('admin.laporan', ['tab' => 'kualitas']) }}&jenis=' + this.value">
        <option value="semua" {{ $filter === 'semua' ? 'selected' : '' }}>Semua temuan</option>
        <option value="kesalahan" {{ $filter === 'kesalahan' ? 'selected' : '' }}>Kesalahan</option>
        <option value="peringatan" {{ $filter === 'peringatan' ? 'selected' : '' }}>Peringatan</option>
    </select>
    <span class="sp"></span>
    <a href="{{ route('admin.laporan', ['tab' => 'kualitas', 'jenis' => $filter, 'export' => 1]) }}" class="btn">⬇ Ekspor CSV</a>
</div>

{{-- Card Tabel Temuan --}}
<div class="card" style="margin-top:12px">
    @if(count($masalah) > 0)
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th style="width:110px">Jenis</th>
                    <th style="width:130px">Data</th>
                    <th>Siswa / Baris</th>
                    <th>Masalah</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($masalah as $m)
                <tr>
                    <td>
                        <span class="bd {{ $m['jenis'] === 'Kesalahan' ? 'e' : 'w' }}">
                            {{ $m['jenis'] }}
                        </span>
                    </td>
                    <td><b>{{ $m['kolom'] }}</b></td>
                    <td>
                        <strong>{{ $m['siswa'] }}</strong>
                        @if($m['nisn'] && $m['nisn'] !== '-')
                            <br><small style="color:var(--mu)">NISN: {{ $m['nisn'] }}</small>
                        @endif
                    </td>
                    <td>{{ $m['masalah'] }}</td>
                    <td>
                        @if(!empty($m['id']))
                            <a href="{{ route('admin.data-siswa.edit', $m['id']) }}" class="btn a" style="padding:3px 8px;font-size:12px">Perbaiki</a>
                        @elseif(!empty($m['siswa_ids']))
                            @foreach($m['siswa_ids'] as $siswaId)
                                <a href="{{ route('admin.data-siswa.edit', $siswaId) }}" class="btn a" style="padding:3px 8px;font-size:12px">Perbaiki #{{ $siswaId }}</a>
                            @endforeach
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="note ok" style="margin:0">
        ✔ Tidak ada temuan. Seluruh data lolos pemeriksaan.
    </div>
    @endif
</div>
