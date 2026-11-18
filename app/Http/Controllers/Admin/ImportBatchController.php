<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Admin\ImportBaris;
use App\Models\Admin\ImportUnggahan;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use App\Services\AksesGuru;
use App\Services\ImportDictionaryService;
use App\Services\PencocokSiswa;
use App\Support\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ImportBatchController extends Controller
{
    public function __construct(
        private readonly AksesGuru $aksesGuru,
        private readonly ImportDictionaryService $importDictionary,
        private readonly PencocokSiswa $pencocokSiswa
    ) {}

    public function previewMatch(Request $request): JsonResponse
    {
        $type = $this->canonicalImportType((string) $request->input('type', ''));
        if (! in_array($type, ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru'], true)) {
            return response()->json(['success' => false, 'message' => 'Jenis import tidak valid.'], 422);
        }

        if ($denied = $this->authorizeImport($request->user(), $type)) {
            return $denied;
        }

        if ($type === 'guru') {
            return response()->json(['success' => false, 'message' => 'Pencocokan siswa tidak berlaku untuk import guru.'], 422);
        }

        if ($request->boolean('create_riwayat') && $request->user()?->peran !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat membuat riwayat kelas saat import.',
            ], 403);
        }

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*' => ['required', 'array', 'max:250'],
            'semester' => ['nullable', 'string', 'max:20'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'create_riwayat' => ['sometimes', 'boolean'],
            'create_tahun_ajaran_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'create_kelas_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_kelas,id'],
        ]);

        $user = $request->user();
        $requestedYearName = trim((string) ($validated['tahun_ajaran'] ?? ''));
        if ($user->peran === 'guru' && $requestedYearName !== '') {
            $currentYearName = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('nama');
            if ($currentYearName === null || $this->normAcademicYear($requestedYearName) !== $currentYearName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guru hanya dapat mengisi data akademik pada tahun ajaran berjalan.',
                ], 403);
            }
        }
        $kelasInput = $validated['kelas'] ?? '';
        $semester = (string) ($validated['semester'] ?? 'auto');
        $createHistory = (bool) ($validated['create_riwayat'] ?? false);
        $mapelCache = $type === 'nilai' ? SekolahMapel::query()->get()->keyBy('kode') : null;
        $results = [];

        foreach ($validated['rows'] as $rowIndex => $rawRow) {
            $row = $this->normalizeImportRow($rawRow);
            $rowNumber = max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1));

            if ($type === 'siswa') {
                $match = $this->pencocokSiswa->cocokkan($row);
                $results[] = [
                    'index' => $rowIndex,
                    'row' => $rowNumber,
                    ...$this->previewMatchResult($match),
                ];

                continue;
            }

            $earlyClassName = $this->specificClassName(
                (string) ($row['kelas'] ?? ''),
                (string) ($row['__target_kelas'] ?? $kelasInput)
            );
            if ($user->peran === 'guru' && $earlyClassName !== null) {
                $accessError = $this->importAccessError(
                    $user,
                    $type === 'kehadiran' ? 'hadir' : $type,
                    [...$row, 'kelas' => $earlyClassName],
                    (int) $rowIndex,
                    $mapelCache
                );
                if ($accessError !== null) {
                    $results[] = [
                        'index' => $rowIndex,
                        'row' => $rowNumber,
                        ...$this->previewMatchResult($this->pencocokSiswa->hasilError($row, $accessError['reason'])),
                        'target' => null,
                    ];

                    continue;
                }
            }

            $target = $this->resolveImportTarget(
                $row,
                (int) $rowIndex,
                (string) ($row['__target_kelas'] ?? $kelasInput),
                (string) ($row['__target_semester'] ?? $semester),
                $createHistory,
                isset($validated['create_tahun_ajaran_id']) ? (int) $validated['create_tahun_ajaran_id'] : null,
                isset($validated['create_kelas_id']) ? (int) $validated['create_kelas_id'] : null,
                $user->peran === 'admin'
            );
            if ($target['error'] !== null) {
                $results[] = [
                    'index' => $rowIndex,
                    'row' => $rowNumber,
                    ...$this->previewMatchResult($target['match'] ?? $this->pencocokSiswa->hasilError($row, $target['error']['reason'])),
                    'level' => 'error',
                    'pesan' => $target['error']['reason'],
                    'target' => null,
                ];

                continue;
            }

            $accessRow = [...$row, 'kelas' => $target['kelas']->nama];
            $accessError = $this->importAccessError(
                $user,
                $type === 'kehadiran' ? 'hadir' : $type,
                $accessRow,
                (int) $rowIndex,
                $mapelCache
            );
            if ($accessError !== null) {
                $results[] = [
                    'index' => $rowIndex,
                    'row' => $rowNumber,
                    ...$this->previewMatchResult($target['match']),
                    'level' => 'error',
                    'pesan' => $accessError['reason'],
                    'target' => null,
                ];

                continue;
            }

            $result = [
                'index' => $rowIndex,
                'row' => $rowNumber,
                ...$this->previewMatchResult($target['match']),
                'target' => [
                    'kelas' => $target['kelas']->nama,
                    'tahun_ajaran' => $target['tahun']->nama,
                    'semester' => $target['semester'],
                    'riwayat_baru' => $target['new_history'],
                ],
            ];
            $results[] = $result;
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Endpoint untuk menerima data hasil parsing Excel fleksibel (dari SheetJS client-side).
     */
    public function metadata(Request $request): JsonResponse
    {
        $type = $this->canonicalImportType((string) $request->input('type', 'siswa'));
        if (! in_array($type, ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru'], true)) {
            return response()->json(['success' => false, 'message' => 'Jenis import tidak valid.'], 422);
        }

        if ($denied = $this->authorizeImport($request->user(), $type)) {
            return $denied;
        }

        $validated = $request->validate([
            'sheets' => ['sometimes', 'array', 'max:100'],
            'sheets.*.name' => ['nullable', 'string', 'max:100'],
            'sheets.*.headers' => ['required_with:sheets', 'array', 'max:250'],
            'sheets.*.headers.*' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json([
            'success' => true,
            'dictionaries' => $this->importDictionary->clientDictionaries($type),
            'formats' => $this->importDictionary->findFormats($type, $validated['sheets'] ?? []),
        ]);
    }

    public function startBatch(Request $request): JsonResponse
    {
        $type = $this->canonicalImportType((string) $request->input('type', 'siswa'));
        if (! in_array($type, ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru'], true)) {
            return response()->json(['success' => false, 'message' => 'Jenis import tidak valid.'], 422);
        }

        if ($denied = $this->authorizeImport($request->user(), $type)) {
            return $denied;
        }

        if ($request->boolean('create_riwayat') && $request->user()?->peran !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat membuat riwayat kelas saat import.',
            ], 403);
        }

        $validated = $request->validate([
            'file_name' => ['nullable', 'string', 'max:255'],
            'total_rows' => ['required', 'integer', 'min:1', 'max:100000'],
            'dup' => ['nullable', Rule::in(['skip', 'lengkapi', 'timpa'])],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'string', 'max:20'],
            'kelas' => ['nullable', 'string', 'max:30'],
            'create_riwayat' => ['sometimes', 'boolean'],
            'create_tahun_ajaran_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'create_kelas_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_kelas,id'],
            'format_id' => ['nullable', 'integer', 'exists:import_format_tersimpan,id'],
            'mapping_corrections' => ['sometimes', 'array', 'max:100'],
            'mapping_corrections.*.name' => ['nullable', 'string', 'max:100'],
            'mapping_corrections.*.header_row' => ['nullable', 'integer', 'min:1'],
            'mapping_corrections.*.headers' => ['required_with:mapping_corrections', 'array', 'max:250'],
            'mapping_corrections.*.headers.*' => ['nullable', 'string', 'max:120'],
            'mapping_corrections.*.mapping' => ['required_with:mapping_corrections', 'array', 'max:250'],
            'mapping_corrections.*.mapping.*' => ['nullable', 'string', 'max:60'],
            'mapping_corrections.*.corrected' => ['sometimes', 'array', 'max:250'],
            'mapping_corrections.*.corrected.*' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user();
        $isAcademicImport = in_array($type, ['nilai', 'kehadiran', 'ekskul'], true);
        $rawYear = trim((string) ($validated['tahun_ajaran'] ?? ''));
        $createHistory = (bool) ($validated['create_riwayat'] ?? false);
        $createYearId = $validated['create_tahun_ajaran_id'] ?? null;
        $createClassId = $validated['create_kelas_id'] ?? null;
        $year = $isAcademicImport
            ? ($createHistory && $createYearId !== null ? SekolahTahunAjaran::query()->whereKey($createYearId)->value('nama') : null)
            : ($rawYear === '' ? null : $this->normAcademicYear($rawYear));
        if (! $isAcademicImport && $rawYear !== '' && $year === null) {
            return response()->json([
                'success' => false,
                'message' => 'Format tahun ajaran tidak valid. Gunakan format seperti 2024/2025.',
            ], 422);
        }

        $corrections = $validated['mapping_corrections'] ?? [];
        if ($corrections !== [] && $user->peran !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat menyimpan koreksi kamus import.',
            ], 403);
        }

        $fileName = trim((string) ($validated['file_name'] ?? '')) ?: 'import.xlsx';
        $formatId = $validated['format_id'] ?? null;

        try {
            $upload = DB::transaction(function () use ($validated, $type, $user, $fileName, $year, $createHistory, $createClassId, $corrections, &$formatId): ImportUnggahan {
                if ($corrections !== []) {
                    $learnedFormats = $this->importDictionary->learnCorrections($type, $corrections, $user, $fileName);
                    $formatId = $learnedFormats->last()?->id ?? $formatId;
                }

                return $this->createUpload(
                    $user,
                    $type,
                    $fileName,
                    (int) $validated['total_rows'],
                    $year,
                    (string) ($validated['semester'] ?? ''),
                    $createHistory && $createClassId !== null
                        ? (string) SekolahKelas::query()->whereKey($createClassId)->value('nama')
                        : (string) ($validated['kelas'] ?? ''),
                    $formatId,
                    $corrections
                );
            });
        } catch (\Throwable $e) {
            Log::error('Gagal memulai import batch: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Batch import tidak dapat dimulai.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'batch_id' => $upload->id,
            'total_rows' => $upload->jumlah_baris,
        ], 201);
    }

    public function failBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:500'],
        ]);
        $upload = ImportUnggahan::query()
            ->whereKey($validated['batch_id'])
            ->where('pengguna_id', $request->user()?->id)
            ->first();

        if ($upload === null) {
            return response()->json(['success' => false, 'message' => 'Batch import tidak ditemukan.'], 404);
        }

        $upload->forceFill([
            'status' => 'gagal',
            'pesan_gagal' => $validated['message'],
            'selesai_at' => now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function showManagedBatch(Request $request, ImportUnggahan $batch): RedirectResponse
    {
        $this->academicTableForType($batch->jenis);

        $query = [
            'tab' => $batch->jenis,
            'batch' => $batch->id,
        ];
        if ($batch->tahun_ajaran_id !== null) {
            $query['tahun_ajaran_id'] = $batch->tahun_ajaran_id;
        }
        if ($request->boolean('edit')) {
            $query['edit'] = 1;
        }

        return redirect()->route('admin.import', $query);
    }

    /**
     * @return array{
     *     batch: ImportUnggahan,
     *     rows: Collection<int, ImportBaris>,
     *     records: Collection,
     *     tahunAjaran: ?SekolahTahunAjaran,
     *     kelas: ?SekolahKelas,
     *     editing: bool
     * }
     */
    public function managedBatchDetails(ImportUnggahan $batch, bool $editing): array
    {
        $this->academicTableForType($batch->jenis);
        $rows = ImportBaris::query()
            ->where('unggahan_id', $batch->id)
            ->orderBy('nomor_baris_excel')
            ->get();
        $tahunAjaran = $batch->tahun_ajaran_id === null ? null : SekolahTahunAjaran::query()->find($batch->tahun_ajaran_id);
        $kelas = $batch->kelas_id === null ? null : SekolahKelas::query()->find($batch->kelas_id);

        return [
            'batch' => $batch,
            'rows' => $rows,
            'records' => $this->recordsForBatch($batch),
            'tahunAjaran' => $tahunAjaran,
            'kelas' => $kelas,
            'editing' => $editing && $batch->status === 'selesai',
        ];
    }

    public function updateManagedRecord(Request $request, ImportUnggahan $batch, int $record): RedirectResponse
    {
        $this->academicTableForType($batch->jenis);
        abort_unless($batch->status === 'selesai', 404);

        $target = match ($batch->jenis) {
            'nilai' => SiswaNilai::query()->where('import_terakhir_unggahan_id', $batch->id)->findOrFail($record),
            'kehadiran' => SiswaKehadiran::query()->where('import_terakhir_unggahan_id', $batch->id)->findOrFail($record),
            'ekskul' => SiswaEkskul::query()->where('import_terakhir_unggahan_id', $batch->id)->findOrFail($record),
        };

        $validated = match ($batch->jenis) {
            'nilai' => $request->validate([
                'nilai_angka' => ['required', 'numeric', 'between:0,100'],
            ]),
            'kehadiran' => $request->validate([
                'sakit' => ['required', 'integer', 'between:0,65535'],
                'izin' => ['required', 'integer', 'between:0,65535'],
                'alpa' => ['required', 'integer', 'between:0,65535'],
                'kokurikuler' => ['nullable', 'string', 'max:4000'],
            ]),
            'ekskul' => $request->validate([
                'nama_kegiatan' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('siswa_ekskul', 'nama_kegiatan')
                        ->where('riwayat_kelas_id', $target->riwayat_kelas_id)
                        ->where('semester', $target->semester)
                        ->ignore($target->id),
                ],
                'predikat' => ['nullable', 'string', 'max:30'],
            ]),
        };

        $target->fill($validated)->save();

        CatatanAktivitas::create([
            'pengguna_id' => $request->user()->id,
            'peran' => $request->user()->peran,
            'aksi' => 'edit_batch_import_'.$batch->jenis,
            'keterangan' => "Koreksi data #{$target->id} pada batch import #{$batch->id}.",
            'objek_tipe' => $target->getTable(),
            'objek_id' => $target->id,
            'alamat_ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $query = [
            'tab' => $batch->jenis,
            'batch' => $batch->id,
            'edit' => 1,
        ];
        if ($batch->tahun_ajaran_id !== null) {
            $query['tahun_ajaran_id'] = $batch->tahun_ajaran_id;
        }

        return redirect()
            ->route('admin.import', $query)
            ->with('success', 'Baris hasil impor berhasil diperbarui.');
    }

    public function destroyManagedBatch(Request $request, ImportUnggahan $batch): RedirectResponse
    {
        $table = $this->academicTableForType($batch->jenis);
        $validated = $request->validate([
            'tahun_ajaran_id' => ['required', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'konfirmasi' => ['required', 'string', Rule::in(['HAPUS BATCH '.$batch->id])],
        ]);
        abort_unless((int) $validated['tahun_ajaran_id'] === (int) $batch->tahun_ajaran_id, 404);

        if ($batch->status !== 'selesai') {
            return back()->with('error', 'Hanya batch yang selesai yang dapat dihapus.');
        }

        $deletedCount = DB::transaction(function () use ($request, $batch, $table): int {
            $deleted = DB::table($table)->where('import_unggahan_id', $batch->id)->delete();
            if ($deleted === 0) {
                return 0;
            }

            $batch->forceFill(['status' => 'dibatalkan'])->save();
            CatatanAktivitas::create([
                'pengguna_id' => $request->user()->id,
                'peran' => $request->user()->peran,
                'aksi' => 'hapus_batch_import_'.$batch->jenis,
                'keterangan' => "Batch import #{$batch->id} dihapus; {$deleted} data {$batch->jenis} dihapus.",
                'objek_tipe' => 'import_unggahan',
                'objek_id' => $batch->id,
                'alamat_ip' => $request->ip(),
                'created_at' => now(),
            ]);

            return $deleted;
        });

        if ($deletedCount === 0) {
            return back()->with('error', 'Batch ini tidak memiliki data hasil yang tercatat untuk dihapus.');
        }

        return back()->with('success', "{$deletedCount} data milik batch #{$batch->id} berhasil dihapus.");
    }

    public function destroyAllForYear(Request $request, string $jenis): RedirectResponse
    {
        $table = $this->academicTableForType($jenis);
        $tahunAjaran = $request->validate([
            'tahun_ajaran_id' => ['required', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'konfirmasi' => [
                'required',
                'string',
                Rule::in(['HAPUS SEMUA '.mb_strtoupper($jenis).' '.(string) SekolahTahunAjaran::query()->whereKey($request->input('tahun_ajaran_id'))->value('nama')]),
            ],
        ]);
        $year = SekolahTahunAjaran::query()->findOrFail($tahunAjaran['tahun_ajaran_id']);

        $deletedCount = DB::transaction(function () use ($request, $jenis, $table, $year): int {
            $historyIds = DB::table('siswa_riwayat_kelas')
                ->where('tahun_ajaran_id', $year->id)
                ->select('id');
            $deleted = DB::table($table)->whereIn('riwayat_kelas_id', $historyIds)->delete();

            CatatanAktivitas::create([
                'pengguna_id' => $request->user()->id,
                'peran' => $request->user()->peran,
                'aksi' => 'hapus_semua_import_'.$jenis,
                'keterangan' => "Hapus semua data {$jenis} tahun ajaran {$year->nama}; {$deleted} baris dihapus.",
                'objek_tipe' => $table,
                'objek_id' => null,
                'alamat_ip' => $request->ip(),
                'created_at' => now(),
            ]);

            return $deleted;
        });

        $label = match ($jenis) {
            'nilai' => 'nilai',
            'kehadiran' => 'kehadiran',
            'ekskul' => 'ekstrakurikuler',
        };

        return back()->with('success', "{$deletedCount} data {$label} tahun ajaran {$year->nama} berhasil dihapus.");
    }

    public function saveBatch(Request $request): JsonResponse
    {
        $type = $this->canonicalImportType((string) $request->input('type', 'siswa'));
        if (! in_array($type, ['siswa', 'nilai', 'kehadiran', 'ekskul', 'guru'], true)) {
            return response()->json(['success' => false, 'message' => 'Jenis import tidak valid.'], 422);
        }

        if ($denied = $this->authorizeImport($request->user(), $type)) {
            return $denied;
        }

        $isAcademicImport = in_array($type, ['nilai', 'kehadiran', 'ekskul'], true);
        $requestedTaName = trim((string) $request->input('tahun_ajaran', ''));
        if ($isAcademicImport && $request->user()?->peran === 'guru' && $requestedTaName !== '') {
            $currentYearName = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('nama');
            if ($currentYearName === null || $this->normAcademicYear($requestedTaName) !== $currentYearName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guru hanya dapat mengisi data akademik pada tahun ajaran berjalan.',
                ], 403);
            }
        }
        $taName = $isAcademicImport || $requestedTaName === '' ? '' : $this->normAcademicYear($requestedTaName);
        $semesterBerjalan = DB::table('sekolah_pengaturan')
            ->where('kunci', 'semester_berjalan')
            ->value('isi');
        $semesterDefault = in_array($semesterBerjalan, ['Ganjil', 'Genap'], true)
            ? $semesterBerjalan
            : 'Genap';
        $semester = $request->input('semester', $semesterDefault);
        $mode = $request->input('mode', 'semua');
        $kelasInput = $request->input('kelas', '');
        $createHistory = $request->boolean('create_riwayat');
        if ($createHistory && $request->user()?->peran !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat membuat riwayat kelas saat import.',
            ], 403);
        }
        $targetOptions = $request->validate([
            'create_riwayat' => ['sometimes', 'boolean'],
            'create_tahun_ajaran_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_tahun_ajaran,id'],
            'create_kelas_id' => ['required_if:create_riwayat,1', 'nullable', 'integer', 'exists:sekolah_kelas,id'],
        ]);
        $createYearId = isset($targetOptions['create_tahun_ajaran_id']) ? (int) $targetOptions['create_tahun_ajaran_id'] : null;
        $createClassId = isset($targetOptions['create_kelas_id']) ? (int) $targetOptions['create_kelas_id'] : null;
        $validatedDup = $request->validate([
            'dup' => ['nullable', Rule::in(['skip', 'lengkapi', 'timpa'])],
        ]);
        $dup = $validatedDup['dup'] ?? ($type === 'siswa' ? 'lengkapi' : 'skip');
        $rawRows = $request->input('rows', []);
        $newClasses = $request->input('new_classes', []);
        $newMapel = $request->input('new_mapel', []);
        $user = auth()->user();
        if (! is_array($rawRows) || $rawRows === []) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data baris yang diterima untuk diimpor.',
            ], 422);
        }

        if (count($rawRows) > 200) {
            return response()->json([
                'success' => false,
                'message' => 'Setiap potongan import maksimal 200 baris.',
            ], 422);
        }

        if (! $isAcademicImport && $requestedTaName !== '' && $taName === null) {
            return response()->json([
                'success' => false,
                'message' => 'Format tahun ajaran tidak valid. Gunakan format seperti 2024/2025.',
            ], 422);
        }

        $rows = array_map(
            fn ($row): mixed => is_array($row) ? $this->normalizeImportRow($row) : $row,
            $rawRows
        );
        $batchId = $request->input('batch_id');
        $isNewBatch = $batchId === null;
        $upload = $isNewBatch
            ? null
            : ImportUnggahan::query()
                ->whereKey($batchId)
                ->where('pengguna_id', $user->id)
                ->where('jenis', $type)
                ->first();
        if (! $isNewBatch && $upload === null) {
            return response()->json([
                'success' => false,
                'message' => 'Batch import tidak ditemukan.',
            ], 404);
        }
        if ($upload !== null && in_array($upload->status, ['selesai', 'gagal', 'dibatalkan'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Batch import sudah ditutup.',
            ], 409);
        }

        $finalChunk = $isNewBatch || $request->boolean('final_chunk');
        $uploadYearName = $isAcademicImport
            ? ($createHistory && $createYearId !== null
                ? SekolahTahunAjaran::query()->whereKey($createYearId)->value('nama')
                : ($taName === '' ? null : $taName))
            : ($taName === '' ? null : $taName);

        try {
            if ($isNewBatch) {
                $upload = DB::transaction(fn (): ImportUnggahan => $this->createUpload(
                    $user,
                    $type,
                    trim((string) $request->input('file_name', '')) ?: 'import.xlsx',
                    count($rows),
                    $uploadYearName,
                    (string) $semester,
                    (string) $kelasInput,
                    $request->input('format_id'),
                    []
                ));
            }

            $result = DB::transaction(function () use ($request, $user, $type, $taName, $requestedTaName, $semester, $mode, $kelasInput, $dup, $rows, $rawRows, $newClasses, $newMapel, $isAcademicImport, $createHistory, $createYearId, $createClassId, $finalChunk, $upload): array {
                $activeUpload = $upload;
                $processedRows = (int) $activeUpload->jumlah_siap + count($rows);
                if ($processedRows > (int) $activeUpload->jumlah_baris
                    || ($finalChunk && $processedRows !== (int) $activeUpload->jumlah_baris)) {
                    throw new \InvalidArgumentException('Jumlah baris potongan tidak sesuai dengan jumlah baris batch.');
                }

                $createdYearIds = [];
                $createdHistoryIds = [];
                if ($taName !== '') {
                    $ta = ! $isAcademicImport && $user->peran === 'admin'
                        ? SekolahTahunAjaran::firstOrCreate(
                            ['nama' => $taName],
                            ['sedang_berjalan' => 0]
                        )
                        : SekolahTahunAjaran::where('nama', $taName)->first();
                    if ($ta?->wasRecentlyCreated) {
                        $createdYearIds[$ta->id] = true;
                    }
                } else {
                    $ta = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
                    if ($user->peran === 'admin' && ! $isAcademicImport) {
                        $ta ??= SekolahTahunAjaran::latest('id')->first()
                            ?? SekolahTahunAjaran::create(['nama' => '2024/2025', 'sedang_berjalan' => 1]);
                    }
                }

                if ($user->peran === 'admin' && $type !== 'siswa' && ! $isAcademicImport) {
                    $allClassNames = collect($rows)->pluck('kelas')->merge($newClasses)->unique()->filter()->values();
                    foreach ($allClassNames as $cName) {
                        $cName = $this->normK($cName);
                        if ($cName) {
                            $this->ensureKelas($cName);
                        }
                    }
                }

                if ($user->peran === 'admin' && ! empty($newMapel) && is_array($newMapel)) {
                    foreach ($newMapel as $mapelData) {
                        if (! empty($mapelData['kode']) && ! empty($mapelData['nama'])) {
                            $subject = SekolahMapel::firstOrCreate(
                                ['kode' => strtolower(substr($mapelData['kode'], 0, 3))],
                                ['nama' => $mapelData['nama']]
                            );
                            $this->importDictionary->learnSubjectAlias((string) $mapelData['nama'], $subject);
                        }
                    }
                }

                $savedCount = 0;
                $skippedCount = 0;
                $rowErrors = [];
                $unlinkedCount = 0;
                $activeStudentCount = 0;
                $graduatedStudentCount = 0;

                if ($type === 'siswa') {
                    [$savedCount, $skippedCount, $rowErrors, $unlinkedCount, $activeStudentCount, $graduatedStudentCount] = $this->saveSiswa(
                        $rows,
                        $ta,
                        $mode,
                        $kelasInput,
                        $dup,
                        $requestedTaName !== ''
                    );
                } elseif ($type === 'nilai') {
                    [$savedCount, $skippedCount, $rowErrors] = $this->saveNilai($rows, (string) $semester, (string) $kelasInput, $user, $dup, $createHistory, $createYearId, $createClassId, (int) $activeUpload->id, $createdHistoryIds);
                } elseif ($type === 'kehadiran') {
                    [$savedCount, $skippedCount, $rowErrors] = $this->saveKehadiran($rows, (string) $semester, (string) $kelasInput, $user, $dup, $createHistory, $createYearId, $createClassId, (int) $activeUpload->id, $createdHistoryIds);
                } elseif ($type === 'ekskul') {
                    [$savedCount, $skippedCount, $rowErrors] = $this->saveEkskul($rows, (string) $semester, (string) $kelasInput, $user, $dup, $createHistory, $createYearId, $createClassId, (int) $activeUpload->id, $createdHistoryIds);
                } elseif ($type === 'guru') {
                    [$savedCount, $skippedCount, $rowErrors] = $this->saveGuru($rows);
                }

                $auditCounts = $this->recordImportRows($activeUpload, $rawRows, $rows, $rowErrors, $savedCount);
                $activeUpload->forceFill([
                    'jumlah_siap' => $processedRows,
                    'jumlah_tersimpan' => (int) $activeUpload->jumlah_tersimpan + $savedCount,
                    'jumlah_error' => (int) $activeUpload->jumlah_error + $skippedCount,
                    'jumlah_peringatan' => (int) $activeUpload->jumlah_peringatan + $auditCounts['warnings'],
                    'status' => $finalChunk ? 'selesai' : 'siap_simpan',
                    'selesai_at' => $finalChunk ? now() : null,
                ])->save();

                if ($finalChunk) {
                    CatatanAktivitas::create([
                        'pengguna_id' => $user->id,
                        'peran' => $user->peran,
                        'aksi' => 'import_'.$type,
                        'keterangan' => "Import {$type} {$taName}: {$activeUpload->jumlah_tersimpan} baris tersimpan, {$activeUpload->jumlah_error} gagal/dilewati.",
                        'objek_tipe' => $type,
                        'objek_id' => $activeUpload->id,
                        'alamat_ip' => $request->ip(),
                    ]);
                }

                $creationSummary = '';
                if ($isAcademicImport && ($createdYearIds !== [] || $createdHistoryIds !== [])) {
                    $createdItems = [];
                    if ($createdYearIds !== []) {
                        $createdItems[] = count($createdYearIds).' tahun ajaran baru';
                    }
                    if ($createdHistoryIds !== []) {
                        $createdItems[] = count($createdHistoryIds).' riwayat kelas baru';
                    }
                    $creationSummary = ' (termasuk '.implode(' dan ', $createdItems).')';
                }

                return [
                    'success' => true,
                    'count' => $savedCount,
                    'skipped' => $skippedCount,
                    'active_count' => $activeStudentCount,
                    'graduated_count' => $graduatedStudentCount,
                    'created_years' => count($createdYearIds),
                    'created_histories' => count($createdHistoryIds),
                    'message' => "Berhasil mengimpor {$savedCount} data {$type}".($skippedCount > 0 ? " ({$skippedCount} baris gagal/dilewati)" : '').$creationSummary.'.'.($type === 'guru' ? ' Akun login dapat dibuat terpisah melalui menu Guru & Master.' : ''),
                    'errors' => $rowErrors,
                    'class_warning' => $type === 'siswa' && $unlinkedCount > 0
                        ? "{$unlinkedCount} biodata tersimpan tanpa riwayat kelas karena kelas atau tahun ajaran tidak dapat ditentukan."
                        : null,
                    'batch_id' => $activeUpload->id,
                    'processed_rows' => $processedRows,
                    'total_rows' => $activeUpload->jumlah_baris,
                    'completed' => $finalChunk,
                ];
            });

            return response()->json($result);
        } catch (\Throwable $e) {
            if ($upload !== null || isset($activeUpload)) {
                $failedUpload = $upload ?? $activeUpload;
                $failedUpload->forceFill([
                    'status' => 'gagal',
                    'pesan_gagal' => mb_substr($e->getMessage(), 0, 500),
                    'selesai_at' => now(),
                ])->save();
            }
            Log::error('Import error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba kembali dan sampaikan ID batch kepada administrator.',
                'batch_id' => $upload?->id,
            ], 500);
        }
    }

    private function authorizeImport(?Pengguna $user, string $type): ?JsonResponse
    {
        if ($user === null || ! in_array($user->peran, ['admin', 'guru'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: hanya admin dan guru yang dapat menggunakan impor.',
            ], 403);
        }

        if ($user->peran !== 'admin' && in_array($type, ['siswa', 'guru'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya admin yang diizinkan mengimpor data biodata siswa atau guru.',
            ], 403);
        }

        if ($user->peran === 'guru') {
            $hasAssignment = match ($type) {
                'nilai' => $this->aksesGuru->adalahGuruMapel($user),
                'kehadiran', 'ekskul' => $this->aksesGuru->adalahWali($user),
                default => true,
            };

            if (! $hasAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: guru tidak memiliki penugasan yang sesuai pada tahun ajaran berjalan.',
                ], 403);
            }
        }

        return null;
    }

    private function canonicalImportType(string $type): string
    {
        return $type === 'hadir' ? 'kehadiran' : $type;
    }

    /**
     * @param  array<int, array<string, mixed>>  $corrections
     */
    private function createUpload(
        Pengguna $user,
        string $type,
        string $fileName,
        int $totalRows,
        ?string $academicYear,
        string $semester,
        string $className,
        ?int $formatId,
        array $corrections
    ): ImportUnggahan {
        $academicYearId = $academicYear === null
            ? (in_array($type, ['nilai', 'kehadiran', 'ekskul'], true)
                ? null
                : SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('id'))
            : SekolahTahunAjaran::query()->where('nama', $academicYear)->value('id');
        $classId = $className === ''
            ? null
            : SekolahKelas::query()->where('nama', $this->normK($className))->value('id');
        $mapping = [];
        foreach ($corrections as $correction) {
            if (isset($correction['name'], $correction['mapping'])) {
                $mapping[$correction['name']] = $correction['mapping'];
            }
        }

        return ImportUnggahan::query()->create([
            'pengguna_id' => $user->id,
            'jenis' => $type,
            'nama_file' => mb_substr($fileName, 0, 255),
            'lokasi' => 'browser:'.mb_substr($fileName, 0, 240),
            'status' => 'diunggah',
            'format_tersimpan_id' => $formatId,
            'tahun_ajaran_id' => $academicYearId,
            'semester' => in_array($semester, ['Ganjil', 'Genap'], true) ? $semester : null,
            'kelas_id' => $classId,
            'jumlah_baris' => $totalRows,
            'jumlah_siap' => 0,
            'pemetaan_kolom' => $mapping === [] ? null : $mapping,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawRows
     * @param  array<int, mixed>  $rows
     * @param  array<int, array{row?: int, reason?: string, warning?: bool}>  $errors
     * @return array{warnings: int}
     */
    private function recordImportRows(ImportUnggahan $upload, array $rawRows, array $rows, array $errors, int $savedCount): array
    {
        $errorsByRow = [];
        foreach ($errors as $error) {
            if (isset($error['row'])) {
                $errorsByRow[(int) $error['row']][] = $error;
            }
        }

        $records = [];
        $warningCount = 0;
        foreach ($rows as $index => $row) {
            $rowNumber = is_array($row) ? max(1, (int) ($row['__excel_row'] ?? $index + 1)) : $index + 1;
            $rowErrors = $errorsByRow[$rowNumber] ?? [];
            $hasError = collect($rowErrors)->contains(fn (array $error): bool => ! ($error['warning'] ?? false));
            $hasWarning = collect($rowErrors)->contains(fn (array $error): bool => $error['warning'] ?? false);
            $status = $hasError ? 'error' : ($hasWarning ? 'peringatan' : 'tersimpan');
            if ($hasWarning) {
                $warningCount++;
            }
            $raw = $rawRows[$index] ?? null;
            $clean = $rows[$index] ?? null;
            $records[] = [
                'unggahan_id' => $upload->id,
                'nama_sheet' => null,
                'nomor_baris_excel' => $rowNumber,
                'data_asli' => $raw === null ? null : json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'data_bersih' => $clean === null ? null : json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'pesan' => $rowErrors === [] ? null : json_encode($rowErrors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        if ($records !== []) {
            ImportBaris::query()->insert($records);
        }

        return ['warnings' => $warningCount];
    }

    /**
     * Normalisasi nama kelas ke standar (contoh: '10 IPA 2' -> 'X-IPA 2')
     */
    private function normK($k)
    {
        $k = trim(strtoupper($k ?? ''));
        if (! $k || $k === '-') {
            return null;
        }
        $k = preg_replace('/^KELAS\s*/', '', $k);
        if (preg_match('/^(XII|XI|X|12|11|10)(?![A-Z])[\s\-._\/]*(.*)$/', $k, $m)) {
            $rom = ['10' => 'X', '11' => 'XI', '12' => 'XII'];
            $t = $rom[$m[1]] ?? $m[1];
            $r = trim(preg_replace('/[\s\-._]+$/', '', preg_replace('/\s+/', ' ', $m[2])));

            return $r ? "{$t}-{$r}" : "{$t}-1";
        }

        return $k;
    }

    private function specificClassName(string $rowClassName, string $classSelection): ?string
    {
        foreach ([$rowClassName, $classSelection] as $value) {
            $value = strtoupper(trim($value));
            if (in_array($value, ['', '*', 'AUTO', 'DEFAULT', 'X', 'XI', 'XII'], true)) {
                continue;
            }

            return $this->normK($value);
        }

        return null;
    }

    private function importAccessError(Pengguna $user, string $type, array $row, int $rowIndex, ?Collection $mapelCache = null): ?array
    {
        if ($user->peran === 'admin') {
            return null;
        }

        $rowNumber = max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1));
        $className = $this->normK($row['kelas'] ?? '');
        if ($className === null) {
            return ['row' => $rowNumber, 'reason' => 'Kelas harus dicantumkan pada setiap baris Excel agar hak akses dapat diverifikasi.'];
        }

        $kelas = SekolahKelas::query()->where('nama', $className)->first();
        if (! $kelas) {
            return ['row' => $rowNumber, 'reason' => "Kelas {$className} tidak ditemukan pada data kelas sekolah."];
        }

        if ($type === 'nilai') {
            $longSubjectName = $this->cleanVal($row['mapelcol'] ?? null);
            $longScore = $this->importNumber($row['nilaicol'] ?? null);
            if ($longSubjectName !== null && $longScore !== null) {
                $subject = $this->importDictionary->findSubject((string) $longSubjectName);
                if ($subject === null) {
                    return ['row' => $rowNumber, 'reason' => "Mapel {$longSubjectName} tidak ditemukan pada data mapel sekolah; baris ini dilewati."];
                }
                if (! $this->aksesGuru->bolehIsiNilai($user, (int) $kelas->id, (int) $subject->id)) {
                    return ['row' => $rowNumber, 'reason' => "Anda bukan guru mapel {$subject->nama} di kelas {$kelas->nama}."];
                }
            }

            $kolomIdentitas = ['nisn', 'nis', 'nomor_induk', 'nama', 'nama_lengkap', 'kelas', 'sem', 'semester', 'mode', 'ta', 'tahun_ajaran', 'id', '__excel_row', 'mapelcol', 'nilaicol'];
            foreach ($row as $key => $value) {
                $kodeMapel = strtolower(trim((string) $key));
                if (in_array($kodeMapel, $kolomIdentitas, true) || $value === '' || $value === null) {
                    continue;
                }

                $nilai = $this->importNumber($value);
                if ($nilai === null) {
                    continue;
                }
                if ($nilai < 0 || $nilai > 100) {
                    return ['row' => $rowNumber, 'reason' => "Nilai {$key} harus berada pada rentang 0–100."];
                }

                $mapel = $mapelCache?->get($kodeMapel);
                if (! $mapel) {
                    return ['row' => $rowNumber, 'reason' => "Mapel {$key} tidak ditemukan pada data mapel sekolah; baris ini dilewati."];
                }

                if (! $this->aksesGuru->bolehIsiNilai($user, (int) $kelas->id, (int) $mapel->id)) {
                    return ['row' => $rowNumber, 'reason' => "Anda bukan guru mapel {$mapel->nama} di kelas {$kelas->nama}."];
                }
            }

            return null;
        }

        if (! $this->aksesGuru->bolehIsiKehadiranEkskul($user, (int) $kelas->id)) {
            return ['row' => $rowNumber, 'reason' => "Anda bukan wali kelas {$kelas->nama}."];
        }

        return null;
    }

    private function normAcademicYear(string $year): ?string
    {
        if (! preg_match('/^((?:19|20)\d{2})\s*[\/-]\s*((?:19|20)\d{2}|\d{2})$/', trim($year), $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if (strlen($matches[2]) === 2) {
            $endYear += intdiv($startYear, 100) * 100;
            if ($endYear <= $startYear) {
                $endYear += 100;
            }
        }

        if ($endYear !== $startYear + 1) {
            return null;
        }

        return $startYear.'/'.$endYear;
    }

    private function ensureKelas($cName)
    {
        $cName = $this->normK($cName);
        if (! $cName) {
            return null;
        }
        $tk = 'X';
        if (str_starts_with($cName, 'XII')) {
            $tk = 'XII';
        } elseif (str_starts_with($cName, 'XI')) {
            $tk = 'XI';
        }

        // Pastikan format memenuhi cek_kelas_format: nama LIKE CONCAT(tingkat, '-%')
        if (! str_starts_with($cName, "{$tk}-")) {
            $cName = "{$tk}-".ltrim(substr($cName, strlen($tk)), '- ');
        }

        return SekolahKelas::firstOrCreate(
            ['nama' => $cName],
            ['tingkat' => $tk]
        );
    }

    /**
     * @return array{history: ?SiswaRiwayatKelas, created: bool, error: ?string}
     */
    private function getOrCreateRiwayatKelas(int $siswaId, int $taId, ?SekolahKelas $kelas): array
    {
        $riwayat = SiswaRiwayatKelas::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $taId)
            ->first();

        if (! $riwayat) {
            if (! $kelas) {
                return ['history' => null, 'created' => false, 'error' => null];
            }
            if (SiswaRiwayatKelas::where('siswa_id', $siswaId)->count() >= 3) {
                return [
                    'history' => null,
                    'created' => false,
                    'error' => 'Siswa sudah memiliki 3 riwayat kelas; riwayat ke-4 tidak dapat dibuat.',
                ];
            }

            $riwayat = SiswaRiwayatKelas::create([
                'siswa_id' => $siswaId,
                'tahun_ajaran_id' => $taId,
                'kelas_id' => $kelas->id,
                'status' => 'Aktif',
            ]);

            return ['history' => $riwayat, 'created' => true, 'error' => null];
        } elseif ($kelas && (int) $riwayat->kelas_id !== (int) $kelas->id) {
            return [
                'history' => null,
                'created' => false,
                'error' => 'Riwayat untuk tahun ajaran ini sudah ada pada kelas berbeda; kelas lama tidak diubah.',
            ];
        }

        return ['history' => $riwayat, 'created' => false, 'error' => null];
    }

    private function normDate($val): ?string
    {
        $val = $this->cleanVal($val);
        if ($val === null || $val === '0000-00-00') {
            return null;
        }

        if (is_numeric($val) && (float) $val > 0 && (float) $val < 80000) {
            $serial = (int) $val;
            $offset = $serial < 60 ? 1 : 0;
            $date = gmdate('Y-m-d', ($serial - 25569 + $offset) * 86400);

            return $date;
        }

        $val = trim((string) $val);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $val, $matches)) {
            [$year, $month, $day] = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        } elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $val, $matches)) {
            [$year, $month, $day] = [(int) $matches[3], (int) $matches[2], (int) $matches[1]];
        } elseif (preg_match('/^(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})$/u', $val, $matches)) {
            $months = [
                'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
            ];
            $month = $months[mb_strtolower($matches[2])] ?? 0;
            [$year, $day] = [(int) $matches[3], (int) $matches[1]];
        } else {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function normN(mixed $v): string
    {
        $value = trim((string) $v);
        if (is_numeric($v) && (str_contains(strtolower($value), 'e') || str_contains($value, '.'))) {
            $value = sprintf('%.0f', (float) $v);
        }
        $value = preg_replace('/\D/', '', $value) ?? '';

        return $value;
    }

    private function cleanVal(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
            if ($value === '' || in_array(mb_strtolower($value), ['-', 'x', 'n/a'], true)) {
                return null;
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeImportRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $value = $this->cleanVal($value);
                if (is_string($value) && preg_match('/^-?\d+,\d+$/', $value)) {
                    $value = str_replace(',', '.', $value);
                }
            }

            if (in_array($key, ['nisn'], true) && $value !== null) {
                $value = $this->normN($value);
            } elseif (in_array($key, ['nis', 'nomor_induk'], true) && is_numeric($value)
                && (str_contains(strtolower((string) $value), 'e') || str_contains((string) $value, '.'))) {
                $value = sprintf('%.0f', (float) $value);
            }

            if (in_array($key, ['tgl', 'tgl_masuk', 'tgl_ijazah_smp', 'tgl_keluar', 'tgl_ijazah_sma', 'tanggal_lahir', 'tanggal_diterima', 'tanggal_keluar', 'tanggal_ijazah_sma'], true)
                && $value !== null) {
                $value = $this->normDate($value) ?? $value;
            }

            $row[$key] = $value;
        }

        return $row;
    }

    private function importNumber(mixed $value): ?float
    {
        $value = $this->cleanVal($value);
        if (is_string($value)) {
            $value = preg_match('/^-?\d+,\d+$/', $value) ? str_replace(',', '.', $value) : $value;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array{0: int, 1: int, 2: array<int, array<string, mixed>>, 3: int, 4: int, 5: int}
     */
    private function saveSiswa(
        array $rows,
        ?SekolahTahunAjaran $ta,
        string $mode,
        string $kelasInput,
        string $duplicateMode,
        bool $formYearIsExplicit
    ): array {
        $saved = 0;
        $skipped = 0;
        $errors = [];
        $unlinked = 0;
        $active = 0;
        $graduated = 0;

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];

                continue;
            }
            $rowNumber = max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1));
            $rowTaName = null;
            $rawRowTaName = trim((string) ($r['tahun_ajaran'] ?? ''));
            if ($rawRowTaName !== '') {
                $rowTaName = $this->normAcademicYear($rawRowTaName);
                if ($rowTaName === null) {
                    $skipped++;
                    $errors[] = ['row' => $rowNumber, 'reason' => 'Format tahun ajaran tidak valid. Gunakan format seperti 2024/2025.'];

                    continue;
                }
            }
            $nisn = $this->normN($r['nisn'] ?? '');
            $nama = $this->cleanVal($r['nama'] ?? $r['nama_lengkap'] ?? '');
            $nis = $this->cleanVal($r['nis'] ?? $r['nomor_induk'] ?? null);
            if (! $nisn && ! $nama && ! $nis) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Baris kosong atau tidak memiliki identitas siswa.'];

                continue;
            }

            if (! $nama) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Nama lengkap wajib diisi.'];

                continue;
            }

            if (! preg_match('/^\d{10}$/', (string) $nisn)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NISN wajib terdiri dari tepat 10 digit.'];

                continue;
            }

            $nik = $this->cleanVal($r['nik'] ?? null);
            if (! empty($r['__numeric_nik'])) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NIK tersimpan sebagai angka di Excel dan berisiko kehilangan digit. Ubah kolom NIK menjadi teks lalu impor ulang.'];

                continue;
            }
            if ($nik !== null && ! preg_match('/^\d{16}$/', (string) $nik)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'NIK harus terdiri dari tepat 16 digit.'];

                continue;
            }

            $studentMatch = $this->pencocokSiswa->cocokkan($r);
            $notFoundForNewStudent = $studentMatch['level'] === 'error'
                && $studentMatch['metode'] === 'nisn'
                && str_starts_with((string) $studentMatch['pesan'], 'NISN tidak ditemukan;');
            if ($studentMatch['level'] === 'error' && ! $notFoundForNewStudent) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => $studentMatch['pesan'] ?? 'Siswa tidak dapat dicocokkan.'];

                continue;
            }
            $existingStudent = $studentMatch['student'];
            $nameMatchWarning = $studentMatch['level'] === 'peringatan'
                ? $studentMatch['pesan']
                : null;

            $jk = strtoupper(trim((string) ($r['jk'] ?? $r['jenis_kelamin'] ?? '')));
            if ($jk !== '' && ! in_array($jk, ['L', 'LAKI-LAKI', 'P', 'PEREMPUAN'], true)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Jenis kelamin harus L atau P.'];

                continue;
            }

            $kStr = $mode === 'semua' ? ($r['kelas'] ?? '') : $kelasInput;
            $kStr = $this->normK($kStr);
            if ($kStr !== null && mb_strlen($kStr) > 30) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Nama kelas melebihi batas 30 karakter.'];

                continue;
            }

            $data = [
                'nama_lengkap' => $nama ?: 'Siswa '.$nisn,
                'nama_panggilan' => $this->cleanVal($r['panggilan'] ?? null),
                'nisn' => $nisn,
                'nomor_induk' => $this->cleanVal($r['nis'] ?? $r['nomor_induk'] ?? null),
                'jenis_kelamin' => in_array($jk, ['P', 'PEREMPUAN'], true) ? 'P' : (in_array($jk, ['L', 'LAKI-LAKI'], true) ? 'L' : null),
                'tempat_lahir' => $this->cleanVal($r['tmp'] ?? $r['tempat_lahir'] ?? null),
                'tanggal_lahir' => $this->normDate($r['tgl'] ?? $r['tanggal_lahir'] ?? null),
                'agama' => $this->cleanVal($r['agama'] ?? null),
                'kewarganegaraan' => $this->cleanVal($r['kewarganegaraan'] ?? null),
                'nik' => $nik,
                'anak_ke' => is_numeric($r['anak_ke'] ?? null) ? (int) $r['anak_ke'] : null,
                'jumlah_saudara' => $this->cleanVal($r['jml_saudara'] ?? $r['jumlah_saudara'] ?? null),
                'status_yatim' => $this->cleanVal($r['yatim'] ?? $r['status_yatim'] ?? null),
                'bahasa_sehari_hari' => $this->cleanVal($r['bahasa'] ?? $r['bahasa_sehari_hari'] ?? null),
                'alamat_jalan' => $this->cleanVal($r['alamat'] ?? $r['alamat_jalan'] ?? null),
                'alamat_rt_rw' => $this->cleanVal($r['rt_rw'] ?? $r['alamat_rt_rw'] ?? null),
                'alamat_desa' => $this->cleanVal($r['kelurahan'] ?? $r['alamat_desa'] ?? null),
                'alamat_kecamatan' => $this->cleanVal($r['kecamatan'] ?? $r['alamat_kecamatan'] ?? null),
                'alamat_kabupaten' => $this->cleanVal($r['kab'] ?? $r['alamat_kabupaten'] ?? null),
                'alamat_provinsi' => $this->cleanVal($r['prov'] ?? $r['alamat_provinsi'] ?? null),
                'alamat_kode_pos' => $this->cleanVal($r['kodepos'] ?? $r['alamat_kode_pos'] ?? null),
                'nomor_hp' => $this->cleanVal($r['hp'] ?? $r['nomor_hp'] ?? null),
                'tempat_tinggal' => $this->cleanVal($r['tinggal'] ?? $r['tempat_tinggal'] ?? null),
                'jarak_ke_sekolah_km' => is_numeric($r['jarak'] ?? null) ? (float) $r['jarak'] : null,
                'golongan_darah' => $this->cleanVal($r['gol_darah'] ?? $r['golongan_darah'] ?? null),
                'penyakit' => $this->cleanVal($r['penyakit'] ?? null),
                'kelainan_fisik' => $this->cleanVal($r['kelainan'] ?? $r['kelainan_fisik'] ?? null),
                'tinggi_badan_cm' => is_numeric($r['tinggi'] ?? null) ? (int) $r['tinggi'] : null,
                'berat_badan_kg' => is_numeric($r['berat'] ?? null) ? (int) $r['berat'] : null,
                'sekolah_asal' => $this->cleanVal($r['asal'] ?? $r['sekolah_asal'] ?? null),
                'tanggal_ijazah_smp' => $this->normDate($r['tgl_ijazah_smp'] ?? null),
                'nomor_ijazah_smp' => $this->cleanVal($r['no_ijazah_smp'] ?? $r['nomor_ijazah_smp'] ?? null),
                'lama_belajar_tahun' => is_numeric($r['lama'] ?? null) ? (int) $r['lama'] : null,
                'jenis_registrasi' => $this->cleanVal($r['masuk'] ?? $r['jenis_registrasi'] ?? null),
                'pindahan_dari_sekolah' => $this->cleanVal($r['sekolah_pindah'] ?? $r['pindahan_dari_sekolah'] ?? null),
                'alasan_pindah' => $this->cleanVal($r['alasan_pindah'] ?? null),
                'kelas_diterima' => $this->cleanVal($r['kelas_masuk'] ?? $r['kelas_diterima'] ?? null),
                'rombel_diterima' => $this->cleanVal($r['rombel_masuk'] ?? $r['rombel_diterima'] ?? null),
                'tanggal_diterima' => $this->normDate($r['tgl_masuk'] ?? $r['tanggal_diterima'] ?? null),

                // Orang tua
                'nama_ayah' => $this->cleanVal($r['ayah'] ?? $r['nama_ayah'] ?? null),
                'agama_ayah' => $this->cleanVal($r['agama_ayah'] ?? null),
                'kewarganegaraan_ayah' => $this->cleanVal($r['wn_ayah'] ?? $r['kewarganegaraan_ayah'] ?? null),
                'pendidikan_ayah' => $this->cleanVal($r['pend_ayah'] ?? $r['pendidikan_ayah'] ?? null),
                'pekerjaan_ayah' => $this->cleanVal($r['kerja_ayah'] ?? $r['pekerjaan_ayah'] ?? null),
                'penghasilan_ayah' => $this->cleanVal($r['gaji_ayah'] ?? $r['penghasilan_ayah'] ?? null),
                'nomor_hp_ayah' => $this->cleanVal($r['hp_ayah'] ?? $r['nomor_hp_ayah'] ?? null),
                'keadaan_ayah' => $this->cleanVal($r['ket_ayah'] ?? $r['keadaan_ayah'] ?? null),

                'nama_ibu' => $this->cleanVal($r['ibu'] ?? $r['nama_ibu'] ?? null),
                'agama_ibu' => $this->cleanVal($r['agama_ibu'] ?? null),
                'kewarganegaraan_ibu' => $this->cleanVal($r['wn_ibu'] ?? $r['kewarganegaraan_ibu'] ?? null),
                'pendidikan_ibu' => $this->cleanVal($r['pend_ibu'] ?? $r['pendidikan_ibu'] ?? null),
                'pekerjaan_ibu' => $this->cleanVal($r['kerja_ibu'] ?? $r['pekerjaan_ibu'] ?? null),
                'penghasilan_ibu' => $this->cleanVal($r['gaji_ibu'] ?? $r['penghasilan_ibu'] ?? null),
                'nomor_hp_ibu' => $this->cleanVal($r['hp_ibu'] ?? $r['nomor_hp_ibu'] ?? null),
                'keadaan_ibu' => $this->cleanVal($r['ket_ibu'] ?? $r['keadaan_ibu'] ?? null),

                'ortu_alamat_jalan' => $this->cleanVal($r['al_jalan'] ?? $r['ortu_alamat_jalan'] ?? null),
                'ortu_alamat_rt_rw' => $this->cleanVal($r['al_rt'] ?? $r['ortu_alamat_rt_rw'] ?? null),
                'ortu_alamat_desa' => $this->cleanVal($r['al_desa'] ?? $r['ortu_alamat_desa'] ?? null),
                'ortu_alamat_kecamatan' => $this->cleanVal($r['al_kec'] ?? $r['ortu_alamat_kecamatan'] ?? null),
                'ortu_alamat_kabupaten' => $this->cleanVal($r['al_kab'] ?? $r['ortu_alamat_kabupaten'] ?? null),
                'ortu_alamat_provinsi' => $this->cleanVal($r['al_prov'] ?? $r['ortu_alamat_provinsi'] ?? null),
                'ortu_alamat_kode_pos' => $this->cleanVal($r['al_pos'] ?? $r['ortu_alamat_kode_pos'] ?? null),

                // Wali
                'nama_wali' => $this->cleanVal($r['wali'] ?? $r['nama_wali'] ?? null),
                'agama_wali' => $this->cleanVal($r['agama_wali'] ?? null),
                'kewarganegaraan_wali' => $this->cleanVal($r['wn_wali'] ?? $r['kewarganegaraan_wali'] ?? null),
                'pendidikan_wali' => $this->cleanVal($r['pend_wali'] ?? $r['pendidikan_wali'] ?? null),
                'pekerjaan_wali' => $this->cleanVal($r['kerja_wali'] ?? $r['pekerjaan_wali'] ?? null),
                'penghasilan_wali' => $this->cleanVal($r['gaji_wali'] ?? $r['penghasilan_wali'] ?? null),
                'nomor_hp_wali' => $this->cleanVal($r['hp_wali'] ?? $r['nomor_hp_wali'] ?? null),
                'wali_alamat_jalan' => $this->cleanVal($r['wali_jalan'] ?? $r['wali_alamat_jalan'] ?? null),
                'wali_alamat_rt_rw' => $this->cleanVal($r['wali_rt'] ?? $r['wali_alamat_rt_rw'] ?? null),
                'wali_alamat_desa' => $this->cleanVal($r['wali_desa'] ?? $r['wali_alamat_desa'] ?? null),
                'wali_alamat_kecamatan' => $this->cleanVal($r['wali_kec'] ?? $r['wali_alamat_kecamatan'] ?? null),
                'wali_alamat_kabupaten' => $this->cleanVal($r['wali_kab'] ?? $r['wali_alamat_kabupaten'] ?? null),
                'wali_alamat_provinsi' => $this->cleanVal($r['wali_prov'] ?? $r['wali_alamat_provinsi'] ?? null),
                'wali_alamat_kode_pos' => $this->cleanVal($r['wali_pos'] ?? $r['wali_alamat_kode_pos'] ?? null),

                // Keluar / Lulus
                'tanggal_keluar' => $this->normDate($r['tgl_keluar'] ?? $r['tanggal_keluar'] ?? null),
                'kelas_keluar' => $this->cleanVal($r['kelas_keluar'] ?? null),
                'alasan_keluar' => $this->cleanVal($r['alasan_keluar'] ?? null),
                'sekolah_tujuan' => $this->cleanVal($r['tujuan'] ?? $r['sekolah_tujuan'] ?? null),
                'nomor_peserta_psaj' => $this->cleanVal($r['nopes'] ?? $r['nomor_peserta_psaj'] ?? null),
                'nomor_skl' => $this->cleanVal($r['no_skl'] ?? $r['nomor_skl'] ?? null),
                'nomor_ijazah_sma' => $this->cleanVal($r['no_ijazah_sma'] ?? $r['nomor_ijazah_sma'] ?? null),
                'tanggal_ijazah_sma' => $this->normDate($r['tgl_ijazah_sma'] ?? $r['tanggal_ijazah_sma'] ?? null),
                'nomor_transkrip' => $this->cleanVal($r['no_transkrip'] ?? $r['nomor_transkrip'] ?? null),
                'tahun_lulus' => ! empty($r['thn_lulus']) ? (string) $r['thn_lulus'] : (! empty($r['tahun_lulus']) ? (string) $r['tahun_lulus'] : null),
                'kegiatan_setelah_lulus' => $this->cleanVal($r['lanjut'] ?? $r['kegiatan_setelah_lulus'] ?? null),
                'catatan' => $this->cleanVal($r['catatan'] ?? null),
            ];

            $profileUpdates = $existingStudent === null
                ? $data
                : $this->studentImportUpdates($existingStudent, $data, $duplicateMode);
            $effectiveData = $existingStudent === null
                ? $data
                : array_merge($existingStudent->only(array_keys($data)), $profileUpdates);
            $statusData = $effectiveData;
            foreach (['alasan_keluar', 'tahun_lulus', 'tanggal_keluar'] as $statusField) {
                if ($this->hasImportValue($data[$statusField] ?? null)) {
                    $statusData[$statusField] = $data[$statusField];
                }
            }
            $statusSiswa = $this->importedStudentStatus($r, $statusData);
            $hasGraduationSignal = $this->isGraduationText((string) ($r['status'] ?? ''))
                || $this->isGraduationText((string) ($data['alasan_keluar'] ?? ''))
                || (is_string($data['tahun_lulus']) && preg_match('/^\d{4}$/', $data['tahun_lulus']) === 1);
            if ($statusSiswa === 'Lulus') {
                $effectiveData['alasan_keluar'] = 'Lulus';
                $profileUpdates['alasan_keluar'] = 'Lulus';
                $statusData['alasan_keluar'] = 'Lulus';
                if (! $this->hasImportValue($effectiveData['tahun_lulus'] ?? null)) {
                    $graduationYear = $data['tahun_lulus']
                        ?? ($data['tanggal_keluar'] !== null ? substr($data['tanggal_keluar'], 0, 4) : null);
                    if ($graduationYear !== null) {
                        $effectiveData['tahun_lulus'] = $graduationYear;
                        $profileUpdates['tahun_lulus'] = $graduationYear;
                        $statusData['tahun_lulus'] = $graduationYear;
                    }
                }
            }

            $rowTa = null;
            if ($rowTaName !== null) {
                $rowTa = $ta !== null && $ta->nama === $rowTaName
                    ? $ta
                    : SekolahTahunAjaran::firstOrCreate(
                        ['nama' => $rowTaName],
                        ['sedang_berjalan' => false]
                    );
            } elseif ($formYearIsExplicit) {
                $rowTa = $ta;
            } else {
                $inferredYear = $this->inferStudentAcademicYear($statusData, $kStr, $statusSiswa);
                if ($inferredYear !== null) {
                    $rowTa = SekolahTahunAjaran::firstOrCreate(
                        ['nama' => $inferredYear],
                        ['sedang_berjalan' => false]
                    );
                } elseif ($statusSiswa !== 'Lulus') {
                    $rowTa = $ta;
                }
            }

            if ($statusSiswa === 'Lulus' && ! $this->hasImportValue($effectiveData['tahun_lulus'] ?? null) && $rowTa !== null) {
                $academicYearParts = explode('/', $rowTa->nama);
                $graduationYear = $academicYearParts[1] ?? null;
                if ($graduationYear !== null && preg_match('/^\d{4}$/', $graduationYear)) {
                    $effectiveData['tahun_lulus'] = $graduationYear;
                    $profileUpdates['tahun_lulus'] = $graduationYear;
                    $statusData['tahun_lulus'] = $graduationYear;
                }
            }

            if ($kStr === null && $existingStudent !== null && $rowTa !== null) {
                $existingHistory = SiswaRiwayatKelas::query()
                    ->where('siswa_id', $existingStudent->id)
                    ->where('tahun_ajaran_id', $rowTa->id)
                    ->first();
                $kStr = $existingHistory === null
                    ? null
                    : SekolahKelas::query()->whereKey($existingHistory->kelas_id)->value('nama');
            }

            $grade = $kStr === null ? null : Semester::tingkatDariNamaKelas($kStr);
            if ($statusSiswa === 'Lulus' && $grade !== null && $grade !== 'XII' && ! $hasGraduationSignal) {
                $statusSiswa = 'Aktif';
            }
            if ($statusSiswa === 'Lulus' && $kStr !== null && $grade !== 'XII') {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Siswa berstatus lulus harus memiliki riwayat pada kelas XII.'];

                continue;
            }
            if ($statusSiswa === 'Aktif' && ($rowTa === null || $kStr === null)) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'reason' => 'Siswa aktif wajib memiliki kelas dan tahun ajaran agar tampil di Data Siswa.',
                ];

                continue;
            }

            $validationError = $this->validateImportedStudent($data, $r)
                ?? $this->validateImportedStudent($effectiveData, $r);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => $validationError];

                continue;
            }

            try {
                DB::transaction(function () use ($effectiveData, $profileUpdates, $existingStudent, $kStr, $rowTa, $statusSiswa): void {
                    if ($existingStudent === null) {
                        $siswa = SiswaBiodata::create($effectiveData);
                    } else {
                        $siswa = $existingStudent;
                        if ($profileUpdates !== []) {
                            $siswa->forceFill($profileUpdates)->save();
                        }
                    }

                    if ($rowTa === null || $kStr === null) {
                        return;
                    }

                    $kelas = $this->ensureKelas($kStr);
                    if ($kelas === null) {
                        throw new \DomainException('Kelas tidak dapat ditentukan untuk riwayat tahun ajaran ini.');
                    }

                    $historyQuery = SiswaRiwayatKelas::query()
                        ->where('siswa_id', $siswa->id)
                        ->where('tahun_ajaran_id', $rowTa->id);
                    $history = $historyQuery->first();
                    if ($history !== null && (int) $history->kelas_id !== (int) $kelas->id) {
                        throw new \DomainException('Riwayat untuk tahun ajaran ini sudah ada pada kelas berbeda; kelas lama tidak diubah.');
                    }

                    if ($history === null) {
                        if (SiswaRiwayatKelas::query()->where('siswa_id', $siswa->id)->count() >= 3) {
                            throw new \DomainException('Siswa sudah memiliki 3 riwayat kelas; riwayat ke-4 tidak dapat dibuat.');
                        }

                        SiswaRiwayatKelas::create([
                            'siswa_id' => $siswa->id,
                            'tahun_ajaran_id' => $rowTa->id,
                            'kelas_id' => $kelas->id,
                            'status' => $statusSiswa,
                        ]);
                    } elseif ($history->status !== $statusSiswa) {
                        $history->forceFill(['status' => $statusSiswa])->save();
                    }
                });
            } catch (\DomainException $exception) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => $exception->getMessage()];

                continue;
            } catch (QueryException $exception) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Database menolak data baris ini karena duplikasi atau batasan kolom/relasi.'];
                Log::warning('Student import row rejected by database.', [
                    'row' => $rowNumber,
                    'nisn' => $nisn,
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($rowTa === null || $kStr === null) {
                $unlinked++;
                $reason = $statusSiswa === 'Lulus' && $rowTa === null
                    ? 'Tahun ajaran siswa lulus tidak dapat ditentukan; biodata disimpan tanpa riwayat kelas.'
                    : ($rowTa === null
                        ? 'Riwayat kelas tidak dibuat karena tahun ajaran tidak dapat ditentukan.'
                        : 'Riwayat kelas tidak dibuat karena kelas tidak dapat ditentukan.');
                $errors[] = ['row' => $rowNumber, 'reason' => $reason, 'warning' => true];
            }
            $saved++;
            if ($statusSiswa === 'Aktif') {
                $active++;
            } elseif ($statusSiswa === 'Lulus') {
                $graduated++;
            }
            foreach ($this->importedStudentDateWarnings($effectiveData) as $warning) {
                $errors[] = ['row' => $rowNumber, 'reason' => $warning, 'warning' => true];
            }
            if ($nameMatchWarning !== null) {
                $errors[] = ['row' => $rowNumber, 'reason' => $nameMatchWarning, 'warning' => true];
            }
        }

        return [$saved, $skipped, $errors, $unlinked, $active, $graduated];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function studentImportUpdates(SiswaBiodata $student, array $incoming, string $duplicateMode): array
    {
        if ($duplicateMode === 'skip') {
            return [];
        }

        $updates = [];
        foreach ($incoming as $field => $value) {
            if (! $this->hasImportValue($value)) {
                continue;
            }

            if ($duplicateMode === 'lengkapi' && $this->hasImportValue($student->getAttribute($field))) {
                continue;
            }

            $updates[$field] = $value;
        }

        return $updates;
    }

    private function hasImportValue(mixed $value): bool
    {
        return $value !== null && (! is_string($value) || trim($value) !== '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function inferStudentAcademicYear(array $data, ?string $className, string $status): ?string
    {
        $grade = $className === null ? null : Semester::tingkatDariNamaKelas($className);
        if ($grade === null && $this->hasImportValue($data['kelas_diterima'] ?? null)) {
            $acceptedClass = $this->normK((string) $data['kelas_diterima']);
            $grade = $acceptedClass === null ? null : Semester::tingkatDariNamaKelas($acceptedClass);
        }
        if ($grade === null && $status === 'Lulus') {
            $grade = 'XII';
        }

        $entryYear = null;
        $graduationYear = $data['tahun_lulus'] ?? null;
        if ($status === 'Lulus' && is_string($graduationYear) && preg_match('/^\d{4}$/', $graduationYear)) {
            $entryYear = (int) $graduationYear - 3;
        } elseif ($this->hasImportValue($data['tanggal_diterima'] ?? null)) {
            $entryYear = (int) substr((string) $data['tanggal_diterima'], 0, 4);
        }

        if ($grade === null || $entryYear === null) {
            return null;
        }

        try {
            return $this->normAcademicYear(Semester::tahunAjaranDariTahunMasuk($entryYear, $grade));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function importedStudentDateWarnings(array $data): array
    {
        $warnings = [];
        $today = now()->startOfDay();
        foreach ([
            'tanggal_lahir' => 'Tanggal lahir',
            'tanggal_diterima' => 'Tanggal diterima',
            'tanggal_keluar' => 'Tanggal keluar',
            'tanggal_ijazah_smp' => 'Tanggal ijazah SMP',
            'tanggal_ijazah_sma' => 'Tanggal ijazah SMA',
        ] as $field => $label) {
            if ($data[$field] === null) {
                continue;
            }

            $date = Carbon::createFromFormat('!Y-m-d', $data[$field]);
            if ($date !== false && $date->greaterThan($today)) {
                $warnings[] = "{$label} berada di masa depan.";
            }
        }

        if ($data['tanggal_lahir'] !== null) {
            $birthDate = Carbon::createFromFormat('!Y-m-d', $data['tanggal_lahir']);
            if ($birthDate !== false && $birthDate->lessThanOrEqualTo($today) && $birthDate->diffInYears($today) > 120) {
                $warnings[] = 'Usia berdasarkan tanggal lahir melebihi 120 tahun.';
            }
        }

        return array_values(array_unique($warnings));
    }

    private function validateImportedStudent(array $data, array $row): ?string
    {
        $maxLengths = [
            'nama_lengkap' => 150, 'nama_panggilan' => 64, 'nomor_induk' => 64,
            'tempat_lahir' => 100, 'agama' => 64, 'kewarganegaraan' => 64,
            'jumlah_saudara' => 64, 'status_yatim' => 64, 'bahasa_sehari_hari' => 64,
            'alamat_jalan' => 255, 'alamat_rt_rw' => 64, 'alamat_desa' => 100,
            'alamat_kecamatan' => 100, 'alamat_kabupaten' => 100, 'alamat_provinsi' => 100,
            'alamat_kode_pos' => 10, 'nomor_hp' => 64, 'tempat_tinggal' => 64,
            'golongan_darah' => 5, 'penyakit' => 255, 'kelainan_fisik' => 255,
            'sekolah_asal' => 150, 'tanggal_ijazah_smp' => 64, 'nomor_ijazah_smp' => 64,
            'jenis_registrasi' => 64, 'pindahan_dari_sekolah' => 150, 'alasan_pindah' => 255,
            'kelas_diterima' => 10, 'rombel_diterima' => 10,
            'nama_ayah' => 150, 'agama_ayah' => 64, 'kewarganegaraan_ayah' => 64,
            'pendidikan_ayah' => 64, 'pekerjaan_ayah' => 100, 'penghasilan_ayah' => 64,
            'nomor_hp_ayah' => 64, 'keadaan_ayah' => 64,
            'nama_ibu' => 150, 'agama_ibu' => 64, 'kewarganegaraan_ibu' => 64,
            'pendidikan_ibu' => 64, 'pekerjaan_ibu' => 100, 'penghasilan_ibu' => 64,
            'nomor_hp_ibu' => 64, 'keadaan_ibu' => 64,
            'ortu_alamat_jalan' => 255, 'ortu_alamat_rt_rw' => 64, 'ortu_alamat_desa' => 100,
            'ortu_alamat_kecamatan' => 100, 'ortu_alamat_kabupaten' => 100,
            'ortu_alamat_provinsi' => 100, 'ortu_alamat_kode_pos' => 10,
            'nama_wali' => 150, 'agama_wali' => 64, 'kewarganegaraan_wali' => 64,
            'pendidikan_wali' => 64, 'pekerjaan_wali' => 100, 'penghasilan_wali' => 64,
            'nomor_hp_wali' => 64, 'wali_alamat_jalan' => 255, 'wali_alamat_rt_rw' => 64,
            'wali_alamat_desa' => 100, 'wali_alamat_kecamatan' => 100,
            'wali_alamat_kabupaten' => 100, 'wali_alamat_provinsi' => 100,
            'wali_alamat_kode_pos' => 10, 'kelas_keluar' => 64, 'alasan_keluar' => 255,
            'sekolah_tujuan' => 150, 'nomor_peserta_psaj' => 64, 'nomor_skl' => 64,
            'nomor_ijazah_sma' => 64, 'nomor_transkrip' => 64,
            'kegiatan_setelah_lulus' => 255,
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if ($data[$field] !== null && mb_strlen((string) $data[$field]) > $maxLength) {
                return "Kolom {$field} melebihi batas {$maxLength} karakter.";
            }
        }

        foreach ([
            'tanggal_lahir' => ['tgl', 'tanggal_lahir'],
            'tanggal_diterima' => ['tgl_masuk', 'tanggal_diterima'],
            'tanggal_keluar' => ['tgl_keluar', 'tanggal_keluar'],
            'tanggal_ijazah_smp' => ['tgl_ijazah_smp', 'tanggal_ijazah_smp'],
            'tanggal_ijazah_sma' => ['tgl_ijazah_sma', 'tanggal_ijazah_sma'],
        ] as $field => $sourceFields) {
            $sourceValue = null;
            foreach ($sourceFields as $sourceField) {
                if (! empty($row[$sourceField])) {
                    $sourceValue = $row[$sourceField];
                    break;
                }
            }
            if ($sourceValue !== null && $sourceValue !== '' && $sourceValue !== '-' && $data[$field] === null) {
                return "Format tanggal pada kolom {$field} tidak valid.";
            }
        }

        $numericRules = [
            'anak_ke' => ['anak_ke', 255, true],
            'jarak_ke_sekolah_km' => ['jarak', 9999.99, false],
            'tinggi_badan_cm' => ['tinggi', 65535, true],
            'berat_badan_kg' => ['berat', 65535, true],
            'lama_belajar_tahun' => ['lama', 255, true],
        ];
        foreach ($numericRules as $field => [$sourceField, $maximum, $integerOnly]) {
            $value = $row[$sourceField] ?? null;
            if ($value === null || $value === '' || $value === '-') {
                continue;
            }
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > $maximum
                || ($integerOnly && (float) $value !== (float) (int) $value)) {
                return "Nilai pada kolom {$sourceField} tidak valid.";
            }
        }

        $tahunLulus = $data['tahun_lulus'];
        if ($tahunLulus !== null && ! preg_match('/^\d{4}$/', $tahunLulus)) {
            return 'Tahun lulus harus terdiri dari 4 digit.';
        }

        return null;
    }

    private function importedStudentStatus(array $row, array $data): string
    {
        $reason = mb_strtolower(trim((string) ($data['alasan_keluar'] ?? '')));
        $status = mb_strtolower(trim((string) ($row['status'] ?? '')));
        $isGraduated = $this->isGraduationText($reason)
            || $this->isGraduationText($status)
            || $data['tahun_lulus'] !== null;

        if ($isGraduated) {
            return 'Lulus';
        }

        return [
            'aktif' => 'Aktif',
            'pindah keluar' => 'Pindah keluar',
            'keluar' => 'Keluar',
        ][$status] ?? 'Aktif';
    }

    private function isGraduationText(string $value): bool
    {
        if (preg_match('/\b(?:belum|tidak|gagal)\s+(?:lulus|tamat)\b/u', $value) === 1) {
            return false;
        }

        return preg_match('/\b(?:lulus|alumni|graduate(?:d)?|tamat)\b/u', $value) === 1;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     match?: array<string, mixed>,
     *     student?: ?SiswaBiodata,
     *     history?: ?SiswaRiwayatKelas,
     *     kelas?: SekolahKelas,
     *     tahun?: SekolahTahunAjaran,
     *     semester?: string,
     *     new_history?: bool,
     *     error: ?array{row: int, reason: string},
     *     warning?: array{row: int, reason: ?string, warning: bool}
     * }
     */
    private function resolveImportTarget(
        array $row,
        int $rowIndex,
        string $classSelection,
        string $semesterSelection,
        bool $createHistory,
        ?int $createYearId,
        ?int $createClassId,
        bool $isAdmin
    ): array {
        $rowNumber = max(1, (int) ($row['__excel_row'] ?? $rowIndex + 1));
        $teacherYear = null;
        if (! $isAdmin) {
            $teacherYear = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->first();
            if ($teacherYear === null) {
                return [
                    'error' => ['row' => $rowNumber, 'reason' => 'Tidak ada tahun ajaran berjalan; guru tidak dapat mengisi data akademik.'],
                ];
            }

            $rowYearName = $this->cleanVal($row['tahun_ajaran'] ?? $row['ta'] ?? null);
            if ($rowYearName !== null && $this->normAcademicYear((string) $rowYearName) !== $teacherYear->nama) {
                return [
                    'error' => ['row' => $rowNumber, 'reason' => 'Data tahun ajaran selain tahun berjalan hanya dapat diisi oleh admin.'],
                ];
            }
        }

        $rowClassName = $this->normK($row['kelas'] ?? '');
        $selection = strtoupper(trim($classSelection));
        $selection = in_array($selection, ['', '*', 'AUTO', 'DEFAULT'], true)
            ? null
            : (in_array($selection, ['X', 'XI', 'XII'], true) ? $selection : $this->normK($selection));
        $selectedGrade = in_array($selection, ['X', 'XI', 'XII'], true)
            ? $selection
            : ($selection === null ? null : Semester::tingkatDariNamaKelas($selection));
        $rowGrade = $rowClassName === null ? null : Semester::tingkatDariNamaKelas($rowClassName);

        if ($selectedGrade !== null && $rowGrade !== null && $selectedGrade !== $rowGrade) {
            return [
                'error' => ['row' => $rowNumber, 'reason' => "Tingkat kelas pada baris ({$rowGrade}) berbeda dari pilihan ({$selectedGrade})."],
            ];
        }

        $className = in_array($selection, ['X', 'XI', 'XII'], true)
            ? $rowClassName
            : ($selection ?? $rowClassName);
        $grade = $selectedGrade ?? $rowGrade;
        if ($grade === null) {
            return [
                'error' => ['row' => $rowNumber, 'reason' => 'Kelas atau tingkat tidak terdeteksi. Pilih kelas/tingkat atau isi kolom kelas pada file Excel.'],
            ];
        }

        $kelas = $className === null
            ? null
            : SekolahKelas::query()->where('nama', $className)->first();
        if ($className !== null && $kelas === null) {
            return [
                'error' => ['row' => $rowNumber, 'reason' => "Kelas {$className} tidak ditemukan pada data kelas sekolah."],
            ];
        }
        if (! $isAdmin && $kelas === null) {
            return [
                'error' => ['row' => $rowNumber, 'reason' => 'Pilih kelas tugas yang spesifik atau cantumkan kelas pada setiap baris Excel.'],
            ];
        }

        $match = $this->pencocokSiswa->cocokkan(
            $row,
            $teacherYear?->id,
            $createHistory ? null : $kelas,
            $grade,
            false,
            $teacherYear !== null
        );
        if (
            $teacherYear !== null
            && $match['level'] === 'error'
            && str_starts_with((string) $match['pesan'], 'Kelas pada file berbeda dari riwayat siswa')
        ) {
            $match = $this->pencocokSiswa->hasilError($row, 'bukan siswa kelas Anda.');
        }
        if ($match['level'] === 'error' || $match['student'] === null) {
            if (
                $teacherYear !== null
                && $match['metode'] === 'nama'
                && $match['pesan'] === 'Siswa dengan identitas tersebut tidak ditemukan.'
            ) {
                $unscopedMatch = $this->pencocokSiswa->cocokkan($row);
                if ($unscopedMatch['student'] !== null || isset($unscopedMatch['kandidat'])) {
                    $match = $this->pencocokSiswa->hasilError($row, 'bukan siswa kelas Anda.');
                }
            }

            return [
                'match' => $match,
                'error' => ['row' => $rowNumber, 'reason' => $match['pesan'] ?? 'Siswa tidak dapat diidentifikasi.'],
            ];
        }

        $historyQuery = SiswaRiwayatKelas::query()
            ->where('siswa_id', $match['student']->id)
            ->whereHas('kelas', fn ($query) => $query->where('tingkat', $grade));
        if ($teacherYear !== null) {
            $historyQuery
                ->where('tahun_ajaran_id', $teacherYear->id)
                ->where('status', 'Aktif')
                ->whereHas('siswa', fn (Builder $query): Builder => $query->aktif((int) $teacherYear->id));
        }
        if ($kelas !== null) {
            $historyQuery->where('kelas_id', $kelas->id);
        }
        $histories = $historyQuery->with(['kelas', 'tahunAjaran'])->get();
        $newHistory = false;
        $history = null;

        if ($histories->count() > 1) {
            $classes = $histories->pluck('kelas.nama')->filter()->unique()->values();
            $reason = $classes->count() === 1
                ? "Riwayat kelas {$classes->first()} ditemukan pada beberapa tahun ajaran; isi tahun_ajaran di file untuk menentukan target."
                : "Siswa memiliki beberapa riwayat kelas tingkat {$grade}; pilih kelas spesifik atau isi kelas di file.";

            return [
                'match' => $match,
                'error' => ['row' => $rowNumber, 'reason' => $reason],
            ];
        }

        if ($histories->count() === 1) {
            $history = $histories->first();
            $kelas = $history->kelas;
            $tahun = $history->tahunAjaran;
        } else {
            if (! $createHistory) {
                return [
                    'match' => $match,
                    'error' => [
                        'row' => $rowNumber,
                        'reason' => $teacherYear !== null
                            ? 'bukan siswa kelas Anda.'
                            : "Siswa belum punya riwayat kelas {$grade}.",
                    ],
                ];
            }
            if (! $isAdmin) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => 'Hanya admin yang dapat membuat riwayat kelas saat import.'],
                ];
            }

            $tahun = $createYearId === null ? null : SekolahTahunAjaran::query()->find($createYearId);
            $kelas = $createClassId === null ? null : SekolahKelas::query()->find($createClassId);
            if ($tahun === null || $kelas === null) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => 'Pilih tahun ajaran dan kelas untuk membuat riwayat baru.'],
                ];
            }
            if ($kelas->tingkat !== $grade || ($className !== null && $className !== $kelas->nama)) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => "Kelas pembuatan riwayat harus sesuai dengan tingkat {$grade} dan kelas yang dipilih."],
                ];
            }

            $historyForYear = SiswaRiwayatKelas::query()
                ->where('siswa_id', $match['student']->id)
                ->where('tahun_ajaran_id', $tahun->id)
                ->first();
            if ($historyForYear !== null) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => 'Siswa sudah memiliki riwayat pada tahun ajaran pilihan di kelas berbeda.'],
                ];
            }
            if (SiswaRiwayatKelas::query()->where('siswa_id', $match['student']->id)->count() >= 3) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => 'Siswa sudah memiliki 3 riwayat kelas; riwayat ke-4 tidak dapat dibuat.'],
                ];
            }

            $semesterInfo = $this->semesterInfoForRow($row, $semesterSelection, $kelas->nama);
            if ($semesterInfo['error'] !== null) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => $semesterInfo['error']],
                ];
            }

            $newHistory = true;
        }

        $className = $kelas?->nama;
        if ($className === null) {
            return [
                'match' => $match,
                'error' => ['row' => $rowNumber, 'reason' => 'Kelas pada riwayat siswa tidak ditemukan pada data kelas sekolah.'],
            ];
        }

        if (! isset($semesterInfo)) {
            $semesterInfo = $this->semesterInfoForRow($row, $semesterSelection, $className);
            if ($semesterInfo['error'] !== null) {
                return [
                    'match' => $match,
                    'error' => ['row' => $rowNumber, 'reason' => $semesterInfo['error']],
                ];
            }
        }

        $target = [
            'match' => $match,
            'student' => $match['student'],
            'history' => $history,
            'kelas' => $kelas,
            'tahun' => $tahun,
            'semester' => $semesterInfo['term'],
            'new_history' => $newHistory,
            'error' => null,
        ];
        if ($match['level'] === 'peringatan') {
            $target['warning'] = [
                'row' => $rowNumber,
                'reason' => $match['pesan'],
                'warning' => true,
            ];
        }

        return $target;
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    private function previewMatchResult(array $match): array
    {
        $student = $match['student'] ?? null;

        return [
            'student' => $student === null ? null : [
                'id' => $student->id,
                'nama_lengkap' => $student->nama_lengkap,
                'nisn' => $student->nisn,
                'nomor_induk' => $student->nomor_induk,
                'kelas' => $student->riwayatKelas
                    ->map(fn (SiswaRiwayatKelas $history): ?string => $history->kelas?->nama)
                    ->filter()
                    ->unique()
                    ->implode(', '),
            ],
            'metode' => $match['metode'],
            'level' => $match['level'],
            'pesan' => $match['pesan'],
            ...(isset($match['kandidat']) ? ['kandidat' => $match['kandidat']] : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<int, bool>  $createdHistoryIds
     */
    private function persistTargetHistory(array &$target, array &$createdHistoryIds): ?string
    {
        if (! ($target['new_history'] ?? false)) {
            return null;
        }

        $result = $this->getOrCreateRiwayatKelas(
            (int) $target['student']->id,
            (int) $target['tahun']->id,
            $target['kelas']
        );
        if ($result['history'] === null) {
            return $result['error'] ?? 'Riwayat kelas baru tidak dapat dibuat.';
        }

        $target['history'] = $result['history'];
        if ($result['created']) {
            $createdHistoryIds[$result['history']->id] = true;
        }

        return null;
    }

    /**
     * @return array{number: ?int, grade: ?string, term: ?string, error: ?string}
     */
    private function semesterInfoForRow(array $row, string $selection, string $className): array
    {
        $grade = Semester::tingkatDariNamaKelas($className);
        if ($grade === null) {
            return ['number' => null, 'grade' => null, 'term' => null, 'error' => 'Tingkat kelas harus X, XI, atau XII agar dapat dipetakan ke enam semester.'];
        }

        $rawSemester = trim((string) ($row['sem'] ?? ''));
        $rowNumber = preg_match('/^(?:semester|smt|sem)?\s*([1-6])$/i', $rawSemester, $numberMatch)
            ? (int) $numberMatch[1]
            : null;
        $rowTerm = preg_match('/ganjil/i', $rawSemester)
            ? 'Ganjil'
            : (preg_match('/genap/i', $rawSemester) ? 'Genap' : null);
        $selectedNumber = ctype_digit($selection) ? (int) $selection : null;
        if ($selectedNumber !== null && ($selectedNumber < 1 || $selectedNumber > 6)) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => 'Nomor semester harus antara 1 dan 6.'];
        }
        $selectedTerm = match (strtolower($selection)) {
            'ganjil', 'local-ganjil' => 'Ganjil',
            'genap', 'local-genap' => 'Genap',
            default => null,
        };
        $rowGlobalNumber = $rowNumber === null
            ? null
            : ($rowNumber <= 2 ? Semester::nomor($grade, Semester::jenisDariNomor($rowNumber)) : $rowNumber);
        $selectedGlobalNumber = $selectedNumber === null
            ? null
            : ($selectedNumber <= 2 ? Semester::nomor($grade, Semester::jenisDariNomor($selectedNumber)) : $selectedNumber);

        if ($rowGlobalNumber !== null && $selectedGlobalNumber !== null && $rowGlobalNumber !== $selectedGlobalNumber) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => "Semester pada baris Excel ({$rowNumber}) berbeda dari semester yang dipilih ({$selectedNumber}) untuk kelas {$grade}."];
        }

        if ($rowTerm !== null && $selectedTerm !== null && $rowTerm !== $selectedTerm) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => "Jenis semester pada baris Excel ({$rowTerm}) berbeda dari jenis semester yang dipilih ({$selectedTerm})."];
        }

        $number = $selectedGlobalNumber ?? $rowGlobalNumber;
        $term = $selectedTerm ?? $rowTerm;
        if ($number === null && $term !== null) {
            $number = Semester::nomor($grade, $term);
        }
        if ($number === null) {
            return ['number' => null, 'grade' => $grade, 'term' => null, 'error' => 'Semester tidak ditentukan. Pilih semester 1–6 atau isi kolom sem pada file Excel.'];
        }

        $numberTerm = Semester::jenisDariNomor($number);
        if ($rowTerm !== null && $rowTerm !== $numberTerm) {
            return ['number' => $number, 'grade' => $grade, 'term' => null, 'error' => "Jenis semester pada baris Excel ({$rowTerm}) tidak sesuai dengan nomor semester {$number} ({$numberTerm})."];
        }
        if ($selectedTerm !== null && $selectedTerm !== $numberTerm) {
            return ['number' => $number, 'grade' => $grade, 'term' => null, 'error' => "Semester yang dipilih ({$selectedTerm}) tidak sesuai dengan nomor semester pada baris ({$numberTerm})."];
        }

        $numberGrade = Semester::tingkatDariNomor($number);
        $term = $numberTerm;

        if ($numberGrade !== $grade) {
            return ['number' => $number, 'grade' => $grade, 'term' => $term, 'error' => "Kelas {$className} tidak sesuai dengan semester {$number} yang dipilih (semester {$number} harus kelas {$numberGrade})."];
        }

        return ['number' => $number, 'grade' => $grade, 'term' => $term, 'error' => null];
    }

    private function academicTableForType(string $type): string
    {
        return match ($type) {
            'nilai' => 'siswa_nilai',
            'kehadiran' => 'siswa_kehadiran',
            'ekskul' => 'siswa_ekskul',
            default => abort(404),
        };
    }

    private function recordsForBatch(ImportUnggahan $batch): Collection
    {
        $relations = ['riwayatKelas.siswa', 'riwayatKelas.kelas'];

        return match ($batch->jenis) {
            'nilai' => SiswaNilai::query()
                ->where('import_terakhir_unggahan_id', $batch->id)
                ->with([...$relations, 'mapel'])
                ->orderBy('id')
                ->get(),
            'kehadiran' => SiswaKehadiran::query()
                ->where('import_terakhir_unggahan_id', $batch->id)
                ->with($relations)
                ->orderBy('id')
                ->get(),
            'ekskul' => SiswaEkskul::query()
                ->where('import_terakhir_unggahan_id', $batch->id)
                ->with($relations)
                ->orderBy('id')
                ->get(),
            default => abort(404),
        };
    }

    private function saveNilai(
        array $rows,
        string $semester,
        string $kelasInput,
        Pengguna $user,
        string $duplicateMode,
        bool $createHistory,
        ?int $createYearId,
        ?int $createClassId,
        int $uploadId,
        array &$createdHistoryIds
    ): array {
        $saved = 0;
        $skipped = 0;
        $errors = [];
        $mapelCache = SekolahMapel::all()->keyBy('kode');

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];

                continue;
            }

            $earlyClassName = $this->specificClassName(
                (string) ($r['kelas'] ?? ''),
                (string) ($r['__target_kelas'] ?? $kelasInput)
            );
            if ($user->peran === 'guru' && $earlyClassName !== null) {
                $accessError = $this->importAccessError($user, 'nilai', [...$r, 'kelas' => $earlyClassName], (int) $rowIndex, $mapelCache);
                if ($accessError !== null) {
                    $skipped++;
                    $errors[] = $accessError;

                    continue;
                }
            }

            $target = $this->resolveImportTarget(
                $r,
                (int) $rowIndex,
                (string) ($r['__target_kelas'] ?? $kelasInput),
                (string) ($r['__target_semester'] ?? $semester),
                $createHistory,
                $createYearId,
                $createClassId,
                $user->peran === 'admin'
            );
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];

                continue;
            }

            $accessError = $this->importAccessError(
                $user,
                'nilai',
                [...$r, 'kelas' => $target['kelas']->nama],
                (int) $rowIndex,
                $mapelCache
            );
            if ($accessError !== null) {
                $skipped++;
                $errors[] = $accessError;

                continue;
            }

            $scores = [];
            $invalidScore = null;
            $longSubjectName = $this->cleanVal($r['mapelcol'] ?? null);
            $longScoreValue = $this->cleanVal($r['nilaicol'] ?? null);
            if ($longSubjectName !== null || $longScoreValue !== null) {
                $longScore = $this->importNumber($longScoreValue);
                $subject = $longSubjectName === null ? null : $this->importDictionary->findSubject((string) $longSubjectName);
                if ($longSubjectName === null || $longScoreValue === null || $longScore === null) {
                    $invalidScore = 'Kolom mapel dan nilai wajib diisi dengan nilai angka.';
                } elseif ($subject === null) {
                    $invalidScore = "Mapel {$longSubjectName} tidak ditemukan pada data mapel sekolah.";
                } elseif ($longScore < 0 || $longScore > 100) {
                    $invalidScore = 'Nilai harus berada pada rentang 0–100.';
                } else {
                    $scores[] = [$subject, $longScore];
                }
            }

            foreach ($r as $key => $val) {
                $kLow = strtolower(trim($key));
                if (in_array($kLow, ['nisn', 'nis', 'nomor_induk', 'nama', 'nama_lengkap', 'kelas', 'sem', 'semester', 'mode', 'ta', 'tahun_ajaran', 'id', '__excel_row', 'mapelcol', 'nilaicol'], true)) {
                    continue;
                }
                if ($val === '' || $val === null) {
                    continue;
                }

                $numVal = $this->importNumber($val);
                if ($numVal === null) {
                    continue;
                }
                if ($numVal < 0 || $numVal > 100) {
                    $invalidScore = "Nilai {$key} harus berada pada rentang 0–100.";
                    break;
                }

                $mapel = $mapelCache->get($kLow);
                if (! $mapel && $user->peran === 'admin') {
                    $mapel = SekolahMapel::firstOrCreate(
                        ['kode' => substr($kLow, 0, 3)],
                        ['nama' => strtoupper($key)]
                    );
                    $mapelCache->put($mapel->kode, $mapel);
                }
                if (! $mapel) {
                    continue;
                }

                $scores[] = [$mapel, $numVal];
            }

            if ($invalidScore !== null) {
                $skipped++;
                $errors[] = ['row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)), 'reason' => $invalidScore];

                continue;
            }

            if ($scores === []) {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Tidak ada nilai mapel yang valid untuk disimpan.',
                ];

                continue;
            }

            $historyError = $this->persistTargetHistory($target, $createdHistoryIds);
            if ($historyError !== null) {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => $historyError,
                ];

                continue;
            }
            $riwayat = $target['history'];
            $sem = $target['semester'];

            $changed = false;
            $duplicateSkipped = false;
            foreach ($scores as [$mapel, $numVal]) {
                $nilai = SiswaNilai::query()->firstOrNew([
                    'riwayat_kelas_id' => $riwayat->id,
                    'semester' => $sem,
                    'mapel_id' => $mapel->id,
                ]);
                if ($nilai->exists && $duplicateMode !== 'timpa') {
                    $duplicateSkipped = true;

                    continue;
                }

                $nilai->nilai_angka = $numVal;
                $nilai->save();
                $tracking = ['import_terakhir_unggahan_id' => $uploadId];
                if ($nilai->wasRecentlyCreated) {
                    $tracking['import_unggahan_id'] = $uploadId;
                }
                $nilai->forceFill($tracking)->save();
                $changed = true;
            }
            if (! $changed && $duplicateSkipped) {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Nilai untuk semester dan mapel tersebut sudah ada; data dilewati.',
                ];

                continue;
            }
            if ($changed) {
                $saved++;
            }
            if ($duplicateSkipped) {
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Nilai yang sudah ada dilewati sesuai mode duplikat.',
                    'warning' => true,
                ];
            }
            if (isset($target['warning'])) {
                $errors[] = $target['warning'];
            }
        }

        return [$saved, $skipped, $errors];
    }

    private function saveKehadiran(
        array $rows,
        string $semester,
        string $kelasInput,
        Pengguna $user,
        string $duplicateMode,
        bool $createHistory,
        ?int $createYearId,
        ?int $createClassId,
        int $uploadId,
        array &$createdHistoryIds
    ): array {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];

                continue;
            }

            $earlyClassName = $this->specificClassName(
                (string) ($r['kelas'] ?? ''),
                (string) ($r['__target_kelas'] ?? $kelasInput)
            );
            if ($user->peran === 'guru' && $earlyClassName !== null) {
                $accessError = $this->importAccessError($user, 'hadir', [...$r, 'kelas' => $earlyClassName], (int) $rowIndex);
                if ($accessError !== null) {
                    $skipped++;
                    $errors[] = $accessError;

                    continue;
                }
            }

            $target = $this->resolveImportTarget(
                $r,
                (int) $rowIndex,
                (string) ($r['__target_kelas'] ?? $kelasInput),
                (string) ($r['__target_semester'] ?? $semester),
                $createHistory,
                $createYearId,
                $createClassId,
                $user->peran === 'admin'
            );
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];

                continue;
            }

            $accessError = $this->importAccessError(
                $user,
                'hadir',
                [...$r, 'kelas' => $target['kelas']->nama],
                (int) $rowIndex
            );
            if ($accessError !== null) {
                $skipped++;
                $errors[] = $accessError;

                continue;
            }

            $historyError = $this->persistTargetHistory($target, $createdHistoryIds);
            if ($historyError !== null) {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => $historyError,
                ];

                continue;
            }
            $riwayat = $target['history'];
            $sem = $target['semester'];

            $kehadiran = SiswaKehadiran::query()->firstOrNew([
                'riwayat_kelas_id' => $riwayat->id,
                'semester' => $sem,
            ]);
            if ($kehadiran->exists && $duplicateMode !== 'timpa') {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Data kehadiran untuk semester tersebut sudah ada; data dilewati.',
                ];

                continue;
            }
            $kehadiran->fill([
                'sakit' => (int) ($r['sakit'] ?? 0),
                'izin' => (int) ($r['izin'] ?? 0),
                'alpa' => (int) ($r['alpa'] ?? 0),
                'kokurikuler' => $this->cleanVal($r['kokur'] ?? $r['kokurikuler'] ?? null),
            ])->save();
            $tracking = ['import_terakhir_unggahan_id' => $uploadId];
            if ($kehadiran->wasRecentlyCreated) {
                $tracking['import_unggahan_id'] = $uploadId;
            }
            $kehadiran->forceFill($tracking)->save();

            $saved++;
            if (isset($target['warning'])) {
                $errors[] = $target['warning'];
            }
        }

        return [$saved, $skipped, $errors];
    }

    private function saveEkskul(
        array $rows,
        string $semester,
        string $kelasInput,
        Pengguna $user,
        string $duplicateMode,
        bool $createHistory,
        ?int $createYearId,
        ?int $createClassId,
        int $uploadId,
        array &$createdHistoryIds
    ): array {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $r) {
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowIndex + 1, 'reason' => 'Format baris tidak valid.'];

                continue;
            }

            $earlyClassName = $this->specificClassName(
                (string) ($r['kelas'] ?? ''),
                (string) ($r['__target_kelas'] ?? $kelasInput)
            );
            if ($user->peran === 'guru' && $earlyClassName !== null) {
                $accessError = $this->importAccessError($user, 'ekskul', [...$r, 'kelas' => $earlyClassName], (int) $rowIndex);
                if ($accessError !== null) {
                    $skipped++;
                    $errors[] = $accessError;

                    continue;
                }
            }

            $target = $this->resolveImportTarget(
                $r,
                (int) $rowIndex,
                (string) ($r['__target_kelas'] ?? $kelasInput),
                (string) ($r['__target_semester'] ?? $semester),
                $createHistory,
                $createYearId,
                $createClassId,
                $user->peran === 'admin'
            );
            if ($target['error'] !== null) {
                $skipped++;
                $errors[] = $target['error'];

                continue;
            }

            $accessError = $this->importAccessError(
                $user,
                'ekskul',
                [...$r, 'kelas' => $target['kelas']->nama],
                (int) $rowIndex
            );
            if ($accessError !== null) {
                $skipped++;
                $errors[] = $accessError;

                continue;
            }

            $sem = $target['semester'];
            $namaKeg = $this->cleanVal($r['ekskul'] ?? $r['nama_kegiatan'] ?? 'Pramuka');
            $predikat = $this->cleanVal($r['predikat'] ?? 'A');

            if ($namaKeg) {
                $historyError = $this->persistTargetHistory($target, $createdHistoryIds);
                if ($historyError !== null) {
                    $skipped++;
                    $errors[] = [
                        'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                        'reason' => $historyError,
                    ];

                    continue;
                }
                $riwayat = $target['history'];
                $ekskul = SiswaEkskul::query()->firstOrNew([
                    'riwayat_kelas_id' => $riwayat->id,
                    'semester' => $sem,
                    'nama_kegiatan' => $namaKeg,
                ]);
                if ($ekskul->exists && $duplicateMode !== 'timpa') {
                    $skipped++;
                    $errors[] = [
                        'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                        'reason' => "Data ekskul {$namaKeg} untuk semester tersebut sudah ada; data dilewati.",
                    ];

                    continue;
                }
                $ekskul->predikat = $predikat;
                $ekskul->save();
                $tracking = ['import_terakhir_unggahan_id' => $uploadId];
                if ($ekskul->wasRecentlyCreated) {
                    $tracking['import_unggahan_id'] = $uploadId;
                }
                $ekskul->forceFill($tracking)->save();
                $saved++;
                if (isset($target['warning'])) {
                    $errors[] = $target['warning'];
                }
            } else {
                $skipped++;
                $errors[] = [
                    'row' => max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)),
                    'reason' => 'Nama kegiatan ekstrakurikuler kosong.',
                ];
            }
        }

        return [$saved, $skipped, $errors];
    }

    private function saveGuru($rows)
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $r) {
            $rowNumber = is_array($r) ? max(1, (int) ($r['__excel_row'] ?? $rowIndex + 1)) : $rowIndex + 1;
            if (! is_array($r)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Format baris tidak valid.'];

                continue;
            }
            $nama = $this->cleanVal($r['nama'] ?? '');
            $nip = $this->cleanVal($r['nip'] ?? null);
            if (! $nama && ! $nip) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Nama dan NIP guru kosong.'];

                continue;
            }

            $guru = null;
            if ($nip) {
                $guru = GuruBiodata::where('nip', $nip)->first();
            }
            if (! $guru) {
                $guru = GuruBiodata::where('nama', $nama)->first();
            }

            $data = [
                'nama' => $nama,
                'nip' => $nip,
                'nuptk' => $this->cleanVal($r['nuptk'] ?? null),
                'nik' => $this->cleanVal($r['nik'] ?? null),
                'jenis_kelamin' => in_array(strtoupper($r['jk'] ?? ''), ['P', 'PEREMPUAN']) ? 'P' : 'L',
                'tempat_lahir' => $this->cleanVal($r['tmp'] ?? $r['tempat_lahir'] ?? null),
                'tanggal_lahir' => $this->normDate($r['tgl'] ?? $r['tanggal_lahir'] ?? null),
                'pendidikan_terakhir' => $this->cleanVal($r['pendidikan'] ?? $r['pendidikan_terakhir'] ?? null),
                'status_kepegawaian' => $this->cleanVal($r['status'] ?? $r['status_kepegawaian'] ?? 'PNS'),
                'mulai_bertugas' => is_numeric($r['mulai_bertugas'] ?? null) ? (int) $r['mulai_bertugas'] : null,
                'nomor_hp' => $this->cleanVal($r['hp'] ?? $r['nomor_hp'] ?? null),
                'email' => $this->cleanVal($r['email'] ?? null),
                'alamat' => $this->cleanVal($r['alamat'] ?? null),
            ];

            if ($guru) {
                $guru->update($data);
            } else {
                $guru = GuruBiodata::create($data);
            }

            $saved++;
        }

        return [$saved, $skipped, $errors];
    }
}
