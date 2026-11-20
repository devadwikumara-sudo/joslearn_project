<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlumniController extends Controller
{
    private const TYPE_GRADUATED = 'Alumni (Lulus)';

    private const TYPE_EXIT = 'Pindah / Keluar';

    private const TYPES = [
        self::TYPE_GRADUATED => 'Lulus',
        self::TYPE_EXIT => 'Pindah/Keluar',
    ];

    public function index(Request $request): View
    {
        $aj = $this->selectedType($request);
        $ay = $this->queryString($request, 'ay');
        $aq = trim($this->queryString($request, 'aq'));
        $kelas = trim($this->queryString($request, 'kelas'));
        $panelTab = $request->query('tab') === 'pelacakan' ? 'pelacakan' : 'kontrol-induk';
        $selectedStudentId = $request->integer('siswa') ?: null;
        $isGraduatedList = $aj === self::TYPE_GRADUATED;
        $kelasListQuery = SekolahKelas::query();
        if ($isGraduatedList) {
            $kelasListQuery->where('tingkat', 'XII');
        }
        $kelasList = $kelasListQuery->orderBy('nama')->pluck('nama');
        $kelasKeluarListQuery = $this->filteredAlumniQuery($aj, '', '', '')
            ->whereNotNull('kelas_keluar')
            ->where('kelas_keluar', '!=', '');
        if ($isGraduatedList) {
            $kelasKeluarListQuery->where('kelas_keluar', 'like', 'XII-%');
        }
        $kelasKeluarList = $kelasKeluarListQuery->distinct()->orderBy('kelas_keluar')->pluck('kelas_keluar');
        $kelasList = $kelasList->merge($kelasKeluarList)->unique()->values();

        $tahunList = SiswaBiodata::query()
            ->graduated()
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '!=', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->toArray();
        $tahunKeluarList = SiswaBiodata::query()
            ->alumni()
            ->whereNotIn('siswa_biodata.id', SiswaBiodata::graduated()->select('id'))
            ->whereNotNull('tanggal_keluar')
            ->selectRaw('SUBSTR(tanggal_keluar, 1, 4) as thn')
            ->distinct()
            ->pluck('thn')
            ->filter()
            ->map(static fn ($year): string => (string) $year)
            ->toArray();
        $ys = array_values(array_unique(array_filter(array_merge($tahunList, $tahunKeluarList))));
        rsort($ys);
        if ($ys === []) {
            $ys = [(string) date('Y'), (string) (date('Y') - 1), (string) (date('Y') - 2)];
        }

        $query = $this->filteredAlumniQuery($aj, $ay, $aq, $kelas);
        $totalFiltered = (clone $query)->count();
        $alumni = $query->with([
            'riwayatKelas' => static function ($query): void {
                $query->with(['kelas', 'tahunAjaran', 'nilai.mapel', 'kehadiran', 'ekskul'])->latest('id');
            },
            'berkas',
            'pelacakan',
        ])
            ->withCount('pelacakan')
            ->orderBy('nomor_induk')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        if ($selectedStudentId === null && $panelTab === 'pelacakan') {
            $selectedStudentId = $alumni->first()?->id;
        }

        foreach ($alumni as $siswa) {
            $isGraduated = $siswa->isGraduated();
            $latestHistory = $siswa->riwayatKelas->first();
            $latestTracking = $siswa->pelacakan->last();
            $siswa->setAttribute('kelas_masuk_display', $this->acceptedClassDisplay($siswa));
            $studentStatus = $isGraduated
                ? 'Lulus'
                : ($siswa->alasan_keluar ?: (in_array($latestHistory?->status, ['Pindah keluar', 'Keluar'], true) ? $latestHistory->status : 'Keluar'));

            $siswa->setAttribute('status_alumni', $studentStatus);
            $siswa->setAttribute('status_kontrol_induk', $this->controlIndukCompleteness($siswa, $isGraduated));
            $siswa->setAttribute(
                'status_pelacakan',
                $latestTracking
                    ? trim($latestTracking->jenis.' · '.$latestTracking->nama_tempat.' ('.$latestTracking->tahun.')')
                    : 'Belum dilacak'
            );
        }

        $allGraduates = SiswaBiodata::graduated()
            ->with('pelacakan')
            ->get(['id', 'tahun_lulus', 'tanggal_keluar']);
        $trackingStats = [
            'total' => $allGraduates->count(),
            'dilacak' => 0,
            'kuliah' => 0,
            'bekerja' => 0,
        ];
        foreach ($allGraduates as $graduate) {
            $latestTracking = $graduate->pelacakan->last();
            if ($latestTracking) {
                $trackingStats['dilacak']++;
                if ($latestTracking->jenis === 'Kuliah') {
                    $trackingStats['kuliah']++;
                }
                if ($latestTracking->jenis === 'Bekerja') {
                    $trackingStats['bekerja']++;
                }
            }
        }

        $panelQuery = array_filter([
            'tab' => $panelTab,
            'aj' => $aj,
            'ay' => $ay,
            'aq' => $aq,
            'kelas' => $kelas,
            'page' => $request->query('page'),
            'siswa' => $selectedStudentId,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $selectedAlumni = $alumni->firstWhere('id', $selectedStudentId);
        $activeAcademicYearId = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->value('id');
        $canCancelGraduation = $activeAcademicYearId !== null
            && ($selectedAlumni?->isGraduated() ?? false)
            && $selectedAlumni->riwayatKelas->contains(
                static fn ($history): bool => (int) $history->tahun_ajaran_id === (int) $activeAcademicYearId
            );

        return view('admin.alumni', [
            'aj' => $aj,
            'ay' => $ay,
            'aq' => $aq,
            'kelas' => $kelas,
            'kelasList' => $kelasList,
            'ys' => $ys,
            'types' => self::TYPES,
            'totalFiltered' => $totalFiltered,
            'alumni' => $alumni,
            'trackingStats' => $trackingStats,
            'panelTab' => $panelTab,
            'selectedStudentId' => $selectedStudentId,
            'selectedAlumni' => $selectedAlumni,
            'canCancelGraduation' => $canCancelGraduation,
            'panelQuery' => $panelQuery,
        ]);
    }

    public function show(int $id): RedirectResponse
    {
        return redirect()->route('admin.data-siswa.show', $id);
    }

    public function editBiodata(Request $request, int $id, FormPendataanController $formPendataan): View
    {
        $siswa = SiswaBiodata::alumni()
            ->with([
                'berkas',
                'pelacakan',
                'riwayatKelas' => static function ($query): void {
                    $query->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
                },
            ])
            ->findOrFail($id);

        $formData = $formPendataan->formOptions($siswa);
        $formData['alumniEdit'] = true;
        $formData['alumniTracking'] = $siswa->pelacakan->last();
        $formData['alumniReturn'] = [
            'tab' => $this->selectedPanelTab($request->query('tab')),
            'aj' => $this->selectedType($request),
            'ay' => $this->queryString($request, 'ay'),
            'aq' => trim($this->queryString($request, 'aq')),
            'kelas' => trim($this->queryString($request, 'kelas')),
            'page' => $request->integer('page') ?: null,
        ];
        $formCloseUrl = route('admin.alumni', array_filter([
            'tab' => $formData['alumniReturn']['tab'],
            'aj' => $formData['alumniReturn']['aj'],
            'ay' => $formData['alumniReturn']['ay'],
            'aq' => $formData['alumniReturn']['aq'],
            'kelas' => $formData['alumniReturn']['kelas'],
            'page' => $formData['alumniReturn']['page'],
        ], static fn ($value): bool => $value !== null && $value !== ''));

        return view('admin.alumni-edit', compact('formData', 'formCloseUrl'));
    }

    public function editData(int $id): JsonResponse
    {
        $siswa = SiswaBiodata::alumni()
            ->with(['berkas', 'pelacakan', 'riwayatKelas'])
            ->findOrFail($id);
        $latestHistory = $siswa->riwayatKelas->sortByDesc('id')->first();
        $latestTracking = $siswa->pelacakan->last();
        $isGraduated = $siswa->isGraduated();
        $payload = $siswa->only([
            'nama_lengkap', 'nama_panggilan', 'nisn', 'nomor_induk', 'jenis_kelamin', 'nik',
            'tempat_lahir', 'tanggal_lahir', 'agama', 'kewarganegaraan', 'anak_ke', 'jumlah_saudara',
            'status_yatim', 'bahasa_sehari_hari', 'alamat_jalan', 'alamat_rt_rw', 'alamat_desa',
            'alamat_kecamatan', 'alamat_kabupaten', 'alamat_provinsi', 'alamat_kode_pos', 'nomor_hp',
            'tempat_tinggal', 'jarak_ke_sekolah_km', 'golongan_darah', 'tinggi_badan_cm',
            'berat_badan_kg', 'penyakit', 'kelainan_fisik', 'sekolah_asal', 'tanggal_ijazah_smp',
            'nomor_ijazah_smp', 'lama_belajar_tahun', 'jenis_registrasi', 'tanggal_diterima',
            'pindahan_dari_sekolah', 'alasan_pindah', 'kelas_diterima', 'rombel_diterima',
            'nama_ayah', 'agama_ayah', 'kewarganegaraan_ayah', 'pendidikan_ayah', 'pekerjaan_ayah',
            'penghasilan_ayah', 'nomor_hp_ayah', 'keadaan_ayah', 'nama_ibu', 'agama_ibu',
            'kewarganegaraan_ibu', 'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu',
            'nomor_hp_ibu', 'keadaan_ibu', 'ortu_alamat_jalan', 'ortu_alamat_rt_rw',
            'ortu_alamat_desa', 'ortu_alamat_kecamatan', 'ortu_alamat_kabupaten',
            'ortu_alamat_provinsi', 'ortu_alamat_kode_pos', 'nama_wali', 'agama_wali',
            'kewarganegaraan_wali', 'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali',
            'nomor_hp_wali', 'wali_alamat_jalan', 'wali_alamat_rt_rw', 'wali_alamat_desa',
            'wali_alamat_kecamatan', 'wali_alamat_kabupaten', 'wali_alamat_provinsi',
            'wali_alamat_kode_pos', 'kelas_keluar', 'alasan_keluar', 'tanggal_keluar',
            'sekolah_tujuan', 'nomor_peserta_psaj', 'nomor_skl', 'nomor_ijazah_sma',
            'tanggal_ijazah_sma', 'nomor_transkrip', 'tahun_lulus', 'kegiatan_setelah_lulus',
            'catatan', 'kk_ada', 'akta_ada', 'ijazah_smp_ada', 'surat_pindah_ada',
        ]);
        $payload['id'] = $siswa->id;
        $payload['status_alumni'] = $isGraduated
            ? 'Lulus'
            : ($siswa->alasan_keluar ?: (in_array($latestHistory?->status, ['Pindah keluar', 'Keluar'], true) ? $latestHistory->status : 'Keluar'));
        $payload['status_kontrol_induk'] = $this->controlIndukCompleteness($siswa, $isGraduated).'%';
        $payload['status_pelacakan'] = $latestTracking
            ? trim($latestTracking->jenis.' · '.$latestTracking->nama_tempat.' ('.$latestTracking->tahun.')')
            : 'Belum dilacak';
        $payload['edit_url'] = route('admin.data-siswa.update', $siswa->id);
        $payload['foto_url'] = $siswa->foto ? route('admin.data-siswa.foto', $siswa->id) : null;
        $payload['attachments'] = $siswa->berkas->map(static fn ($attachment): array => [
            'jenis' => $attachment->jenis,
            'nama_file' => $attachment->nama_file,
            'url' => route('admin.data-siswa.berkas', [$siswa->id, $attachment->jenis]),
        ])->values();
        $payload['tracking_jenis'] = $latestTracking?->jenis;
        $payload['tracking_nama_tempat'] = $latestTracking?->nama_tempat;
        $payload['tracking_tahun'] = $latestTracking?->tahun;
        $payload['tracking_keterangan'] = $latestTracking?->keterangan;
        $payload['tracking_history'] = $siswa->pelacakan->map(static fn ($tracking): array => [
            'jenis' => $tracking->jenis,
            'nama_tempat' => $tracking->nama_tempat,
            'tahun' => $tracking->tahun,
            'keterangan' => $tracking->keterangan,
            'delete_url' => route('admin.pelacakan-alumni.destroy', $tracking->id),
        ])->values();

        return response()->json($payload);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $siswa = SiswaBiodata::alumni()->with('pelacakan')->findOrFail($id);
        $validated = $request->validate([
            'nomor_induk' => ['nullable', 'string', 'max:64', Rule::unique('siswa_biodata', 'nomor_induk')->whereNull('deleted_at')->ignore($siswa->id)],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nisn' => ['required', 'digits:10', Rule::unique('siswa_biodata', 'nisn')->whereNull('deleted_at')->ignore($siswa->id)],
            'jenis_registrasi' => ['nullable', 'string', 'max:64'],
            'tanggal_diterima' => ['nullable', 'date_format:Y-m-d'],
            'kelas_diterima' => ['nullable', 'string', 'max:10'],
            'rombel_diterima' => ['nullable', 'string', 'max:10'],
            'sekolah_asal' => ['nullable', 'string', 'max:150'],
            'alasan_keluar' => ['nullable', 'string', 'max:255'],
            'tanggal_keluar' => ['nullable', 'date_format:Y-m-d'],
            'kelas_keluar' => ['nullable', 'string', 'max:64'],
            'sekolah_tujuan' => ['nullable', 'string', 'max:150'],
            'nomor_peserta_psaj' => ['nullable', 'string', 'max:64'],
            'nomor_skl' => ['nullable', 'string', 'max:64'],
            'nomor_ijazah_sma' => ['nullable', 'string', 'max:64'],
            'tanggal_ijazah_sma' => ['nullable', 'date_format:Y-m-d'],
            'nomor_transkrip' => ['nullable', 'string', 'max:64'],
            'tahun_lulus' => ['nullable', 'digits:4'],
        ]);
        $latestTracking = $siswa->pelacakan->last();
        $trackingFields = [
            'tracking_jenis',
            'tracking_nama_tempat',
            'tracking_tahun',
            'tracking_keterangan',
        ];
        $trackingIsRequired = $latestTracking !== null
            || collect($request->only($trackingFields))->contains(static fn (mixed $value): bool => filled($value));
        $trackingRequired = $trackingIsRequired ? 'required' : 'nullable';
        $tracking = $request->validate([
            'tracking_jenis' => [$trackingRequired, 'string', Rule::in(['Kuliah', 'Bekerja', 'Wirausaha', 'Mencari kerja', 'Lainnya'])],
            'tracking_nama_tempat' => [$trackingRequired, 'string', 'max:150'],
            'tracking_tahun' => [$trackingRequired, 'digits:4'],
            'tracking_keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $siswa, $validated, $tracking, $trackingIsRequired, $latestTracking): void {
            $siswa->fill($validated)->save();

            if ($siswa->isGraduated()) {
                $siswa->riwayatKelas()
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first()
                    ?->update(['status' => 'Lulus']);
            }

            $trackingChanged = false;
            if ($trackingIsRequired) {
                $trackingAttributes = [
                    'jenis' => $tracking['tracking_jenis'],
                    'nama_tempat' => $tracking['tracking_nama_tempat'],
                    'tahun' => $tracking['tracking_tahun'],
                    'keterangan' => $tracking['tracking_keterangan'] ?? null,
                ];
                if ($latestTracking) {
                    $latestTracking->fill($trackingAttributes);
                    if ($latestTracking->isDirty()) {
                        $latestTracking->save();
                        $trackingChanged = true;
                    }
                } else {
                    $siswa->pelacakan()->create($trackingAttributes);
                    $trackingChanged = true;
                }

                $siswa->update([
                    'kegiatan_setelah_lulus' => "{$trackingAttributes['jenis']} · {$trackingAttributes['nama_tempat']}",
                ]);
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'edit_kontrol_induk',
                'keterangan' => "Memperbarui Kontrol Induk alumni: {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);

            if ($trackingChanged) {
                CatatanAktivitas::create([
                    'pengguna_id' => auth()->id(),
                    'peran' => auth()->user()->peran ?? 'admin',
                    'aksi' => 'lacak_alumni',
                    'keterangan' => "Memperbarui pelacakan alumni: {$siswa->nama_lengkap} ({$trackingAttributes['jenis']} di {$trackingAttributes['nama_tempat']})",
                    'objek_tipe' => 'siswa',
                    'objek_id' => $siswa->id,
                    'alamat_ip' => $request->ip(),
                ]);
            }
        });

        return redirect()->route('admin.alumni', array_filter([
            'tab' => $this->selectedPanelTab($request->input('tab')),
            'aj' => $this->selectedType($request),
            'ay' => $request->input('ay'),
            'aq' => $request->input('aq'),
            'kelas' => $request->input('kelas'),
            'page' => $request->integer('page') ?: null,
        ], static fn ($value): bool => $value !== null && $value !== ''))
            ->with('success', "Kontrol Induk {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    public function cancelGraduation(Request $request, int $id): RedirectResponse
    {
        $siswa = SiswaBiodata::with('riwayatKelas')->findOrFail($id);
        abort_unless($siswa->isGraduated(), 404);

        $tahunAjaranAktif = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->first();
        $riwayatAktif = $tahunAjaranAktif
            ? $siswa->riwayatKelas()
                ->where('tahun_ajaran_id', $tahunAjaranAktif->id)
                ->whereNull('deleted_at')
                ->latest('id')
                ->first()
            : null;

        if (! $riwayatAktif) {
            return back()->withErrors([
                'kelulusan' => 'Kelulusan tidak dapat dibatalkan karena siswa belum memiliki riwayat pada tahun ajaran aktif.',
            ]);
        }

        DB::transaction(function () use ($request, $siswa, $riwayatAktif): void {
            $siswa->riwayatKelas()
                ->where('status', 'Lulus')
                ->update(['status' => 'Aktif']);
            $riwayatAktif->update(['status' => 'Aktif']);
            $siswa->update([
                'tahun_lulus' => null,
                'alasan_keluar' => null,
                'tanggal_keluar' => null,
                'kelas_keluar' => null,
                'sekolah_tujuan' => null,
                'kegiatan_setelah_lulus' => null,
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'batalkan_kelulusan',
                'keterangan' => "Membatalkan kelulusan siswa: {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.alumni', array_filter([
            'tab' => 'kontrol-induk',
            'aj' => $this->selectedType($request),
            'ay' => $request->input('ay'),
            'aq' => $request->input('aq'),
            'kelas' => $request->input('kelas'),
            'page' => $request->integer('page') ?: null,
        ], static fn ($value): bool => $value !== null && $value !== ''))
            ->with('success', "Kelulusan {$siswa->nama_lengkap} berhasil dibatalkan.");
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filteredAlumniQuery(
            $this->selectedType($request),
            $this->queryString($request, 'ay'),
            trim($this->queryString($request, 'aq')),
            trim($this->queryString($request, 'kelas'))
        )
            ->with(['riwayatKelas', 'pelacakan'])
            ->orderBy('nomor_induk')
            ->orderBy('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="alumni-kontrol-induk-'.date('Ymd-His').'.csv"',
        ];

        $callback = function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, [
                'No', 'Induk', 'Nama Peserta Didik', 'NISN', 'Jenis Registrasi',
                'Tanggal Masuk', 'Masuk Kelas', 'Sekolah Asal SMP', 'Keluar Karena',
                'Tanggal Keluar', 'Keluar Kelas', 'Sekolah Tujuan', 'Nomor Peserta PSAJ',
                'Nomor SKL', 'Nomor Ijazah Nasional', 'Tgl Pengesahan Ijazah',
                'Nomor Transkrip Nilai', 'Melanjutkan Studi / Bekerja', 'Tahun Lulus', 'Status',
                'Kelengkapan Kontrol Induk', 'Status Pelacakan',
            ]);

            foreach ($rows as $index => $siswa) {
                $latestTracking = $siswa->pelacakan->last();
                $isGraduated = $siswa->isGraduated();
                $masukKelas = $this->acceptedClassDisplay($siswa);
                $trackingStatus = $latestTracking
                    ? trim($latestTracking->jenis.' · '.$latestTracking->nama_tempat.' ('.$latestTracking->tahun.')')
                    : 'Belum dilacak';

                fputcsv($output, [
                    $index + 1,
                    $siswa->nomor_induk ?: '-',
                    $siswa->nama_lengkap ?: '-',
                    $siswa->nisn ?: '-',
                    $siswa->jenis_registrasi ?: '-',
                    $siswa->tanggal_diterima ?: '-',
                    $masukKelas ?: '-',
                    $siswa->sekolah_asal ?: '-',
                    $siswa->alasan_keluar ?: '-',
                    $siswa->tanggal_keluar ?: '-',
                    $siswa->kelas_keluar ?: '-',
                    $siswa->sekolah_tujuan ?: '-',
                    $siswa->nomor_peserta_psaj ?: '-',
                    $siswa->nomor_skl ?: '-',
                    $siswa->nomor_ijazah_sma ?: '-',
                    $siswa->tanggal_ijazah_sma ?: '-',
                    $siswa->nomor_transkrip ?: '-',
                    $siswa->kegiatan_setelah_lulus ?: '-',
                    $siswa->tahun_lulus ?: ($siswa->tanggal_keluar ? substr($siswa->tanggal_keluar, 0, 4) : '-'),
                    $isGraduated ? 'Lulus' : ($siswa->alasan_keluar ?: 'Pindah/Keluar'),
                    $this->controlIndukCompleteness($siswa, $isGraduated).'%',
                    $trackingStatus,
                ]);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['selected', 'all', 'class'])],
            'aj' => ['required', Rule::in(array_keys(self::TYPES))],
            'ay' => ['nullable', 'digits:4'],
            'kelas' => ['nullable', 'required_if:mode,class', 'string', 'max:64', Rule::exists('sekolah_kelas', 'nama')],
            'aq' => ['nullable', 'string', 'max:150'],
            'konfirmasi' => ['required', 'in:hapus'],
            'siswa_ids' => ['required_if:mode,selected', 'array', 'min:1', 'max:250'],
            'siswa_ids.*' => ['required', 'integer', 'distinct', 'exists:siswa_biodata,id'],
        ]);

        $type = $validated['aj'];
        $year = $validated['ay'] ?? '';
        $class = $validated['kelas'] ?? '';
        $search = trim($validated['aq'] ?? '');
        $query = $this->filteredAlumniQuery($type, $year, $search, $class);

        if ($validated['mode'] === 'selected') {
            $query->whereIn('siswa_biodata.id', $validated['siswa_ids']);
        }

        $deletedCount = 0;
        DB::transaction(function () use ($query, &$deletedCount, $request, $validated): void {
            $query->orderBy('id')->chunkById(200, function ($alumni) use (&$deletedCount): void {
                foreach ($alumni as $siswa) {
                    $siswa->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount > 0) {
                CatatanAktivitas::create([
                    'pengguna_id' => auth()->id(),
                    'peran' => auth()->user()->peran,
                    'aksi' => 'hapus_alumni_massal',
                    'keterangan' => "Memindahkan {$deletedCount} data alumni ke Tempat Sampah melalui aksi {$validated['mode']}.",
                    'objek_tipe' => 'alumni',
                    'alamat_ip' => $request->ip(),
                ]);
            }
        });

        $redirectFilters = array_filter([
            'aj' => $type,
            'ay' => $year,
            'kelas' => $class,
            'aq' => $search,
        ], static fn ($value): bool => $value !== null && $value !== '');

        if ($deletedCount === 0) {
            return redirect()->route('admin.alumni', $redirectFilters)
                ->with('error', 'Tidak ada alumni yang cocok dengan pilihan dan filter untuk dihapus.');
        }

        return redirect()->route('admin.alumni', $redirectFilters)
            ->with('success', "{$deletedCount} data alumni berhasil dipindahkan ke Tempat Sampah.");
    }

    private function filteredAlumniQuery(string $type, string $year, string $search, string $kelas): Builder
    {
        $query = $type === self::TYPE_EXIT
            ? SiswaBiodata::query()
                ->alumni()
                ->whereNotIn('siswa_biodata.id', SiswaBiodata::graduated()->select('id'))
            : SiswaBiodata::query()->graduated();

        if ($year !== '') {
            $query->where(function (Builder $query) use ($year): void {
                $query->where('tahun_lulus', $year)
                    ->orWhereRaw('SUBSTR(tanggal_keluar, 1, 4) = ?', [$year]);
            });
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('nama_lengkap', 'like', "{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($kelas !== '') {
            $query->where('kelas_keluar', $kelas);
            if ($type === self::TYPE_GRADUATED) {
                $query->where('kelas_keluar', 'like', 'XII-%');
            }
        }

        return $query;
    }

    private function acceptedClassDisplay(SiswaBiodata $siswa): string
    {
        $grade = mb_strtoupper(trim((string) $siswa->kelas_diterima));
        $group = trim((string) $siswa->rombel_diterima);

        if ($grade === '-') {
            $grade = '';
        }

        if ($grade === '' && $group !== '') {
            $grade = 'X';
        }

        return trim($grade.($group !== '' ? '-'.$group : ''));
    }

    private function selectedType(Request $request): string
    {
        $type = $request->input('aj', self::TYPE_GRADUATED);

        return is_string($type) && array_key_exists($type, self::TYPES)
            ? $type
            : self::TYPE_GRADUATED;
    }

    private function selectedPanelTab(mixed $tab): string
    {
        return $tab === 'pelacakan' ? 'pelacakan' : 'kontrol-induk';
    }

    private function queryString(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }

    private function controlIndukCompleteness(SiswaBiodata $siswa, bool $isGraduated): int
    {
        $fields = ['alasan_keluar', 'tanggal_keluar', 'kelas_keluar'];
        if ($isGraduated) {
            $fields = array_merge($fields, [
                'nomor_peserta_psaj',
                'nomor_skl',
                'nomor_ijazah_sma',
                'tanggal_ijazah_sma',
                'nomor_transkrip',
            ]);
        } else {
            $fields[] = 'sekolah_tujuan';
        }

        $filled = count(array_filter(
            $fields,
            static fn (string $field): bool => filled($siswa->{$field})
        ));

        return (int) round(($filled / count($fields)) * 100);
    }
}
