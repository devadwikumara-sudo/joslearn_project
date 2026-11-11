<?php

namespace App\Services;

use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\Guru\GuruMengajar;
use App\Models\Guru\GuruWaliKelas;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use Illuminate\Database\Eloquent\Collection;

class AksesGuru
{
    public function guruDariPengguna(?Pengguna $pengguna): ?GuruBiodata
    {
        if (! $pengguna || $pengguna->peran !== 'guru') {
            return null;
        }

        return $pengguna->guru;
    }

    /**
     * @return Collection<int, SekolahKelas>
     */
    public function kelasDiajar(GuruBiodata|Pengguna|null $subjek): Collection
    {
        if ($this->adalahAdmin($subjek)) {
            return SekolahKelas::query()->get();
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return new Collection;
        }

        $kelas = GuruMengajar::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('kelas')
            ->get()
            ->pluck('kelas')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($kelas->all());
    }

    /**
     * @return Collection<int, SekolahMapel>
     */
    public function mapelDiajar(GuruBiodata|Pengguna|null $subjek, int|SekolahKelas|null $kelas = null): Collection
    {
        if ($this->adalahAdmin($subjek)) {
            return SekolahMapel::query()->get();
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return new Collection;
        }

        $kelasId = $kelas instanceof SekolahKelas ? $kelas->id : $kelas;

        $mapel = GuruMengajar::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->when($kelasId !== null, fn ($query) => $query->where('kelas_id', $kelasId))
            ->with('mapel')
            ->get()
            ->pluck('mapel')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($mapel->all());
    }

    /**
     * @return Collection<int, SekolahKelas>
     */
    public function kelasWali(GuruBiodata|Pengguna|null $subjek): Collection
    {
        if ($this->adalahAdmin($subjek)) {
            return SekolahKelas::query()->get();
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return new Collection;
        }

        $kelas = GuruWaliKelas::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('kelas')
            ->get()
            ->pluck('kelas')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($kelas->all());
    }

    public function bolehIsiNilai(GuruBiodata|Pengguna|null $subjek, int $kelasId, int $mapelId): bool
    {
        if ($this->adalahAdmin($subjek)) {
            return true;
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return false;
        }

        return GuruMengajar::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('kelas_id', $kelasId)
            ->where('mapel_id', $mapelId)
            ->exists();
    }

    public function bolehIsiKehadiranEkskul(GuruBiodata|Pengguna|null $subjek, int $kelasId): bool
    {
        return $this->bolehUntukKelasWali($subjek, $kelasId);
    }

    public function bolehKenaikanKelas(GuruBiodata|Pengguna|null $subjek, int $kelasId): bool
    {
        return $this->bolehUntukKelasWali($subjek, $kelasId);
    }

    public function adalahGuruMapel(GuruBiodata|Pengguna|null $subjek): bool
    {
        if ($this->adalahAdmin($subjek)) {
            return true;
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return false;
        }

        return GuruMengajar::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->exists();
    }

    public function adalahWali(GuruBiodata|Pengguna|null $subjek): bool
    {
        if ($this->adalahAdmin($subjek)) {
            return true;
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return false;
        }

        return GuruWaliKelas::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->exists();
    }

    private function bolehUntukKelasWali(GuruBiodata|Pengguna|null $subjek, int $kelasId): bool
    {
        if ($this->adalahAdmin($subjek)) {
            return true;
        }

        $guru = $this->biodataGuru($subjek);
        $tahunAjaranId = $this->tahunAjaranBerjalanId();
        if (! $guru || ! $tahunAjaranId) {
            return false;
        }

        return GuruWaliKelas::query()
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('kelas_id', $kelasId)
            ->exists();
    }

    private function adalahAdmin(GuruBiodata|Pengguna|null $subjek): bool
    {
        return $subjek instanceof Pengguna && $subjek->peran === 'admin';
    }

    private function biodataGuru(GuruBiodata|Pengguna|null $subjek): ?GuruBiodata
    {
        if ($subjek instanceof GuruBiodata) {
            return $subjek;
        }

        return $subjek instanceof Pengguna && $subjek->peran === 'guru'
            ? $subjek->guru
            : null;
    }

    private function tahunAjaranBerjalanId(): ?int
    {
        $id = SekolahTahunAjaran::query()->where('sedang_berjalan', true)->value('id');

        return $id === null ? null : (int) $id;
    }
}
