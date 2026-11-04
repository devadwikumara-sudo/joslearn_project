<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaBiodata extends Model
{
    protected $table = 'siswa_biodata';
    protected $guarded = ['id'];

    public function riwayatKelas()
    {
        return $this->hasMany(SiswaRiwayatKelas::class, 'siswa_id');
    }

    public function pelacakan()
    {
        return $this->hasMany(SiswaPelacakanAlumni::class, 'siswa_id')->orderBy('id', 'asc');
    }

    public function pengguna()
    {
        return $this->hasOne(Pengguna::class, 'siswa_id');
    }
}
