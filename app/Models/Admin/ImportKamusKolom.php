<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ImportKamusKolom extends Model
{
    protected $table = 'import_kamus_kolom';

    protected $guarded = ['id'];

    public $timestamps = false;
}
