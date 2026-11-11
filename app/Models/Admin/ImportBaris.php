<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ImportBaris extends Model
{
    protected $table = 'import_baris';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'data_asli' => 'array',
            'data_bersih' => 'array',
            'pesan' => 'array',
        ];
    }
}
