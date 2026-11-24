<?php

namespace App\Services;

use App\Http\Controllers\Api\NotifikasiApiController;
use App\Models\Auth\AkunSiswa;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Reset kata sandi akun siswa lewat tautan email (tanpa OTP, tanpa tabel baru).
 *
 * Token terenkripsi dengan APP_KEY dan berisi id akun, masa berlaku, serta sidik
 * kata sandi saat ini, sehingga otomatis tidak berlaku lagi begitu sandi diganti
 * (sekali pakai).
 */
class ResetSandiSiswaService
{
    public const BERLAKU_MENIT = 60;

    public function buatToken(AkunSiswa $akun): string
    {
        return Crypt::encryptString(json_encode([
            'id' => $akun->id,
            'exp' => now()->addMinutes(self::BERLAKU_MENIT)->timestamp,
            'sidik' => hash('sha256', $akun->password),
        ]));
    }

    public function bacaToken(string $token): ?AkunSiswa
    {
        try {
            $isi = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($isi) || ($isi['exp'] ?? 0) < now()->timestamp) {
            return null;
        }

        $akun = AkunSiswa::where('id', $isi['id'] ?? 0)->where('aktif', true)->first();

        if (! $akun || ! hash_equals((string) ($isi['sidik'] ?? ''), hash('sha256', $akun->password))) {
            return null;
        }

        return $akun;
    }

    /**
     * Alamat dasar memakai APP_URL (bukan host request), karena email dibuka dari
     * perangkat lain dan alamat emulator seperti 10.0.2.2 tidak dapat dijangkau.
     */
    public function tautan(string $token): string
    {
        return rtrim((string) config('app.url'), '/').route('siswa.reset-sandi', ['token' => $token], false);
    }

    /**
     * Kirim email berisi tautan reset dan kembalikan tautannya.
     */
    public function kirimTautan(AkunSiswa $akun): string
    {
        $tautan = $this->tautan($this->buatToken($akun));

        Mail::raw(
            "Kami menerima permintaan mengatur ulang kata sandi akun JosBook Anda.\n\n"
            ."Buka tautan berikut untuk membuat kata sandi baru:\n{$tautan}\n\n"
            .'Tautan berlaku '.self::BERLAKU_MENIT." menit dan hanya dapat dipakai sekali.\n"
            .'Jika Anda tidak merasa memintanya, abaikan email ini; kata sandi Anda tidak berubah.',
            fn ($pesan) => $pesan->to($akun->email)->subject('Atur Ulang Kata Sandi JosBook')
        );

        return $tautan;
    }

    public function ubahPassword(AkunSiswa $akun, string $passwordBaru): void
    {
        DB::transaction(function () use ($akun, $passwordBaru): void {
            $akun->password = Hash::make($passwordBaru);
            $akun->wajib_ganti_password = false;
            $akun->save();

            // Keluarkan semua perangkat yang sedang login
            $akun->tokens()->delete();
            DB::table('akun_perangkat_login')->where('akun_siswa_id', $akun->id)->delete();
        });

        NotifikasiApiController::buat($akun->id, 'Password berhasil diganti', 'Password akun Anda telah diperbarui.');
    }
}
