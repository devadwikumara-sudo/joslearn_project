<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $kelasList = SekolahKelas::query()->orderBy('nama')->pluck('nama');

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
                'riwayatKelas' => static function ($query): void {
                    $query->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
                },
            ])
            ->findOrFail($id);

        $formData = $formPendataan->formOptions($siswa);
        $formData['alumniEdit'] = true;
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
            'siswa' => $siswa->id,
        ], static fn ($value): bool => $value !== null && $value !== ''));

        return view('admin.alumni-edit', compact('formData', 'formCloseUrl'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
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
            'kegiatan_setelah_lulus' => ['nullable', 'string', 'max:255'],
        ]);

        $siswa = SiswaBiodata::findOrFail($id);

        DB::transaction(function () use ($request, $siswa, $validated): void {
            $siswa->fill($validated)->save();

            if ($siswa->isGraduated()) {
                $siswa->riwayatKelas()
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first()
                    ?->update(['status' => 'Lulus']);
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
        });

        return redirect()->route('admin.alumni', array_filter([
            'tab' => $this->selectedPanelTab($request->input('tab')),
            'aj' => $this->selectedType($request),
            'ay' => $request->input('ay'),
            'aq' => $request->input('aq'),
            'kelas' => $request->input('kelas'),
            'page' => $request->integer('page') ?: null,
            'siswa' => $id,
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
                $masukKelas = trim(($siswa->kelas_diterima ?: '').($siswa->rombel_diterima ? '-'.$siswa->rombel_diterima : ''));
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
                $query->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($kelas !== '') {
            $query->whereHas('riwayatKelas.kelas', function (Builder $classQuery) use ($kelas): void {
                $classQuery->where('nama', $kelas);
            });
        }

        return $query;
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
