<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruWaliKelas extends Model
{
    protected $table = 'guru_wali_kelas';

    protected $guarded = ['id'];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(SekolahKelas::class, 'kelas_id');
    }
}
