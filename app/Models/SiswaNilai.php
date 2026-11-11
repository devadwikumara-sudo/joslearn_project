<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaNilai extends Model
{
    protected $table = 'siswa_nilai';
    protected $guarded = ['id'];

    public function riwayatKelas()
    {
        return $this->belongsTo(SiswaRiwayatKelas::class, 'riwayat_kelas_id');
    }

    public function mapel()
    {
        return $this->belongsTo(SekolahMapel::class, 'mapel_id');
    }
}

