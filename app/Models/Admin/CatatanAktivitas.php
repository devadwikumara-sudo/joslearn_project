<?php

namespace App\Models\Admin;

use App\Models\Auth\Pengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatatanAktivitas extends Model
{
    protected $table = 'catatan_aktivitas';

    protected $guarded = ['id'];

    const UPDATED_AT = null;

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function scopeForAdminLog(Builder $query, string $lf, string $lq): Builder
    {
        if ($lf !== '') {
            $query->where(static function (Builder $query) use ($lf): void {
                $query->where('peran', $lf)
                    ->orWhereHas('pengguna', static function (Builder $query) use ($lf): void {
                        $query->where('username', $lf);
                    });
            });
        }

        if ($lq !== '') {
            $query->where(static function (Builder $query) use ($lq): void {
                $query->where('aksi', 'like', "%{$lq}%")
                    ->orWhere('keterangan', 'like', "%{$lq}%");
            });
        }

        return $query;
    }
}
