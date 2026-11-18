<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px">
    <div>
        <h3 style="margin:0;font-size:18px">Data Tenaga Pendidik &amp; Kependidikan</h3>
        <p class="sub" style="margin:3px 0 0">Kelola guru mata pelajaran, wali kelas, dan akun akses sistem</p>
    </div>
    
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:20px;font-size:12px;font-weight:700;color:var(--admin-primary)">
            <span>👨‍🏫 {{ $totalGuru ?? 0 }} Total Guru</span>
        </div>
        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:20px;font-size:12px;font-weight:700;color:#059669">
            <span>📚 {{ $totalGuruMapel ?? 0 }} Guru Mapel</span>
        </div>
        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:20px;font-size:12px;font-weight:700;color:#7c3aed">
            <span>🎓 {{ $totalWaliKelas ?? 0 }} Wali Kelas</span>
        </div>
    </div>
</div>

@if(session('sandi_sementara'))
<div class="note ok" style="margin-bottom:14px">
    🔑 Sandi sementara untuk <b>{{ session('sandi_sementara.username') }}</b> (hanya ditampilkan sekali ini):
    <code style="font-size:14px;font-weight:800;padding:2px 8px;background:#dcfce7;border-radius:6px;margin:0 4px">{{ session('sandi_sementara.password') }}</code>. Berikan kepada guru bersangkutan secara aman.
</div>
@endif

<div class="note" style="margin-bottom:14px">
    ℹ <b>Hak Akses:</b> Guru mapel berwenang mengedit nilai pada mata pelajaran di kelas yang diajarnya. Wali kelas berwenang memproses kenaikan kelas, kehadiran, dan ekstrakurikuler pada kelasnya. Seorang guru dapat memegang kedua peran sekaligus.
</div>

