<?php

namespace App\Models;

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
}
