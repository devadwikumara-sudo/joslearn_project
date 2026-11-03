<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'akun_pengguna';

    protected $fillable = [
        'peran',
        'username',
        'email',
        'password',
        'aktif',
        'guru_id',
        'siswa_id',
        'wajib_ganti_password',
        'terakhir_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'            => 'hashed',
        'terakhir_login_at'   => 'datetime',
        'wajib_ganti_password' => 'boolean',
        'aktif'               => 'boolean',
    ];

    /**
     * Tell Laravel to use 'username' as the auth identifier field.
     */
    public function getAuthIdentifierName(): string
    {
        return 'username';
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
