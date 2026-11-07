<?php

namespace App\Models\Admin;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Eloquent\Model;

class CatatanCadangan extends Model
{
    protected $table = 'catatan_cadangan';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }
}
