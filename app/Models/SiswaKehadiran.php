<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaKehadiran extends Model
{
    protected $table = 'siswa_kehadiran';
    protected $guarded = ['id'];

    public function riwayatKelas()
    {
        return $this->belongsTo(SiswaRiwayatKelas::class, 'riwayat_kelas_id');
    }
}

