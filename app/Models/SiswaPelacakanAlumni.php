<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaPelacakanAlumni extends Model
{
    protected $table = 'siswa_pelacakan_alumni';
    protected $guarded = ['id'];

    public function siswa()
    {
        return $this->belongsTo(SiswaBiodata::class, 'siswa_id');
    }
}

