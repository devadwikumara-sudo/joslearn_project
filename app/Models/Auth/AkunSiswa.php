<?php

namespace App\Models\Auth;

use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AkunSiswa extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'akun_siswa';

    protected $fillable = [
        'username',
        'email',
        'email_diverifikasi_at',
        'password',
        'aktif',
        'siswa_id',
        'wajib_ganti_password',
        'terakhir_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'terakhir_login_at' => 'datetime',
        'email_diverifikasi_at' => 'datetime',
        'wajib_ganti_password' => 'boolean',
        'aktif' => 'boolean',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaBiodata::class, 'siswa_id');
    }

    public function getNamaAttribute(): string
    {
        if ($this->siswa && $this->siswa->nama_lengkap) {
            return $this->siswa->nama_lengkap;
        }

        return ucfirst($this->username);
    }
}
