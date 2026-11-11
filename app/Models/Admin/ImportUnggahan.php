<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ImportUnggahan extends Model
{
    protected $table = 'import_unggahan';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pemetaan_kolom' => 'array',
            'created_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }
}
