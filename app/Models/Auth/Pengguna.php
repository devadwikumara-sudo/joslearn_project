<?php

namespace App\Models\Auth;

use App\Models\Guru\GuruBiodata;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'akun_pengguna';

    protected $fillable = [
        'peran',
        'username',
        'nama',
        'email',
        'email_diverifikasi_at',
        'password',
        'aktif',
        'guru_id',
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

    /**
     * Nama pengguna untuk layout / tampilan.
     */
    public function getNamaAttribute(): string
    {
        if ($this->peran === 'guru' && $this->guru && $this->guru->nama) {
            return $this->guru->nama;
        }

        $nama = trim((string) ($this->attributes['nama'] ?? ''));

        if ($nama !== '') {
            return $nama;
        }

        return $this->peran === 'admin' ? 'Administrator' : ucfirst($this->username);
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
