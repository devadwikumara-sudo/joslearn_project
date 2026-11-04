<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanTempatSampah extends Model
{
    protected $table = 'catatan_tempat_sampah';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'dihapus_oleh');
    }
}

