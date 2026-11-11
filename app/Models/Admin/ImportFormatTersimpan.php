<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ImportFormatTersimpan extends Model
{
    protected $table = 'import_format_tersimpan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pemetaan_kolom' => 'array',
            'terakhir_dipakai_at' => 'datetime',
        ];
    }
}
