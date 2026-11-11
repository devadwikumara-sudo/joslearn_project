@extends('layouts.app')

@section('title', 'Data Guru — JosBook Admin')

@section('content')
<h2>Data Guru</h2>
<p class="sub">{{ $totalGuru ?? 0 }} guru · {{ $totalGuruMapel ?? 0 }} guru mapel · {{ $totalWaliKelas ?? 0 }} wali kelas</p>

@if(session('sandi_sementara'))
<div class="note ok">
    Sandi sementara untuk <b>{{ session('sandi_sementara.username') }}</b> (hanya ditampilkan kali ini):
    <code>{{ session('sandi_sementara.password') }}</code>. Berikan kepada guru secara aman.
</div>
@endif
@if(session('error'))
<div class="note err">{{ session('error') }}</div>
@endif

<div class="note">
    Guru mapel hanya bisa mengedit nilai mapelnya di kelas yang diajar. Wali kelas bisa menaikkan kelas siswa di kelasnya sendiri dan mengelola kehadiran &amp; ekskul kelasnya. Satu guru boleh punya kedua peran.
</div>

{{-- Card Daftar Guru --}}
<div class="card" style="margin-top:14px">
    <div class="bar">
        <b>Daftar guru</b>
        <span class="sp"></span>
        <button type="button" class="btn a" onclick="toggleModalGuru(true)">+ Tambah guru</button>
    </div>

    <div class="tw" style="margin-top:10px">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th>Status</th>
                    <th>Peran</th>
                    <th>Mapel</th>
                    <th>Mengajar di</th>
                    <th>Wali kelas</th>
                    <th>HP</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guruList ?? [] as $g)
                @php
                    $isWali = $g->waliKelas && $g->waliKelas->count() > 0;
                    $waliObj = $isWali ? $g->waliKelas->first() : null;
                    $waliKelasNama = ($waliObj && $waliObj->kelas) ? $waliObj->kelas->nama : '-';

                    $isMengajar = $g->mengajar && $g->mengajar->count() > 0;
                    $mapelNames = $isMengajar ? $g->mengajar->pluck('mapel.nama')->unique()->filter()->implode(', ') : '-';
                    $kelasAjarNames = $isMengajar ? $g->mengajar->pluck('kelas.nama')->unique()->filter()->implode(', ') : '-';
                @endphp
                <tr>
                    <td><b>{{ $g->nama }}</b></td>
                    <td><code>{{ $g->nip ?? '-' }}</code></td>
                    <td>{{ $g->nuptk ?? '-' }}</td>
                    <td>{{ $g->status_kepegawaian ?? 'PNS' }}</td>
                    <td>
                        @if($isMengajar)
                            <span class="bd ok">Guru Mapel</span>
                        @endif
                        @if($isWali)
                            <span class="bd w">Wali Kelas</span>
                        @endif
                        @if(!$isMengajar && !$isWali)
                            <span class="bd">Guru</span>
                        @endif
                    </td>
                    <td>{{ $mapelNames }}</td>
                    <td>{{ $kelasAjarNames }}</td>
                    <td>{{ $waliKelasNama }}</td>
                    <td>{{ $g->nomor_hp ?? '-' }}</td>
                    <td style="white-space:nowrap">
                        @if($g->akun)
                            <form action="{{ route('admin.data-guru.reset-sandi', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Reset sandi akun {{ $g->nama }}?')">
                                @csrf
                                <button type="submit" class="btn o" style="padding:3px 8px;font-size:12px">Reset sandi</button>
                            </form>
                        @else
                            <form action="{{ route('admin.data-guru.buat-akun', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Buat akun untuk {{ $g->nama }}?')">
                                @csrf
                                <button type="submit" class="btn a" style="padding:3px 8px;font-size:12px">Buat akun</button>
                            </form>
                        @endif
                        <button type="button" class="btn o" style="padding:3px 8px;font-size:12px" onclick="editGuruData({{ \Illuminate\Support\Js::from($g) }})">Edit</button>
                        <form action="{{ route('admin.data-guru.destroy', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Hapus guru {{ $g->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x" style="padding:3px 8px;font-size:12px">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:26px;color:var(--mu)">
                        Belum ada data guru tersimpan. Klik <b>+ Tambah guru</b> untuk menambah tenaga pendidik.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL TAMBAH / EDIT GURU (Sesuai Prototype gedit) --}}
