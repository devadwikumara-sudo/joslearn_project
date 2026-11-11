<?php

namespace App\Models;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiswaBiodata extends Model
{
    use SoftDeletes;

    protected $table = 'siswa_biodata';

    protected $guarded = ['id'];

    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(SiswaRiwayatKelas::class, 'siswa_id');
    }

    public function riwayatKelasAkademik(): HasMany
    {
        return $this->hasMany(SiswaRiwayatKelas::class, 'siswa_id');
    }

    public function nilai(): HasManyThrough
    {
        return $this->hasManyThrough(SiswaNilai::class, SiswaRiwayatKelas::class, 'siswa_id', 'riwayat_kelas_id');
    }

    public function kehadiran(): HasManyThrough
    {
        return $this->hasManyThrough(SiswaKehadiran::class, SiswaRiwayatKelas::class, 'siswa_id', 'riwayat_kelas_id');
    }

    public function ekskul(): HasManyThrough
    {
        return $this->hasManyThrough(SiswaEkskul::class, SiswaRiwayatKelas::class, 'siswa_id', 'riwayat_kelas_id');
    }

    public function pelacakan(): HasMany
    {
        return $this->hasMany(SiswaPelacakanAlumni::class, 'siswa_id')->orderBy('id', 'asc');
    }

    public function pengguna(): HasOne
    {
        return $this->hasOne(Pengguna::class, 'siswa_id');
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(SiswaBerkas::class, 'siswa_id');
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where(function (Builder $graduatedQuery): void {
            $graduatedQuery->where(function (Builder $yearQuery): void {
                $yearQuery->whereNotNull('tahun_lulus')
                    ->where('tahun_lulus', '!=', '');
            })->orWhere(function (Builder $reasonQuery): void {
                $reasonQuery->where('alasan_keluar', 'like', '%Lulus%')
                    ->where('alasan_keluar', 'not like', '%Belum Lulus%')
                    ->where('alasan_keluar', 'not like', '%Tidak Lulus%');
            })->orWhereHas('riwayatKelas', function (Builder $historyQuery): void {
                $historyQuery->where('status', 'Lulus');
            });
        });
    }

    public function isGraduated(): bool
    {
        if (filled($this->tahun_lulus) || $this->hasGraduationReason()) {
            return true;
        }

        return $this->relationLoaded('riwayatKelas')
            ? $this->riwayatKelas->contains('status', 'Lulus')
            : $this->riwayatKelas()->where('status', 'Lulus')->exists();
    }

    private function hasGraduationReason(): bool
    {
        $reason = mb_strtolower(trim((string) $this->alasan_keluar));

        return preg_match('/\blulus\b/u', $reason) === 1
            && preg_match('/\b(?:belum|tidak)\s+lulus\b/u', $reason) !== 1;
    }
}
