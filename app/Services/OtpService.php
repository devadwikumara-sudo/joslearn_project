<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const BERHASIL = 'berhasil';

    public const SALAH = 'salah';

    public const TIDAK_VALID = 'tidak_valid';

    public const TERLALU_BANYAK = 'terlalu_banyak';

    public const BERLAKU_MENIT = 10;

    public const MAKS_PERCOBAAN = 5;

    public function buatDanKirim(string $email, string $tujuan): string
    {
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($email, $tujuan, $kode): void {
            DB::table('akun_kode_otp')
                ->where('email', $email)
                ->where('tujuan', $tujuan)
                ->whereNull('dipakai_at')
                ->update(['dipakai_at' => now()]);

            DB::table('akun_kode_otp')->insert([
                'email' => $email,
                'tujuan' => $tujuan,
                'kode_hash' => Hash::make($kode),
                'percobaan' => 0,
                'berlaku_sampai' => now()->addMinutes(self::BERLAKU_MENIT),
                'created_at' => now(),
            ]);
        });

        $keperluan = $tujuan === 'daftar' ? 'verifikasi pendaftaran akun' : 'reset kata sandi';

        Mail::raw(
            "Kode {$keperluan} JosBook Anda: {$kode}\n\n"
            .'Kode berlaku '.self::BERLAKU_MENIT." menit. Jangan bagikan kode ini kepada siapa pun.\n"
            .'Jika Anda tidak merasa memintanya, abaikan email ini.',
            fn ($pesan) => $pesan->to($email)->subject('Kode Verifikasi JosBook')
        );

        return $kode;
    }

    public function verifikasi(string $email, string $tujuan, string $kode): string
    {
        return DB::transaction(function () use ($email, $tujuan, $kode): string {
            $otp = DB::table('akun_kode_otp')
                ->where('email', $email)
                ->where('tujuan', $tujuan)
                ->whereNull('dipakai_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $otp || Carbon::parse($otp->berlaku_sampai)->isPast()) {
                return self::TIDAK_VALID;
            }

            if ($otp->percobaan >= self::MAKS_PERCOBAAN) {
                return self::TERLALU_BANYAK;
            }

            if (! Hash::check($kode, $otp->kode_hash)) {
                DB::table('akun_kode_otp')->where('id', $otp->id)->increment('percobaan');

                return self::SALAH;
            }

            DB::table('akun_kode_otp')->where('id', $otp->id)->update(['dipakai_at' => now()]);

            return self::BERHASIL;
        });
    }

    public function detikSampaiKirimUlang(string $email, string $tujuan, int $jedaDetik): int
    {
        $terakhir = DB::table('akun_kode_otp')
            ->where('email', $email)
            ->where('tujuan', $tujuan)
            ->orderByDesc('id')
            ->value('created_at');

        if (! $terakhir) {
            return 0;
        }

        $berlalu = Carbon::parse($terakhir)->diffInSeconds(now(), true);

        return max(0, (int) ceil($jedaDetik - $berlalu));
    }
}