<div id="modal-guru" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
    <div class="card" style="max-width:720px;width:100%;margin:auto;background:#fff;border-radius:10px">
        <h3 id="modal-title">Tambah Guru</h3>
        <p class="sub">Lengkapi data guru lalu tentukan perannya.</p>

        <form id="form-guru" action="{{ route('admin.data-guru.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="guru-method" value="POST">

            <div class="fl">
                <div>
                    <label>Nama Lengkap (dengan gelar) *</label>
                    <input type="text" name="nama" id="g-nama" required>
                </div>
                <div>
                    <label>NIP / NIK</label>
                    <input type="text" name="nip" id="g-nip">
                </div>
                <div>
                    <label>NUPTK</label>
                    <input type="text" name="nuptk" id="g-nuptk">
                </div>
                <div>
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="g-jk">
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="g-tmp">
                </div>
                <div>
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="g-tgl">
                </div>
                <div>
                    <label>Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" id="g-pend">
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                        <option value="D3">D3</option>
                    </select>
                </div>
                <div>
                    <label>Status Kepegawaian</label>
                    <select name="status_kepegawaian" id="g-status">
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                        <option value="GTT">GTT</option>
                        <option value="Honorer">Honorer</option>
                    </select>
                </div>
                <div>
                    <label>Mulai Bertugas (Tahun)</label>
                    <input type="number" name="mulai_bertugas" id="g-tmt" placeholder="2015">
                </div>
                <div>
                    <label>No. HP</label>
                    <input type="text" name="nomor_hp" id="g-hp">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" id="g-email">
                </div>
                <div>
                    <label>Alamat</label>
                    <input type="text" name="alamat" id="g-alamat">
                </div>
            </div>

            {{-- Peran Guru --}}
            <h4 style="margin:16px 0 6px;color:var(--p)">Peran guru (boleh dua-duanya)</h4>

            <div class="card" style="margin:0 0 10px;background:#f8fafc">
                <label style="display:flex;gap:6px;font-size:13px;font-weight:600;align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_mapel" id="g-ismp" value="1" onchange="syncGuruRoleFields()"> Guru mapel — bisa mengedit nilai mapelnya
                </label>
                <fieldset id="g-mapel-fields" style="border:0;padding:0;margin:8px 0 0;min-width:0">
                    <div id="g-teaching-assignments" style="display:grid;gap:8px"></div>
                    <button type="button" class="btn o" style="margin-top:8px" onclick="addTeacherAssignmentRow()">+ Tambah penugasan kelas dan mapel</button>
                </fieldset>
            </div>

            <div class="card" style="margin:0;background:#f8fafc">
                <label style="display:flex;gap:6px;font-size:13px;font-weight:600;align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_wali" id="g-iswali" value="1" onchange="syncGuruRoleFields()"> Wali kelas — bisa menaikkan kelas siswa di kelasnya
                </label>
                <fieldset id="g-wali-fields" style="border:0;padding:0;margin:8px 0 0;min-width:0">
                    <select name="wali_kelas" id="g-wali" required>
                        <option value="">— Pilih kelas wali —</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </fieldset>
            </div>

            <template id="g-teaching-assignment-template">
                <div data-assignment-row class="bar" style="flex-wrap:wrap;gap:8px">
                    <label style="flex:1;min-width:160px">Kelas
                        <select data-field="kelas" required>
                            <option value="">Pilih kelas</option>
                            @foreach($kelasList as $k)
                                <option value="{{ $k }}">{{ $k }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="flex:1;min-width:160px">Mata pelajaran
                        <select data-field="mapel" required>
                            <option value="">Pilih mata pelajaran</option>
                            @foreach($mapelList as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="btn x" data-remove-assignment aria-label="Hapus penugasan">Hapus</button>
                </div>
            </template>

            <div class="bar" style="margin-top:16px;border-top:1px solid #e2e8f0;padding-top:12px">
                <span class="sp"></span>
                <button type="button" class="btn o" onclick="toggleModalGuru(false)">Batal</button>
                <button type="submit" class="btn a">Simpan Data Guru</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
var nextTeachingAssignmentIndex = 0;

function toggleModalGuru(show) {
    var modal = document.getElementById('modal-guru');
    if (!modal) return;
    modal.style.display = show ? 'flex' : 'none';
    if (show) {
        document.getElementById('form-guru').reset();
        document.getElementById('g-teaching-assignments').replaceChildren();
        nextTeachingAssignmentIndex = 0;
        addTeacherAssignmentRow();
        syncGuruRoleFields();
        document.getElementById('modal-title').textContent = 'Tambah Guru';
        document.getElementById('form-guru').action = "{{ route('admin.data-guru.store') }}";
        document.getElementById('guru-method').value = 'POST';
    }
}

function addTeacherAssignmentRow(kelas, mapel) {
    var template = document.getElementById('g-teaching-assignment-template');
    var container = document.getElementById('g-teaching-assignments');
    var row = template.content.firstElementChild.cloneNode(true);
    var index = nextTeachingAssignmentIndex++;
    var classSelect = row.querySelector('[data-field="kelas"]');
    var subjectSelect = row.querySelector('[data-field="mapel"]');

    classSelect.name = 'penugasan_mengajar[' + index + '][kelas]';
    subjectSelect.name = 'penugasan_mengajar[' + index + '][mapel]';
    classSelect.value = kelas || '';
    subjectSelect.value = mapel || '';
    row.querySelector('[data-remove-assignment]').addEventListener('click', function() {
        row.remove();
    });
    container.appendChild(row);
}

function syncGuruRoleFields() {
    document.getElementById('g-mapel-fields').disabled = !document.getElementById('g-ismp').checked;
    document.getElementById('g-wali-fields').disabled = !document.getElementById('g-iswali').checked;
}

function editGuruData(g) {
    toggleModalGuru(true);
    document.getElementById('modal-title').textContent = 'Edit Guru · ' + g.nama;
    document.getElementById('form-guru').action = "/admin/data-guru/" + g.id;
    document.getElementById('guru-method').value = 'PUT';

    document.getElementById('g-nama').value = g.nama || '';
    document.getElementById('g-nip').value = g.nip || '';
    document.getElementById('g-nuptk').value = g.nuptk || '';
    document.getElementById('g-jk').value = g.jenis_kelamin || 'L';
    document.getElementById('g-tmp').value = g.tempat_lahir || '';
    document.getElementById('g-tgl').value = g.tanggal_lahir ? g.tanggal_lahir.slice(0, 10) : '';
    document.getElementById('g-pend').value = g.pendidikan_terakhir || 'S1';
    document.getElementById('g-status').value = g.status_kepegawaian || 'PNS';
    document.getElementById('g-tmt').value = g.mulai_bertugas || '';
    document.getElementById('g-hp').value = g.nomor_hp || '';
    document.getElementById('g-email').value = g.email || '';
    document.getElementById('g-alamat').value = g.alamat || '';

    var waliKelas = (g.wali_kelas || [])[0] || null;
    document.getElementById('g-iswali').checked = Boolean(waliKelas);
    document.getElementById('g-wali').value = waliKelas && waliKelas.kelas ? waliKelas.kelas.nama : '';

    var assignments = g.mengajar || [];
    var assignmentsContainer = document.getElementById('g-teaching-assignments');
    assignmentsContainer.replaceChildren();
    nextTeachingAssignmentIndex = 0;
    document.getElementById('g-ismp').checked = assignments.length > 0;
    assignments.forEach(function(assignment) {
        var kelas = assignment.kelas ? assignment.kelas.nama : '';
        var mapel = assignment.mapel ? assignment.mapel.kode : '';
        addTeacherAssignmentRow(kelas, mapel);
    });
    if (!assignments.length) {
        addTeacherAssignmentRow();
    }
    syncGuruRoleFields();
}

syncGuruRoleFields();
</script>
@endpush
@endsection
