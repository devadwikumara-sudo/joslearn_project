@extends('layouts.app')

@section('title', 'Kenaikan Kelas — JosBook Admin')

@section('content')
<h2>Kenaikan Kelas</h2>
<p class="sub">Proses dari {{ $taAktif ?? '2024/2025' }} ke {{ $taBerikutnya ?? '2025/2026' }}. Naikkan semua sekaligus atau atur satu per satu. Riwayat tahun lama tetap tersimpan.</p>

<form action="{{ route('admin.kenaikan-kelas.proses') }}" method="POST">
    @csrf
    <input type="hidden" name="ta_aktif" value="{{ $taAktif }}">
    <input type="hidden" name="ta_berikutnya" value="{{ $taBerikutnya }}">
    <input type="hidden" name="kelas_asal" value="{{ $kelasAsal }}">

    <div class="card">
        <div class="bar">
            <div>
                <label>Kelas asal</label>
                <select onchange="location.href='{{ route('admin.kenaikan-kelas') }}?kelas='+this.value">
                    @foreach($kelasList as $k)
                        <option value="{{ $k }}" {{ $kelasAsal === $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <span class="sp"></span>
            <button type="button" class="btn o" onclick="setSemuaHasil('Naik')">Naikkan semua</button>
            <button type="button" class="btn o" onclick="setSemuaHasil('Tinggal')">Tinggal semua</button>
            <button type="button" class="btn o" onclick="setSemuaHasil('Lulus')">Luluskan semua</button>
            <button type="submit" class="btn a">Proses {{ count($siswaList) }} siswa</button>
        </div>

        <div class="tw" style="margin-top:12px">
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
                        $defaultHasil = $isKelas12 ? 'Lulus' : 'Naik';
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
                        <td colspan="4" style="text-align:center;padding:26px;color:var(--mu)">
                            Tidak ada siswa aktif di kelas {{ $kelasAsal }} pada tahun ajaran {{ $taAktif }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="note" style="margin-top:14px">
    Wali kelas hanya dapat memproses kelasnya sendiri. Siswa kelas XII otomatis berstatus <b>Lulus</b> dan masuk ke Data Alumni. Untuk memindahkan siswa ke kelas lain di tahun yang sama, pakai tombol ⇄ di Data Siswa.
</div>

@push('scripts')
<script>
function setSemuaHasil(aksi) {
    document.querySelectorAll('.sel-keputusan').forEach(function(sel) {
        var id = sel.getAttribute('data-id');
        // Pastikan opsi ada
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
@endsection
