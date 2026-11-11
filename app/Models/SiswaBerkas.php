<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaBerkas extends Model
{
    protected $table = 'siswa_berkas';

    protected $guarded = ['id'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaBiodata::class, 'siswa_id');
    }
}
