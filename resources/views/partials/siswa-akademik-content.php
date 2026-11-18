@php
    $academicHistories = $siswa->relationLoaded('riwayatKelasAkademik')
        ? $siswa->riwayatKelasAkademik
        : $siswa->riwayatKelas;
    $academicHistories = $academicHistories->sortBy('tahun_ajaran_id');
    $academicHistoryByGrade = $academicHistories->keyBy(fn ($history) =>
        $history->kelas?->tingkat ?? \App\Support\Semester::tingkatDariNamaKelas($history->kelas?->nama ?? '')
    );
    $academicManagePermissions = $academicManagePermissions ?? [];
    $canManageAcademicRecords = $canManageAcademicRecords ?? false;
    $academicMapelOptions = $academicMapelOptions ?? collect();
    $academicYearOptions = $academicYearOptions ?? collect();
    $academicClassOptions = $academicClassOptions ?? collect();
    $academicGrades = ['X', 'XI', 'XII'];
    $academicTerms = ['Ganjil', 'Genap'];
@endphp

@pushOnce('styles')
<style>
    .academic-record-section{display:grid;gap:12px;padding:16px;border:1px solid #dce5ef;border-radius:14px;background:#fff}
    .academic-record-section h4{margin:0;color:#12355b;font-size:13px}
    .academic-record-table-wrap{overflow-x:auto}
    .academic-record-table{width:100%;border-collapse:collapse;font-size:12px}
    .academic-record-table th,.academic-record-table td{padding:8px;border-bottom:1px solid #e8edf3;text-align:left;vertical-align:top}
    .academic-record-actions{display:flex;gap:5px;flex-wrap:wrap;margin-top:6px}
    .academic-record-actions form{margin:0}
    .academic-record-empty{margin:0;color:#64748b;font-size:12px}
    .academic-record-dialog{width:min(560px,calc(100% - 24px));max-height:90vh;padding:0;border:1px solid #dce5ef;border-radius:16px;background:#fff;color:#1b2430}
    .academic-record-dialog::backdrop{background:#09182b99}
    .academic-record-dialog form{display:grid;gap:12px;padding:18px}
    .academic-record-dialog h4{margin:0;color:#12355b}
    .academic-record-dialog label{display:grid;gap:5px;font-size:12px;font-weight:600}
    .academic-record-dialog input,.academic-record-dialog select,.academic-record-dialog textarea{width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}
    .academic-record-dialog textarea{min-height:90px;resize:vertical}
    .academic-record-dialog [data-academic-fields][hidden]{display:none}
    .academic-record-dialog .academic-record-dialog-actions{display:flex;justify-content:flex-end;gap:8px}
</style>
@endPushOnce

<div class="academic-records">
    <section class="academic-record-section">
        <h4>Nilai, Kehadiran, dan Ekstrakurikuler · 6 Slot Semester</h4>
        <div class="academic-record-table-wrap">
            <table class="academic-record-table">
                <thead>
                    <tr>
                        <th>Slot semester</th>
                        <th>Nilai</th>
                        <th>Kehadiran</th>
                        <th>Ekstrakurikuler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($academicGrades as $slotGrade)
                        @php
                            $history = $academicHistoryByGrade->get($slotGrade);
                            $historyClass = $history?->kelas?->nama;
                            $permission = $history ? ($academicManagePermissions[$history->id] ?? []) : [];
                            $mapelOptions = $canManageAcademicRecords
                                ? $academicMapelOptions
                                : ($permission['mapel_options'] ?? collect());
                            $mapelIds = $canManageAcademicRecords
                                ? $mapelOptions->modelKeys()
                                : ($permission['nilai_mapel_ids'] ?? []);
                            $slotClassOptions = $academicClassOptions->filter(fn ($classOption) =>
                                ($classOption->tingkat ?? \App\Support\Semester::tingkatDariNamaKelas($classOption->nama)) === $slotGrade
                            );
                            $canCreateHistoryForSlot = $canManageAcademicRecords
                                && $history === null
                                && $academicHistories->count() < 3
                                && $academicYearOptions->isNotEmpty()
                                && $slotClassOptions->isNotEmpty();
                            $mapelPayload = $mapelOptions->map(fn ($mapel) => [
                                'id' => $mapel->id,
                                'label' => $mapel->nama.' ('.strtoupper($mapel->kode).')',
                            ])->values();
                        @endphp
                        @foreach($academicTerms as $term)
                            @php
                                $globalSemester = \App\Support\Semester::nomor($slotGrade, $term);
                                $termGrades = $history?->nilai?->where('semester', $term) ?? collect();
                                $termAttendance = $history?->kehadiran?->firstWhere('semester', $term);
                                $termActivities = $history?->ekskul?->where('semester', $term) ?? collect();
                                $recordValues = $history
                                    ? ['riwayat_kelas_id' => $history->id, 'semester' => $term]
                                    : ['semester' => $term, 'tingkat' => $slotGrade];
                                $valuesPayload = json_encode($recordValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                            @endphp
                            <tr data-academic-slot="{{ $globalSemester }}">
                                <td>
                                    <b>Kelas {{ $slotGrade }} · Semester {{ $globalSemester }}</b><br>
                                    @if($history)
                                        {{ $history->tahunAjaran?->nama ?? 'Tahun ajaran tidak tersedia' }} · {{ $historyClass ?? 'Kelas belum ditetapkan' }}
                                    @else
                                        <span class="academic-record-empty">Riwayat kelas belum tersedia.</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($termGrades as $gradeRow)
                                        {{ $gradeRow->mapel?->nama ?? strtoupper($gradeRow->mapel?->kode ?? 'Mapel') }}: {{ $gradeRow->nilai_angka }}
                                        @if($canManageAcademicRecords || in_array((int) $gradeRow->mapel_id, $mapelIds, true))
                                            <div class="academic-record-actions">
                                                <button type="button" class="btn o" data-academic-open
                                                    data-type="nilai"
                                                    data-title="Ubah nilai"
                                                    data-update-url="{{ route('siswa.akademik.update', ['siswa' => $siswa->id, 'jenis' => 'nilai', 'record' => $gradeRow->id]) }}"
                                                    data-values="{{ json_encode(['riwayat_kelas_id' => $history->id, 'semester' => $term, 'mapel_id' => $gradeRow->mapel_id, 'nilai_angka' => $gradeRow->nilai_angka], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                                    data-mapel-options="{{ json_encode($mapelPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">Ubah</button>
                                                <form method="POST" action="{{ route('siswa.akademik.destroy', ['siswa' => $siswa->id, 'jenis' => 'nilai', 'record' => $gradeRow->id]) }}" onsubmit="return confirm('Hapus baris nilai ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn x">Hapus</button>
                                                </form>
                                            </div>
                                        @endif
                                        <br>
                                    @empty
                                        <p class="academic-record-empty">Belum ada data</p>
                                    @endforelse
                                    @if($history && ($canManageAcademicRecords || $mapelIds !== []) && $mapelOptions->isNotEmpty())
                                        <button type="button" class="btn o" data-academic-open
                                            data-type="nilai"
                                            data-title="Tambah nilai"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'nilai']) }}"
                                            data-values="{{ $valuesPayload }}"
                                            data-mapel-options="{{ json_encode($mapelPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">+ Nilai</button>
                                    @elseif($canCreateHistoryForSlot && $mapelOptions->isNotEmpty())
                                        <button type="button" class="btn o" data-academic-open data-history-required="true"
                                            data-type="nilai" data-title="Tambah nilai"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'nilai']) }}"
                                            data-values="{{ $valuesPayload }}"
                                            data-mapel-options="{{ json_encode($mapelPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">+ Nilai</button>
                                    @endif
                                </td>
                                <td>
                                    @if($termAttendance)
                                        Sakit {{ $termAttendance->sakit }}, Izin {{ $termAttendance->izin }}, Alpa {{ $termAttendance->alpa }}
                                        @if($termAttendance->kokurikuler)
                                            <br>{{ $termAttendance->kokurikuler }}
                                        @endif
                                    @else
                                        <p class="academic-record-empty">Belum ada data</p>
                                    @endif
                                    @if($termAttendance && ($canManageAcademicRecords || ($permission['kehadiran'] ?? false)))
                                        <div class="academic-record-actions">
                                            <button type="button" class="btn o" data-academic-open
                                                data-type="kehadiran"
                                                data-title="Ubah kehadiran"
                                                data-update-url="{{ route('siswa.akademik.update', ['siswa' => $siswa->id, 'jenis' => 'kehadiran', 'record' => $termAttendance->id]) }}"
                                                data-values="{{ json_encode(['riwayat_kelas_id' => $history->id, 'semester' => $term, 'sakit' => $termAttendance->sakit, 'izin' => $termAttendance->izin, 'alpa' => $termAttendance->alpa, 'kokurikuler' => $termAttendance->kokurikuler], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">Ubah</button>
                                            <form method="POST" action="{{ route('siswa.akademik.destroy', ['siswa' => $siswa->id, 'jenis' => 'kehadiran', 'record' => $termAttendance->id]) }}" onsubmit="return confirm('Hapus baris kehadiran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn x">Hapus</button>
                                            </form>
                                        </div>
                                    @endif
                                    @if($history && ! $termAttendance && ($canManageAcademicRecords || ($permission['kehadiran'] ?? false)))
                                        <button type="button" class="btn o" data-academic-open
                                            data-type="kehadiran"
                                            data-title="Tambah kehadiran"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'kehadiran']) }}"
                                            data-values="{{ $valuesPayload }}">+ Kehadiran</button>
                                    @elseif($canCreateHistoryForSlot)
                                        <button type="button" class="btn o" data-academic-open data-history-required="true"
                                            data-type="kehadiran" data-title="Tambah kehadiran"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'kehadiran']) }}"
                                            data-values="{{ $valuesPayload }}">+ Kehadiran</button>
                                    @endif
                                </td>
                                <td>
                                    @forelse($termActivities as $activity)
                                        {{ $activity->nama_kegiatan }}: {{ $activity->predikat ?: '-' }}
                                        @if($canManageAcademicRecords || ($permission['ekskul'] ?? false))
                                            <div class="academic-record-actions">
                                                <button type="button" class="btn o" data-academic-open
                                                    data-type="ekskul"
                                                    data-title="Ubah ekstrakurikuler"
                                                    data-update-url="{{ route('siswa.akademik.update', ['siswa' => $siswa->id, 'jenis' => 'ekskul', 'record' => $activity->id]) }}"
                                                    data-values="{{ json_encode(['riwayat_kelas_id' => $history->id, 'semester' => $term, 'nama_kegiatan' => $activity->nama_kegiatan, 'predikat' => $activity->predikat], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">Ubah</button>
                                                <form method="POST" action="{{ route('siswa.akademik.destroy', ['siswa' => $siswa->id, 'jenis' => 'ekskul', 'record' => $activity->id]) }}" onsubmit="return confirm('Hapus baris ekstrakurikuler ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn x">Hapus</button>
                                                </form>
                                            </div>
                                        @endif
                                        <br>
                                    @empty
                                        <p class="academic-record-empty">Belum ada data</p>
                                    @endforelse
                                    @if($history && ($canManageAcademicRecords || ($permission['ekskul'] ?? false)))
                                        <button type="button" class="btn o" data-academic-open
                                            data-type="ekskul"
                                            data-title="Tambah ekstrakurikuler"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'ekskul']) }}"
                                            data-values="{{ $valuesPayload }}">+ Ekskul</button>
                                    @elseif($canCreateHistoryForSlot)
                                        <button type="button" class="btn o" data-academic-open data-history-required="true"
                                            data-type="ekskul" data-title="Tambah ekstrakurikuler"
                                            data-store-url="{{ route('siswa.akademik.store', ['siswa' => $siswa->id, 'jenis' => 'ekskul']) }}"
                                            data-values="{{ $valuesPayload }}">+ Ekskul</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <dialog class="academic-record-dialog">
        <form method="POST" data-academic-form>
            @csrf
            <input type="hidden" name="_method" value="PUT" data-academic-method disabled>
            <h4 data-academic-title>Data akademik</h4>
            <input type="hidden" name="riwayat_kelas_id">
            <div data-academic-history-fields hidden>
                <p class="academic-record-empty">Riwayat untuk tingkat ini belum ada. Pilih tahun ajaran dan kelas untuk membuatnya.</p>
                <input type="hidden" name="tingkat" disabled>
                <label>Tahun ajaran
                    <select name="tahun_ajaran_id" disabled>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach($academicYearOptions as $yearOption)
                            <option value="{{ $yearOption->id }}">{{ $yearOption->nama }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kelas
                    <select name="kelas_id" disabled>
                        <option value="">Pilih kelas</option>
                        @foreach($academicClassOptions as $classOption)
                            <option value="{{ $classOption->id }}" data-grade="{{ $classOption->tingkat ?? \App\Support\Semester::tingkatDariNamaKelas($classOption->nama) }}">{{ $classOption->nama }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <label>Semester
                <select name="semester" required>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>
            </label>
            <div data-academic-fields="nilai" hidden>
                <label>Mata pelajaran
                    <select name="mapel_id" required></select>
                </label>
                <label>Nilai (0–100)
                    <input type="number" name="nilai_angka" min="0" max="100" step="0.01" required>
                </label>
            </div>
            <div data-academic-fields="kehadiran" hidden>
                <label>Sakit (hari)<input type="number" name="sakit" min="0" max="65535" step="1" required></label>
                <label>Izin (hari)<input type="number" name="izin" min="0" max="65535" step="1" required></label>
                <label>Alpa (hari)<input type="number" name="alpa" min="0" max="65535" step="1" required></label>
                <label>Catatan kokurikuler<textarea name="kokurikuler"></textarea></label>
            </div>
            <div data-academic-fields="ekskul" hidden>
                <label>Nama kegiatan<input type="text" name="nama_kegiatan" maxlength="100" required></label>
                <label>Predikat<input type="text" name="predikat" maxlength="30"></label>
            </div>
            <div class="academic-record-dialog-actions">
                <button type="button" class="btn o" data-academic-close>Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </dialog>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-academic-open]');
    if (trigger) {
        const wrapper = trigger.closest('.academic-records');
        const dialog = wrapper.querySelector('dialog');
        const form = dialog.querySelector('[data-academic-form]');
        const values = JSON.parse(trigger.dataset.values || '{}');
        const method = form.querySelector('[data-academic-method]');
        const type = trigger.dataset.type;
        const historyFields = dialog.querySelector('[data-academic-history-fields]');
        const requiresHistory = trigger.dataset.historyRequired === 'true';

        form.reset();
        form.action = trigger.dataset.updateUrl || trigger.dataset.storeUrl;
        method.disabled = !trigger.dataset.updateUrl;
        dialog.querySelector('[data-academic-title]').textContent = trigger.dataset.title;
        historyFields.hidden = !requiresHistory;
        historyFields.querySelectorAll('input,select').forEach(field => {
            field.disabled = !requiresHistory;
            field.required = requiresHistory && field.name !== 'tingkat';
        });
        dialog.querySelectorAll('[data-academic-fields]').forEach(fields => {
            fields.hidden = fields.dataset.academicFields !== type;
            fields.querySelectorAll('input,select,textarea').forEach(field => {
                field.disabled = fields.hidden;
            });
        });
        const selectedGrade = values.tingkat || '';
        const classSelect = historyFields.querySelector('[name="kelas_id"]');
        classSelect.querySelectorAll('option').forEach(option => {
            const matchesGrade = !option.value || option.dataset.grade === selectedGrade;
            option.hidden = !matchesGrade;
            option.disabled = !matchesGrade;
        });
        classSelect.value = '';

        const mapelSelect = form.elements.namedItem('mapel_id');
        if (type === 'nilai') {
            const options = JSON.parse(trigger.dataset.mapelOptions || '[]');
            mapelSelect.replaceChildren(new Option('Pilih mata pelajaran', ''));
            options.forEach(option => mapelSelect.add(new Option(option.label, option.id)));
        }

        Object.entries(values).forEach(([name, value]) => {
            const field = form.elements.namedItem(name);
            if (field) {
                field.value = value ?? '';
            }
        });
        dialog.showModal();
        return;
    }

    const close = event.target.closest('[data-academic-close]');
    if (close) {
        close.closest('dialog').close();
    }
});
</script>
@endPushOnce