{{-- Card Daftar Guru --}}
<div class="card">
    <div class="bar" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <b>Daftar Tenaga Pendidik</b>
        </div>
        <span class="sp"></span>
        <button type="button" class="btn a" onclick="toggleModalGuru(true)">+ Tambah Guru Baru</button>
    </div>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Nama &amp; Gelar</th>
                    <th>NIP / NUPTK</th>
                    <th>Status</th>
                    <th>Peran</th>
                    <th>Mapel</th>
                    <th>Mengajar di</th>
                    <th>Wali Kelas</th>
                    <th>Kontak HP</th>
                    <th style="text-align:right">Aksi</th>
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
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:10px;background:var(--admin-primary-lighter);color:var(--admin-primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">
                                {{ mb_strtoupper(mb_substr($g->nama ?: 'G', 0, 1)) }}
                            </div>
                            <div>
                                <b style="color:var(--admin-text);font-size:13.5px">{{ $g->nama }}</b>
                                @if($g->email)
                                    <br><small style="color:var(--mu);font-size:11px">{{ $g->email }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <code>{{ $g->nip ?: '-' }}</code>
                        @if($g->nuptk)
                            <br><small style="color:var(--mu);font-size:11px">NUPTK: {{ $g->nuptk }}</small>
                        @endif
                    </td>
                    <td><span class="bd">{{ $g->status_kepegawaian ?? 'PNS' }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            @if($isMengajar)
                                <span class="bd ok" style="font-size:11px">Guru Mapel</span>
                            @endif
                            @if($isWali)
                                <span class="bd w" style="font-size:11px">Wali Kelas</span>
                            @endif
                            @if(!$isMengajar && !$isWali)
                                <span class="bd" style="font-size:11px">Guru</span>
                            @endif
                        </div>
                    </td>
                    <td style="max-width:180px;font-size:12.5px">{{ $mapelNames }}</td>
                    <td style="font-size:12.5px">{{ $kelasAjarNames }}</td>
                    <td>
                        @if($isWali)
                            <span class="bd" style="background:#eff6ff;color:var(--admin-primary);border:1px solid #bfdbfe;font-weight:700">Kelas {{ $waliKelasNama }}</span>
                        @else
                            <span style="color:var(--mu)">-</span>
                        @endif
                    </td>
                    <td style="font-size:12.5px">{{ $g->nomor_hp ?? '-' }}</td>
                    <td style="white-space:nowrap;text-align:right">
                        @if($g->akun)
                            <form action="{{ route('admin.data-guru.reset-sandi', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Reset sandi akun {{ $g->nama }}?')">
                                @csrf
                                <button type="submit" class="btn o" style="padding:4px 8px;font-size:11.5px" title="Reset Kata Sandi">🔑 Reset</button>
                            </form>
                        @else
                            <form action="{{ route('admin.data-guru.buat-akun', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Buat akun login untuk {{ $g->nama }}?')">
                                @csrf
                                <button type="submit" class="btn a" style="padding:4px 8px;font-size:11.5px" title="Buat Akun Akses">➕ Buat Akun</button>
                            </form>
                        @endif
                        <button type="button" class="btn o" style="padding:4px 8px;font-size:11.5px" onclick="editGuruData({{ \Illuminate\Support\Js::from($g) }})">✏ Edit</button>
                        <form action="{{ route('admin.data-guru.destroy', $g->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Hapus guru {{ $g->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn x" style="padding:4px 8px;font-size:11.5px" title="Hapus Data Guru">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:36px;color:var(--mu)">
                        <div style="font-size:32px;margin-bottom:8px">👨‍🏫</div>
                        <div style="font-size:14px;font-weight:600">Belum ada data guru tersimpan.</div>
                        <p class="sub" style="margin:4px 0 0">Klik tombol <b>+ Tambah Guru Baru</b> untuk mendaftarkan tenaga pendidik.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL TAMBAH / EDIT GURU --}}
<div id="modal-guru" style="display:none;position:fixed;inset:0;background:rgba(9,24,43,0.65);backdrop-filter:blur(6px);z-index:999;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
    <div class="card" style="max-width:760px;width:100%;max-height:92dvh;overflow-y:auto;margin:auto;background:#fff;border-radius:18px;box-shadow:0 20px 40px -10px rgba(9,24,43,0.4);border:1px solid #dce5ef;padding:24px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f6">
            <div>
                <h3 id="modal-title" style="margin:0;font-size:18px;color:var(--admin-text)">Tambah Guru</h3>
                <p class="sub" style="margin:3px 0 0">Lengkapi biodata dan tentukan peran penugasan guru di sekolah.</p>
            </div>
            <button type="button" class="btn x" onclick="toggleModalGuru(false)" style="padding:4px 8px;font-size:14px" aria-label="Tutup modal">✕</button>
        </div>

        <form id="form-guru" action="{{ route('admin.data-guru.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="guru-method" value="POST">

            <div class="fl" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px">
                <div style="grid-column:1/-1">
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Nama Lengkap &amp; Gelar *</label>
                    <input type="text" name="nama" id="g-nama" required style="width:100%" placeholder="mis. Drs. Budi Santoso, M.Pd">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">NIP / NIK</label>
                    <input type="text" name="nip" id="g-nip" style="width:100%">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">NUPTK</label>
                    <input type="text" name="nuptk" id="g-nuptk" style="width:100%">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="g-jk" style="width:100%">
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="g-tmp" style="width:100%">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="g-tgl" style="width:100%">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" id="g-pend" style="width:100%">
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                        <option value="D3">D3</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Status Kepegawaian</label>
                    <select name="status_kepegawaian" id="g-status" style="width:100%">
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                        <option value="GTT">GTT</option>
                        <option value="Honorer">Honorer</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Mulai Bertugas (Tahun)</label>
                    <input type="number" name="mulai_bertugas" id="g-tmt" placeholder="2018" style="width:100%">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">No. Handphone / WhatsApp</label>
                    <input type="text" name="nomor_hp" id="g-hp" style="width:100%" placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Email</label>
                    <input type="email" name="email" id="g-email" style="width:100%">
                </div>
                <div style="grid-column:1/-1">
                    <label style="font-size:11.5px;font-weight:700;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:0.04em">Alamat Lengkap</label>
                    <input type="text" name="alamat" id="g-alamat" style="width:100%">
                </div>
            </div>

            {{-- Peran Guru --}}
            <h4 style="margin:18px 0 10px;color:var(--admin-primary);font-size:14px;display:flex;align-items:center;gap:6px">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Peran &amp; Penugasan Guru
            </h4>

            <div class="card" style="margin:0 0 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px">
                <label style="display:flex;gap:8px;font-size:13px;font-weight:700;color:var(--admin-text);align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_mapel" id="g-ismp" value="1" onchange="syncGuruRoleFields()">
                    <span>Guru Mata Pelajaran — berwenang mengedit nilai mapelnya</span>
                </label>
                <fieldset id="g-mapel-fields" style="border:0;padding:0;margin:10px 0 0;min-width:0">
                    <div id="g-teaching-assignments" style="display:grid;gap:8px"></div>
                    <button type="button" class="btn o" style="margin-top:10px;font-size:12px" onclick="addTeacherAssignmentRow()">+ Tambah Penugasan Kelas &amp; Mapel</button>
                </fieldset>
            </div>

            <div class="card" style="margin:0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px">
                <label style="display:flex;gap:8px;font-size:13px;font-weight:700;color:var(--admin-text);align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_wali" id="g-iswali" value="1" onchange="syncGuruRoleFields()">
                    <span>Wali Kelas — berwenang memproses kenaikan kelas &amp; kehadiran</span>
                </label>
                <fieldset id="g-wali-fields" style="border:0;padding:0;margin:10px 0 0;min-width:0">
                    <select name="wali_kelas" id="g-wali" required style="width:100%;max-width:320px">
                        <option value="">— Pilih Kelas Binaan —</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </fieldset>
            </div>

            <template id="g-teaching-assignment-template">
                <div data-assignment-row class="bar" style="flex-wrap:wrap;gap:8px;background:#fff;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1">
                    <label style="flex:1;min-width:140px;font-size:11.5px;font-weight:600">Kelas
                        <select data-field="kelas" required style="width:100%;margin-top:3px">
                            <option value="">Pilih kelas</option>
                            @foreach($kelasList as $k)
                                <option value="{{ $k }}">{{ $k }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="flex:1;min-width:180px;font-size:11.5px;font-weight:600">Mata Pelajaran
                        <select data-field="mapel" required style="width:100%;margin-top:3px">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach($mapelList as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="btn x" data-remove-assignment aria-label="Hapus penugasan" style="padding:6px 10px;font-size:11px;align-self:flex-end">Hapus</button>
                </div>
            </template>

            <div class="bar" style="margin-top:18px;border-top:1px solid #e2e8f0;padding-top:14px">
                <span class="sp"></span>
                <button type="button" class="btn o" onclick="toggleModalGuru(false)">Batal</button>
                <button type="submit" class="btn a">💾 Simpan Data Guru</button>
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
