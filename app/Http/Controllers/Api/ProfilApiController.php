<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Layar Profil: Data Diri, Edit Profil, Ganti Password, foto.
 */
class ProfilApiController extends Controller
{
    /**
     * GET /api/v1/profil  (Profil + Data Diri)
     */
    public function show(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $siswa    = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->dataProfil($pengguna)]);
    }

    /**
     * PUT /api/v1/profil  (Edit Profil)
     * Yang boleh diubah siswa: email, nomor HP, alamat. Nama dan kelas hanya admin.
     */
    public function update(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $siswa    = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'email'    => ['required', 'email', 'max:120', Rule::unique('akun_pengguna', 'email')->ignore($pengguna->id)],
            'nomor_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]*$/'],
            'alamat'   => ['nullable', 'string', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email ini sudah dipakai akun lain.',
            'nomor_hp.regex' => 'Nomor HP hanya boleh berisi angka.',
        ]);

        DB::transaction(function () use ($pengguna, $siswa, $data) {
            $pengguna->email = strtolower(trim($data['email']));
            $pengguna->save();

            $siswa->nomor_hp     = $data['nomor_hp'] ?? null;
            $siswa->alamat_jalan = $data['alamat'] ?? null;
            $siswa->save();
        });

        NotifikasiApiController::buat($pengguna->id, 'Profil berhasil diperbarui', 'Perubahan profil telah tersimpan di profil anda');

        return response()->json([
            'pesan' => 'Profil berhasil diperbarui.',
            'data'  => $this->dataProfil($pengguna->fresh('siswa')),
        ]);
    }

    /**
     * PUT /api/v1/ganti-password  (Ganti Password)
     * Field: password_lama, password_baru, password_baru_confirmation
     */
    public function gantiPassword(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.required' => 'Isi password lama.',
            'password_baru.required' => 'Isi password baru.',
            'password_baru.min'      => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password tidak sama.',
            'password_baru.different' => 'Password baru harus berbeda dari password lama.',
        ]);

        if (! Hash::check($data['password_lama'], $pengguna->password)) {
            return response()->json(['pesan' => 'Password lama salah.', 'kode' => 'password_lama_salah'], 422);
        }

        $pengguna->password             = Hash::make($data['password_baru']);
        $pengguna->wajib_ganti_password = false;
        $pengguna->save();

        // Perangkat lain dikeluarkan; perangkat ini tetap login
        $tokenIni = $pengguna->currentAccessToken()->id;
        $pengguna->tokens()->where('id', '!=', $tokenIni)->delete();
        DB::table('akun_perangkat_login')
            ->where('pengguna_id', $pengguna->id)->where('token_id', '!=', $tokenIni)->delete();

        NotifikasiApiController::buat($pengguna->id, 'Password berhasil diganti', 'Password akun Anda telah diperbarui');

        return response()->json(['pesan' => 'Password berhasil diganti.']);
    }

    /**
     * GET /api/v1/profil/foto  — pas foto milik siswa yang login.
     * Aturan jalur file sama dengan DataSiswaController::foto di sisi web.
     */
    public function foto(Request $request)
    {
        $siswa = $request->user()->siswa;
        $path  = $siswa?->foto;

        if (! $path) {
            return response()->json(['pesan' => 'Foto belum tersedia.'], 404);
        }

        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        $ekstensiAman = ['jpg', 'jpeg', 'png', 'webp'];

        foreach (['siswa/foto/' => 'disk', 'uploads/foto/' => 'public'] as $awalan => $lokasi) {
            if (! str_starts_with($path, $awalan)) {
                continue;
            }

            $nama = substr($path, strlen($awalan));

            if ($nama === ''
                || basename($nama) !== $nama
                || ! in_array(strtolower(pathinfo($nama, PATHINFO_EXTENSION)), $ekstensiAman, true)) {
                break;
            }

            if ($lokasi === 'disk') {
                $disk = Storage::disk('local');
                if ($disk->exists($path)) {
                    return response()->file($disk->path($path), $headers);
                }
            } elseif (is_file(public_path($path))) {
                return response()->file(public_path($path), $headers);
            }
        }

        return response()->json(['pesan' => 'Foto tidak ditemukan.'], 404);
    }

    private function dataProfil($pengguna): array
    {
        $siswa = $pengguna->siswa;

        $ta = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::orderByDesc('nama')->first();

        $riwayat = SiswaRiwayatKelas::with('kelas')
            ->where('siswa_id', $siswa->id)
            ->when($ta, fn ($q) => $q->where('tahun_ajaran_id', $ta->id))
            ->first()
            ?? SiswaRiwayatKelas::with('kelas')->where('siswa_id', $siswa->id)->orderByDesc('tahun_ajaran_id')->first();

        return [
            'nama'          => $siswa->nama_lengkap,
            'nisn'          => $siswa->nisn,
            'kelas'         => $riwayat?->kelas?->nama,
            'jenis_kelamin' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : null),
            'tempat_lahir'  => $siswa->tempat_lahir,
            'tanggal_lahir' => $siswa->tanggal_lahir,
            'alamat'        => BiodataApiController::gabungAlamat($siswa),
            'alamat_jalan'  => $siswa->alamat_jalan,
            'email'         => $pengguna->email,
            'nomor_hp'      => $siswa->nomor_hp,
            'foto_url'      => $siswa->foto ? url('/api/v1/profil/foto') : null,
        ];
    }
}