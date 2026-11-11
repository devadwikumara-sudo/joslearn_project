<?php

namespace App\Models\Guru;

use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruMengajar extends Model
{
    protected $table = 'guru_mengajar';

    protected $guarded = ['id'];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(SekolahKelas::class, 'kelas_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(SekolahMapel::class, 'mapel_id');
    }
}
