<?php

namespace App\Models\Admin;

use App\Models\Auth\Pengguna;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use Illuminate\Database\Eloquent\Model;

class CatatanKenaikanKelas extends Model
{
    protected $table = 'catatan_kenaikan_kelas';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function kelasAsal()
    {
        return $this->belongsTo(SekolahKelas::class, 'kelas_asal_id');
    }

    public function tahunAjaranAsal()
    {
        return $this->belongsTo(SekolahTahunAjaran::class, 'tahun_ajaran_asal_id');
    }

    public function tahunAjaranTujuan()
    {
        return $this->belongsTo(SekolahTahunAjaran::class, 'tahun_ajaran_tujuan_id');
    }
}
