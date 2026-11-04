<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanAktivitas extends Model
{
    protected $table = 'catatan_aktivitas';
    protected $guarded = ['id'];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
