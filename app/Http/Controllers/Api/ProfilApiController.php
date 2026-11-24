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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

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
        $siswa = $pengguna->siswa;

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
        $siswa = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:120', Rule::unique('akun_siswa', 'email')->ignore($pengguna->id)],
            'nomor_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]*$/'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'nomor_hp.regex' => 'Nomor HP hanya boleh berisi angka.',
        ]);

        $email = strtolower(trim($data['email']));
        DB::transaction(function () use ($pengguna, $siswa, $data, $email): void {
            $akunSiswa = DB::table('akun_siswa')->where('id', $pengguna->id);

            if (! $akunSiswa->exists()) {
                abort(404, 'Akun siswa tidak ditemukan.');
            }

            $akunSiswa->update(['email' => $email, 'updated_at' => now()]);
            $siswa->nomor_hp = $data['nomor_hp'] ?? null;
            $siswa->alamat_jalan = $data['alamat'] ?? null;
            $siswa->save();
        });
        $pengguna->email = $email;

        NotifikasiApiController::buat($pengguna->id, 'Profil berhasil diperbarui', 'Perubahan profil telah tersimpan di profil anda');

        return response()->json([
            'pesan' => 'Profil berhasil diperbarui.',
            'data' => $this->dataProfil($pengguna->fresh('siswa')),
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
            'password_baru.min' => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password tidak sama.',
            'password_baru.different' => 'Password baru harus berbeda dari password lama.',
        ]);

        if (! Hash::check($data['password_lama'], $pengguna->password)) {
            return response()->json(['pesan' => 'Password lama salah.', 'kode' => 'password_lama_salah'], 422);
        }

        $pengguna->password = Hash::make($data['password_baru']);
        $pengguna->wajib_ganti_password = false;
        $pengguna->save();

        // Perangkat lain dikeluarkan; perangkat ini tetap login
        $tokenIni = $pengguna->currentAccessToken()->id;
        $pengguna->tokens()->where('id', '!=', $tokenIni)->delete();
        DB::table('akun_perangkat_login')
            ->where('akun_siswa_id', $pengguna->id)->where('token_id', '!=', $tokenIni)->delete();

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
        $path = $siswa?->foto;

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

    /**
     * POST /api/v1/profil/foto  — unggah pas foto (multipart, field foto_file).
     * Aturan berkas sama dengan form pendataan web: JPG/PNG/WEBP, maksimal 2 MB,
     * disimpan di disk privat `local` pada siswa/foto dan dibaca lewat GET /profil/foto.
     */
    public function simpanFoto(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $siswa = $pengguna->siswa;

        if (! $siswa) {
            return response()->json(['pesan' => 'Data siswa tidak ditemukan.'], 404);
        }

        $request->validate([
            'foto_file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto_file.required' => 'Pilih foto terlebih dahulu.',
            'foto_file.file' => 'Foto gagal diunggah. Coba lagi.',
            'foto_file.uploaded' => 'Foto gagal diunggah. Ukuran foto maksimal 2 MB.',
            'foto_file.image' => 'File harus berupa gambar.',
            'foto_file.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto_file.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $berkas = $request->file('foto_file');
        $nama = 'foto_'.($siswa->nisn ?: $siswa->id).'_'.Str::uuid().'.'.$berkas->extension();
        $pathBaru = $berkas->storeAs('siswa/foto', $nama, 'local');

        if ($pathBaru === false) {
            return response()->json(['pesan' => 'Foto gagal disimpan. Coba lagi.'], 500);
        }

        $pathLama = $siswa->foto;

        try {
            $siswa->foto = $pathBaru;
            $siswa->foto_ada = true;
            $siswa->save();
        } catch (Throwable $e) {
            Storage::disk('local')->delete($pathBaru);

            throw $e;
        }

        $this->hapusFotoLama($pathLama);

        NotifikasiApiController::buat($pengguna->id, 'Foto profil berhasil diperbarui', 'Foto profil Anda telah tersimpan.');

        return response()->json([
            'pesan' => 'Foto profil berhasil diperbarui.',
            'data' => ['foto_url' => url('/api/v1/profil/foto')],
        ]);
    }

    /**
     * Hapus berkas foto sebelumnya. Hanya jalur siswa/foto/ di disk privat yang
     * disentuh; foto lama dari jalur lain (uploads/foto/) dibiarkan apa adanya.
     */
    private function hapusFotoLama(?string $path): void
    {
        $awalan = 'siswa/foto/';

        if (! $path || ! str_starts_with($path, $awalan)) {
            return;
        }

        $nama = substr($path, strlen($awalan));

        if ($nama === '' || basename($nama) !== $nama) {
            return;
        }

        Storage::disk('local')->delete($path);
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
            'nama' => $siswa->nama_lengkap,
            'nisn' => $siswa->nisn,
            'kelas' => $riwayat?->kelas?->nama,
            'jenis_kelamin' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : null),
            'tempat_lahir' => $siswa->tempat_lahir,
            'tanggal_lahir' => $siswa->tanggal_lahir,
            'alamat' => BiodataApiController::gabungAlamat($siswa),
            'alamat_jalan' => $siswa->alamat_jalan,
            'email' => $pengguna->email,
            'nomor_hp' => $siswa->nomor_hp,
            'foto_url' => $siswa->foto ? url('/api/v1/profil/foto') : null,
        ];
    }
}
