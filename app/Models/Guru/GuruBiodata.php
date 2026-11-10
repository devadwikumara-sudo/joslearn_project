<?php

namespace App\Models\Guru;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuruBiodata extends Model
{
    use SoftDeletes;

    protected $table = 'guru_biodata';

    protected $guarded = ['id'];

    public function akun(): HasOne
    {
        return $this->hasOne(Pengguna::class, 'guru_id');
    }

    public function waliKelas(): HasMany
    {
        return $this->hasMany(GuruWaliKelas::class, 'guru_id');
    }

    public function mengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class, 'guru_id');
    }
}
