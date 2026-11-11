<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaEkskul extends Model
{
    protected $table = 'siswa_ekskul';
    protected $guarded = ['id'];

    public function riwayatKelas()
    {
        return $this->belongsTo(SiswaRiwayatKelas::class, 'riwayat_kelas_id');
    }
}

