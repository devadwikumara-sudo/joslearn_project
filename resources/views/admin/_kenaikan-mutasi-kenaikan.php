@if($kelasAsal === '*')
    <div class="note" style="margin-bottom:14px">
        ℹ Silakan pilih satu rombel kelas tertentu pada pilihan filter di atas untuk memproses kenaikan kelas secara massal.
    </div>
@else
<form action="{{ route($routePrefix . '.kenaikan-kelas.proses') }}" method="POST">
    @csrf
    <input type="hidden" name="ta_aktif" value="{{ $taAktif }}">
    <input type="hidden" name="ta_berikutnya" value="{{ $taBerikutnya }}">
    <input type="hidden" name="kelas_asal" value="{{ $kelasAsal }}">

    <div class="card">
        <div class="bar" style="flex-wrap:wrap;gap:12px;margin-bottom:14px">
            <div>
                <div style="display:flex;align-items:center;gap:8px">
                    <h3 style="margin:0;font-size:17px;color:var(--admin-text)">Kenaikan Kelas {{ $kelasAsal }}</h3>
                    <span class="bd" style="background:#eff6ff;color:var(--admin-primary);border:1.5px solid #bfdbfe;font-weight:700">
                        {{ $taAktif }} &rarr; {{ $taBerikutnya }}
                    </span>
                </div>
                <p class="sub" style="margin:4px 0 0">Tentukan status kenaikan kelas siswa secara serentak atau perorangan. Riwayat nilai dan kelas sebelumnya tetap tersimpan permanen.</p>
            </div>
            <span class="sp"></span>
            @if(count($siswaList) > 0)
                <button type="submit" class="btn a">⚡ Proses {{ count($siswaList) }} Siswa</button>
            @endif
        </div>

        <div class="bar" style="background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:14px;flex-wrap:wrap;gap:8px">
            <span style="font-size:12px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Pilihan Cepat Massal:</span>
            <button type="button" class="btn o" style="font-size:12px;padding:5px 10px" onclick="setSemuaHasil('Naik')">⬆ Naikkan Semua</button>
            <button type="button" class="btn o" style="font-size:12px;padding:5px 10px" onclick="setSemuaHasil('Tinggal')">⏹ Tinggal Semua</button>
            <button type="button" class="btn o" style="font-size:12px;padding:5px 10px" onclick="setSemuaHasil('Lulus')">🎓 Luluskan Semua</button>
        </div>

        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px;text-align:center">No</th>
                        <th>Nama Lengkap</th>
                        <th style="width:160px">NISN</th>
                        <th style="width:160px">Keputusan Status</th>
                        <th>Kelas Tujuan ({{ $taBerikutnya }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $isKelas12 = str_starts_with(strtoupper($kelasAsal), 'XII');
                    @endphp
                    @forelse($siswaList as $idx => $s)
                    @php
                        $defaultTujuan = $nextKelasMap[$kelasAsal] ?? '';
                    @endphp
                    <tr>
                        <td style="text-align:center;color:var(--mu);font-size:12px">{{ $idx + 1 }}</td>
                        <td>
                            <b style="color:var(--admin-text);font-size:13.5px">{{ $s->nama_lengkap }}</b>
                            @if($s->nomor_induk)
                                <br><small style="color:var(--mu);font-size:11px">NIS: {{ $s->nomor_induk }}</small>
                            @endif
                        </td>
                        <td><code>{{ $s->nisn }}</code></td>
                        <td>
                            <select name="keputusan[{{ $s->id }}]" class="sel-keputusan" data-id="{{ $s->id }}" onchange="updateTargetKelas(this, '{{ $s->id }}')" style="width:100%;font-weight:700;font-size:13px">
                                @if($isKelas12)
                                    <option value="Lulus" selected>🎓 Lulus</option>
                                    <option value="Tinggal">⏹ Tinggal</option>
                                    <option value="Keluar">✕ Keluar</option>
                                @else
                                    <option value="Naik" selected>⬆ Naik</option>
                                    <option value="Tinggal">⏹ Tinggal</option>
                                    <option value="Keluar">✕ Keluar</option>
                                @endif
                            </select>
                        </td>
                        <td>
                            <div id="target-wrap-{{ $s->id }}">
                                @if($isKelas12)
                                    <span class="bd" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;font-weight:700">Lulus (Otomatis Masuk Alumni)</span>
                                @else
                                    <select name="kelas_tujuan[{{ $s->id }}]" class="sel-tujuan" style="width:100%;max-width:240px">
                                        @forelse($kelasNaikList as $kt)
                                            <option value="{{ $kt }}" {{ $defaultTujuan === $kt ? 'selected' : '' }}>{{ $kt }}</option>
                                        @empty
                                            <option value="" selected disabled>Belum ada kelas tujuan tingkat berikutnya</option>
                                        @endforelse
                                    </select>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:36px;color:var(--mu)">
                            <div style="font-size:32px;margin-bottom:8px">👥</div>
                            <div style="font-size:14px;font-weight:600">Tidak ada siswa aktif di kelas {{ $kelasAsal }} pada tahun ajaran {{ $taAktif }}.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(count($siswaList) > 0)
            <div class="bar" style="margin-top:16px;border-top:1px solid #eef2f6;padding-top:14px">
                <span class="sub">Periksa kembali keputusan kenaikan sebelum memproses.</span>
                <span class="sp"></span>
                <button type="submit" class="btn a">⚡ Proses {{ count($siswaList) }} Siswa</button>
            </div>
        @endif
    </div>
</form>

<div class="note" style="margin-top:14px">
    ℹ <b>Catatan Sistem:</b> Wali kelas hanya dapat memproses kelas yang diampunya. Siswa kelas XII yang diproses otomatis berstatus <b>Lulus</b> dan masuk ke Data Alumni. Untuk memindahkan siswa antar kelas pada tahun berjalan, gunakan tombol perpindahan kelas (⇄) di Data Siswa.
</div>
@endif

@push('scripts')
<script>
function setSemuaHasil(aksi) {
    document.querySelectorAll('.sel-keputusan').forEach(function(sel) {
        var id = sel.getAttribute('data-id');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === aksi) {
                sel.selectedIndex = i;
                updateTargetKelas(sel, id);
                break;
            }
        }
    });
}

function updateTargetKelas(sel, id) {
    var val = sel.value;
    var wrap = document.getElementById('target-wrap-' + id);
    if (!wrap) return;

    if (val === 'Lulus') {
        wrap.innerHTML = '<span class="bd" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;font-weight:700">Lulus (Masuk Alumni)</span>';
    } else if (val === 'Tinggal') {
        wrap.innerHTML = '<span class="bd w">{{ $kelasAsal }} (Tinggal di Kelas Asal)</span>';
    } else if (val === 'Keluar') {
        wrap.innerHTML = '<span style="color:var(--mu)">— (Keluar Sekolah)</span>';
    }
}
</script>
@endpush
