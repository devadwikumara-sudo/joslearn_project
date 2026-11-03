@extends('layouts.app')

@section('title', 'Input Nilai Mapel - JosBook Guru')

@section('content')
<h2>Input Nilai Mapel</h2>
<p class="sub">Input nilai secara langsung per siswa tanpa upload file</p>

<div class="card">
    <div class="bar">
        <select id="selKelas" style="min-width:160px" onchange="muatSiswa(this.value)">
            <option value="">Pilih Kelas</option>
            @foreach($kelasList ?? [] as $k)
                <option value="{{ $k }}" {{ request('kelas') === $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select>
        <select id="selSemester" style="min-width:160px">
            <option value="1">Semester 1 (Ganjil)</option>
            <option value="2">Semester 2 (Genap)</option>
        </select>
        <select id="selTahun" style="min-width:140px">
            <option>2024/2025</option><option>2023/2024</option>
        </select>
        <span class="sp"></span>
        <button class="btn" onclick="simpanSemua()">💾 Simpan Semua Nilai</button>
    </div>

    <div class="note">
        📌 Mata Pelajaran: <b>{{ Auth::user()->mata_pelajaran ?? 'Belum diatur — hubungi admin' }}</b>
    </div>

    <div class="tw">
        <table id="tblNilai">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>NISN</th>
                    <th>Nilai Harian</th>
                    <th>UTS</th>
                    <th>UAS</th>
                    <th>Nilai Akhir</th>
                    <th>Predikat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswa ?? [] as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $s->nama_lengkap }}</b></td>
                    <td>{{ $s->nisn }}</td>
                    <td>
                        <input type="number" name="nilai_harian[{{ $s->id }}]" min="0" max="100" step="0.1"
                            value="{{ $s->nilai->nilai_harian ?? '' }}"
                            style="width:70px;text-align:center" onchange="hitungAkhir(this, {{ $i }})">
                    </td>
                    <td>
                        <input type="number" name="uts[{{ $s->id }}]" min="0" max="100" step="0.1"
                            value="{{ $s->nilai->uts ?? '' }}"
                            style="width:70px;text-align:center" onchange="hitungAkhir(this, {{ $i }})">
                    </td>
                    <td>
                        <input type="number" name="uas[{{ $s->id }}]" min="0" max="100" step="0.1"
                            value="{{ $s->nilai->uas ?? '' }}"
                            style="width:70px;text-align:center" onchange="hitungAkhir(this, {{ $i }})">
                    </td>
                    <td><b id="akhir-{{ $i }}">{{ $s->nilai->nilai_akhir ?? '-' }}</b></td>
                    <td><span id="pred-{{ $i }}" class="bd {{ isset($s->nilai->predikat) ? ($s->nilai->predikat === 'A' ? 'ok' : ($s->nilai->predikat === 'D' ? 'e' : 'w')) : '' }}">
                        {{ $s->nilai->predikat ?? '-' }}
                    </span></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--mu);padding:24px">Pilih kelas untuk menampilkan daftar siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(count($siswa ?? []) > 0)
    <form id="formNilai" method="POST" action="{{ route('guru.input-nilai-mapel.store') }}">
        @csrf
        <input type="hidden" name="kelas" id="hidKelas" value="{{ request('kelas') }}">
        <input type="hidden" name="semester" id="hidSemester" value="1">
        <input type="hidden" name="tahun_ajaran" id="hidTahun" value="2024/2025">
        <input type="hidden" name="data_nilai" id="hidData" value="">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="submit" class="btn">💾 Simpan Semua Nilai</button>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function hitungAkhir(inp, idx) {
    var row = inp.closest('tr');
    var inputs = row.querySelectorAll('input[type=number]');
    var nh = parseFloat(inputs[0].value) || 0;
    var uts = parseFloat(inputs[1].value) || 0;
    var uas = parseFloat(inputs[2].value) || 0;
    var akhir = (nh * 0.4 + uts * 0.3 + uas * 0.3).toFixed(1);
    document.getElementById('akhir-' + idx).textContent = akhir;

    var pred = akhir >= 90 ? 'A' : akhir >= 80 ? 'B' : akhir >= 70 ? 'C' : akhir >= 60 ? 'D' : 'E';
    var elPred = document.getElementById('pred-' + idx);
    elPred.textContent = pred;
    elPred.className = 'bd ' + (pred === 'A' ? 'ok' : pred === 'E' || pred === 'D' ? 'e' : 'w');
}

function simpanSemua() {
    document.getElementById('hidKelas').value = document.getElementById('selKelas').value;
    document.getElementById('hidSemester').value = document.getElementById('selSemester').value;
    document.getElementById('hidTahun').value = document.getElementById('selTahun').value;
    document.getElementById('formNilai').submit();
}

function muatSiswa(kelas) {
    if (kelas) window.location.href = '?kelas=' + encodeURIComponent(kelas);
}
</script>
@endpush

