<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiswaRiwayatKelas extends Model
{
    use SoftDeletes;

    protected $table = 'siswa_riwayat_kelas';

    protected $guarded = ['id'];

    public function siswa()
    {
        return $this->belongsTo(SiswaBiodata::class, 'siswa_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(SekolahTahunAjaran::class, 'tahun_ajaran_id');
    }

    public function kelas()
    {
        return $this->belongsTo(SekolahKelas::class, 'kelas_id');
    }

    public function kelasTujuan(): BelongsTo
    {
        return $this->belongsTo(SekolahKelas::class, 'kelas_tujuan_id');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(SiswaNilai::class, 'riwayat_kelas_id');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(SiswaKehadiran::class, 'riwayat_kelas_id');
    }

    public function ekskul(): HasMany
    {
        return $this->hasMany(SiswaEkskul::class, 'riwayat_kelas_id');
    }
}
