<?php

namespace App\Services;

use App\Models\Admin\ImportUnggahan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportBatchHistory
{
    public function forYear(string $type, ?int $yearId): Collection
    {
        if ($yearId === null) {
            return collect();
        }

        $table = match ($type) {
            'nilai' => 'siswa_nilai',
            'kehadiran' => 'siswa_kehadiran',
            'ekskul' => 'siswa_ekskul',
            default => throw new \InvalidArgumentException('Jenis import akademik tidak valid.'),
        };

        $batches = ImportUnggahan::query()
            ->leftJoin('sekolah_kelas as kelas', 'kelas.id', '=', 'import_unggahan.kelas_id')
            ->where('import_unggahan.jenis', $type)
            ->where('import_unggahan.tahun_ajaran_id', $yearId)
            ->select('import_unggahan.*', 'kelas.nama as kelas_label')
            ->orderByDesc('import_unggahan.created_at')
            ->orderByDesc('import_unggahan.id')
            ->get();

        if ($batches->isEmpty()) {
            return $batches;
        }

        $createdCounts = DB::table($table)
            ->whereIn('import_unggahan_id', $batches->pluck('id'))
            ->select('import_unggahan_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('import_unggahan_id')
            ->pluck('total', 'import_unggahan_id');
        $recordCounts = DB::table($table)
            ->whereIn('import_terakhir_unggahan_id', $batches->pluck('id'))
            ->select('import_terakhir_unggahan_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('import_terakhir_unggahan_id')
            ->pluck('total', 'import_terakhir_unggahan_id');
        $recordDetails = DB::table($table.' as data')
            ->join('siswa_riwayat_kelas as riwayat', 'riwayat.id', '=', 'data.riwayat_kelas_id')
            ->join('sekolah_kelas as kelas', 'kelas.id', '=', 'riwayat.kelas_id')
            ->whereIn('data.import_terakhir_unggahan_id', $batches->pluck('id'))
            ->select('data.import_terakhir_unggahan_id', 'kelas.nama as kelas', 'data.semester')
            ->distinct()
            ->get()
            ->groupBy('import_terakhir_unggahan_id');

        return $batches->each(function (ImportUnggahan $batch) use ($createdCounts, $recordCounts, $recordDetails): void {
            $details = $recordDetails->get($batch->id, collect());
            $classes = $details->pluck('kelas')->unique()->values();
            $semesters = $details->pluck('semester')->unique()->values();

            if ($classes->isNotEmpty()) {
                $batch->setAttribute(
                    'kelas_label',
                    $classes->count() > 1 ? 'Beragam ('.$classes->implode(', ').')' : $classes->first()
                );
            }
            if ($semesters->isNotEmpty()) {
                $batch->setAttribute(
                    'semester_label',
                    $semesters->count() > 1 ? 'Beragam ('.$semesters->implode(', ').')' : $semesters->first()
                );
            }
            $batch->setAttribute('data_dibuat', (int) ($createdCounts[$batch->id] ?? 0));
            $batch->setAttribute('data_tercatat', (int) ($recordCounts[$batch->id] ?? 0));
        });
    }
}
