<?php

namespace App\Models\Guru;

use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
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

    public function guru(): BelongsTo
    {
        return $this->belongsTo(GuruBiodata::class, 'guru_id');
    }
}
