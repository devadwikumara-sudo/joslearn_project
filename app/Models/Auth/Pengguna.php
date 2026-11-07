<?php

namespace App\Models\Auth;

use App\Models\Guru\GuruBiodata;
use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'akun_pengguna';

    protected $fillable = [
        'peran',
        'username',
        'email',
        'email_diverifikasi_at',
        'password',
        'aktif',
        'guru_id',
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

    public function guru(): BelongsTo
    {
        return $this->belongsTo(GuruBiodata::class, 'guru_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaBiodata::class, 'siswa_id');
    }

    /**
     * Nama pengguna untuk layout / tampilan.
     */
    public function getNamaAttribute(): string
    {
        if ($this->peran === 'guru' && $this->guru && $this->guru->nama) {
            return $this->guru->nama;
        }

        if ($this->peran === 'siswa' && $this->siswa && $this->siswa->nama_lengkap) {
            return $this->siswa->nama_lengkap;
        }

        return ucfirst($this->username);
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
