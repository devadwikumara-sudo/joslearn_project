<?php

namespace App\Services;

use App\Models\Auth\Pengguna;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Database\Eloquent\Collection;

class AcademicRecordAccess
{
    public function __construct(private readonly AksesGuru $aksesGuru) {}

    public function authorize(Pengguna $user, string $jenis, SiswaRiwayatKelas $history, ?int $mapelId = null): void
    {
        if ($user->peran === 'admin') {
            return;
        }

        $tahunAjaranBerjalanId = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->value('id');

        abort_unless(
            $user->peran === 'guru'
                && $tahunAjaranBerjalanId !== null
                && (int) $history->tahun_ajaran_id === (int) $tahunAjaranBerjalanId,
            403,
            'Riwayat kelas ini bukan bagian dari tahun ajaran berjalan.'
        );

        $allowed = $jenis === 'nilai'
            ? $mapelId !== null && $this->aksesGuru->bolehIsiNilai($user, (int) $history->kelas_id, $mapelId)
            : $this->aksesGuru->bolehIsiKehadiranEkskul($user, (int) $history->kelas_id);

        abort_unless($allowed, 403, 'Anda tidak memiliki penugasan untuk mengubah data ini.');
    }

    /**
     * @return array{
     *     nilai_mapel_ids: array<int, int>,
     *     mapel_options: Collection<int, SekolahMapel>,
     *     kehadiran: bool,
     *     ekskul: bool
     * }
     */
    public function permissionsFor(Pengguna $user, SiswaRiwayatKelas $history): array
    {
        if ($user->peran === 'admin') {
            $mapelOptions = SekolahMapel::query()->orderBy('nama')->get(['id', 'nama', 'kode']);
            $canManageAttendanceAndActivities = true;
        } elseif ($user->peran === 'guru' && $this->isCurrentHistory($history)) {
            $mapelOptions = $this->aksesGuru->mapelDiajar($user, (int) $history->kelas_id);
            $canManageAttendanceAndActivities = $this->aksesGuru
                ->bolehIsiKehadiranEkskul($user, (int) $history->kelas_id);
        } else {
            $mapelOptions = new Collection;
            $canManageAttendanceAndActivities = false;
        }

        return [
            'nilai_mapel_ids' => $mapelOptions->modelKeys(),
            'mapel_options' => $mapelOptions,
            'kehadiran' => $canManageAttendanceAndActivities,
            'ekskul' => $canManageAttendanceAndActivities,
        ];
    }

    private function isCurrentHistory(SiswaRiwayatKelas $history): bool
    {
        $tahunAjaranBerjalanId = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->value('id');

        return $tahunAjaranBerjalanId !== null
            && (int) $history->tahun_ajaran_id === (int) $tahunAjaranBerjalanId;
    }
}
