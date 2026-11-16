@if($kelasAsal === '*')
    <div class="note">Pilih satu kelas pada filter di atas untuk memproses kenaikan kelas.</div>
@else
<form action="{{ route($routePrefix . '.kenaikan-kelas.proses') }}" method="POST">
    @csrf
    <input type="hidden" name="ta_aktif" value="{{ $taAktif }}">
    <input type="hidden" name="ta_berikutnya" value="{{ $taBerikutnya }}">
    <input type="hidden" name="kelas_asal" value="{{ $kelasAsal }}">

    <div class="card">
        <h3>Kenaikan kelas {{ $kelasAsal }}</h3>
        <p class="sub" style="margin-bottom:12px">Proses dari {{ $taAktif }} ke {{ $taBerikutnya }}. Naikkan semua sekaligus atau atur satu per satu. Riwayat tahun lama tetap tersimpan.</p>
        <div class="bar">
            <button type="button" class="btn o" onclick="setSemuaHasil('Naik')">Naikkan semua</button>
            <button type="button" class="btn o" onclick="setSemuaHasil('Tinggal')">Tinggal semua</button>
            <button type="button" class="btn o" onclick="setSemuaHasil('Lulus')">Luluskan semua</button>
            <span class="sp"></span>
            <button type="submit" class="btn a">Proses {{ count($siswaList) }} siswa</button>
        </div>

        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NISN</th>
                        <th>Hasil</th>
                        <th>Kelas tujuan ({{ $taBerikutnya }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $isKelas12 = str_starts_with(strtoupper($kelasAsal), 'XII');
                    @endphp
                    @forelse($siswaList as $s)
                    @php
                        $defaultTujuan = $nextKelasMap[$kelasAsal] ?? $kelasAsal;
                    @endphp
                    <tr>
                        <td><b>{{ $s->nama_lengkap }}</b></td>
                        <td><code>{{ $s->nisn }}</code></td>
                        <td>
                            <select name="keputusan[{{ $s->id }}]" class="sel-keputusan" data-id="{{ $s->id }}" onchange="updateTargetKelas(this, '{{ $s->id }}')">
                                @if($isKelas12)
                                    <option value="Lulus" selected>Lulus</option>
                                    <option value="Tinggal">Tinggal</option>
                                    <option value="Keluar">Keluar</option>
                                @else
                                    <option value="Naik" selected>Naik</option>
                                    <option value="Tinggal">Tinggal</option>
                                    <option value="Keluar">Keluar</option>
                                @endif
                            </select>
                        </td>
                        <td>
                            <div id="target-wrap-{{ $s->id }}">
                                @if($isKelas12)
                                    <span style="color:var(--mu)">Lulus (Masuk Alumni)</span>
                                @else
                                    <select name="kelas_tujuan[{{ $s->id }}]" class="sel-tujuan">
                                        @foreach($kelasList as $kt)
                                            <option value="{{ $kt }}" {{ $defaultTujuan === $kt ? 'selected' : '' }}>{{ $kt }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                            Tidak ada siswa aktif di kelas {{ $kelasAsal }} pada tahun ajaran {{ $taAktif }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="note">
    Wali kelas hanya dapat memproses kelasnya sendiri. Siswa kelas XII otomatis berstatus <b>Lulus</b> dan masuk ke Data Alumni. Untuk memindahkan siswa ke kelas lain di tahun yang sama, pakai tombol ⇄ di Data Siswa.
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
        wrap.innerHTML = '<span style="color:var(--mu)">Lulus (Masuk Alumni)</span>';
    } else if (val === 'Tinggal') {
        wrap.innerHTML = '<span>{{ $kelasAsal }} (Tinggal)</span>';
    } else if (val === 'Keluar') {
        wrap.innerHTML = '<span style="color:var(--mu)">— (Keluar)</span>';
    }
}
</script>
@endpush