<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DataGuruController extends Controller
{
    public function index(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;

        $guruList = GuruBiodata::whereNull('deleted_at')
            ->with([
                'akun',
                'waliKelas' => fn ($query) => $query->where('tahun_ajaran_id', $taAktifId)->with('kelas'),
                'mengajar' => fn ($query) => $query->where('tahun_ajaran_id', $taAktifId)->with(['mapel', 'kelas']),
            ])
            ->get();

        $totalGuru = $guruList->count();
        $totalGuruMapel = $guruList->filter(fn ($g) => $g->mengajar && $g->mengajar->count() > 0)->count();
        $totalWaliKelas = $guruList->filter(fn ($g) => $g->waliKelas && $g->waliKelas->count() > 0)->count();

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $mapelList = SekolahMapel::pluck('nama', 'kode')->toArray();
        if (empty($mapelList)) {
            $mapelList = [
                'mtk' => 'Matematika',
                'ind' => 'Bahasa Indonesia',
                'big' => 'Bahasa Inggris',
                'pjk' => 'PJOK',
                'pnc' => 'PPKn / Pancasila',
                'agm' => 'Pendidikan Agama',
                'ipa' => 'Ilmu Pengetahuan Alam',
                'ips' => 'Ilmu Pengetahuan Sosial',
                'inf' => 'Informatika',
                'sbd' => 'Seni Budaya',
                'sej' => 'Sejarah',
            ];
        }

        return view('admin.data-guru', compact(
            'guruList',
            'totalGuru',
            'totalGuruMapel',
            'totalWaliKelas',
            'kelasList',
            'mapelList',
            'taAktif'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedGuruInput($request);
        $yearId = $this->currentAcademicYearId();

        $guru = DB::transaction(function () use ($validated, $yearId, $request): GuruBiodata {
            $guru = GuruBiodata::create($this->guruAttributes($validated));
            $this->syncAssignments($guru, $yearId, $validated);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'tambah_guru',
                'keterangan' => "Menambahkan guru baru: {$guru->nama} (NIP: ".($guru->nip ?? '-').')',
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);

            return $guru;
        });

        return redirect()->route('admin.data-guru')->with('success', "Data guru {$guru->nama} berhasil disimpan.");
    }

    public function edit(int $id): JsonResponse
    {
        $taAktifId = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id')
            ?? SekolahTahunAjaran::latest('id')->value('id')
            ?? 0;
        $guru = GuruBiodata::with([
            'waliKelas' => fn ($query) => $query->where('tahun_ajaran_id', $taAktifId)->with('kelas'),
            'mengajar' => fn ($query) => $query->where('tahun_ajaran_id', $taAktifId)->with(['mapel', 'kelas']),
        ])->findOrFail($id);

        return response()->json($guru);
    }

    public function update(Request $request, int $id)
    {
        $guru = GuruBiodata::findOrFail($id);
        $validated = $this->validatedGuruInput($request);
        $yearId = $this->currentAcademicYearId();

        DB::transaction(function () use ($guru, $validated, $yearId, $request): void {
            $guru->update($this->guruAttributes($validated));
            $this->syncAssignments($guru, $yearId, $validated);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'edit_guru',
                'keterangan' => "Memperbarui biodata dan penugasan guru: {$guru->nama}",
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.data-guru')->with('success', "Data guru {$guru->nama} berhasil diperbarui.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedGuruInput(Request $request): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:20'],
            'nik' => ['nullable', 'string', 'max:16'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'pendidikan_terakhir' => ['nullable', 'string', 'max:30'],
            'status_kepegawaian' => ['nullable', 'string', 'max:30'],
            'mulai_bertugas' => ['nullable', 'integer', 'between:1000,9999'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'is_wali' => ['sometimes', 'boolean'],
            'wali_kelas' => ['nullable', 'required_if:is_wali,1', 'string', 'exists:sekolah_kelas,nama'],
            'is_mapel' => ['sometimes', 'boolean'],
            'penugasan_mengajar' => ['nullable', 'required_if:is_mapel,1', 'array', 'min:1'],
            'penugasan_mengajar.*.kelas' => ['required', 'string', 'exists:sekolah_kelas,nama'],
            'penugasan_mengajar.*.mapel' => ['required', 'string', 'exists:sekolah_mapel,kode'],
        ]);

        $assignmentKeys = [];
        foreach ($validated['penugasan_mengajar'] ?? [] as $index => $assignment) {
            $key = $assignment['kelas'].'|'.$assignment['mapel'];
            if (isset($assignmentKeys[$key])) {
                throw ValidationException::withMessages([
                    "penugasan_mengajar.{$index}.mapel" => 'Penugasan kelas dan mata pelajaran tidak boleh ganda.',
                ]);
            }
            $assignmentKeys[$key] = true;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function guruAttributes(array $validated): array
    {
        return array_replace(array_intersect_key($validated, array_flip([
            'nama',
            'nip',
            'nuptk',
            'nik',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'pendidikan_terakhir',
            'status_kepegawaian',
            'mulai_bertugas',
            'nomor_hp',
            'email',
            'alamat',
        ])), [
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? 'L',
            'status_kepegawaian' => $validated['status_kepegawaian'] ?? 'PNS',
        ]);
    }

    private function currentAcademicYearId(): int
    {
        $yearId = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('id')
            ?? SekolahTahunAjaran::latest('id')->value('id');

        abort_if($yearId === null, 422, 'Tahun ajaran berjalan belum tersedia.');

        return (int) $yearId;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncAssignments(GuruBiodata $guru, int $yearId, array $validated): void
    {
        $previousHomeroomClassIds = DB::table('guru_wali_kelas')
            ->where('tahun_ajaran_id', $yearId)
            ->where('guru_id', $guru->id)
            ->pluck('kelas_id')
            ->map(static fn ($classId): int => (int) $classId)
            ->all();

        DB::table('guru_wali_kelas')
            ->where('tahun_ajaran_id', $yearId)
            ->where('guru_id', $guru->id)
            ->delete();

        $affectedHomeroomClassIds = $previousHomeroomClassIds;
        if (! empty($validated['is_wali'])) {
            $classId = (int) SekolahKelas::where('nama', $validated['wali_kelas'])->value('id');
            DB::table('guru_wali_kelas')->updateOrInsert(
                ['tahun_ajaran_id' => $yearId, 'kelas_id' => $classId],
                ['guru_id' => $guru->id, 'updated_at' => now()]
            );
            $affectedHomeroomClassIds[] = $classId;
        }

        foreach (array_unique($affectedHomeroomClassIds) as $classId) {
            $homeroom = DB::table('guru_wali_kelas as assignment')
                ->join('guru_biodata as guru', 'guru.id', '=', 'assignment.guru_id')
                ->where('assignment.tahun_ajaran_id', $yearId)
                ->where('assignment.kelas_id', $classId)
                ->select('assignment.guru_id', 'guru.nama', 'guru.nip')
                ->first();

            DB::table('siswa_riwayat_kelas')
                ->where('tahun_ajaran_id', $yearId)
                ->where('kelas_id', $classId)
                ->update([
                    'wali_guru_id' => $homeroom?->guru_id,
                    'nama_wali_kelas' => $homeroom?->nama,
                    'nip_wali_kelas' => $homeroom?->nip,
                    'updated_at' => now(),
                ]);
        }

        DB::table('guru_mengajar')
            ->where('tahun_ajaran_id', $yearId)
            ->where('guru_id', $guru->id)
            ->delete();

        if (empty($validated['is_mapel'])) {
            return;
        }

        foreach ($validated['penugasan_mengajar'] as $assignment) {
            $classId = (int) SekolahKelas::where('nama', $assignment['kelas'])->value('id');
            $mapelId = (int) SekolahMapel::where('kode', $assignment['mapel'])->value('id');

            DB::table('guru_mengajar')->updateOrInsert(
                [
                    'tahun_ajaran_id' => $yearId,
                    'kelas_id' => $classId,
                    'mapel_id' => $mapelId,
                ],
                ['guru_id' => $guru->id]
            );
        }
    }

    public function destroy(int $id)
    {
        $guru = GuruBiodata::findOrFail($id);
        $nama = $guru->nama;
        $guru->delete();

        return redirect()->route('admin.data-guru')->with('success', "Guru {$nama} berhasil dipindahkan ke Tempat Sampah.");
    }

    public function buatAkun(int $id, Request $request)
    {
        $guru = GuruBiodata::whereNull('deleted_at')->findOrFail($id);
        if (! $guru->nip) {
            return back()->with('error', 'Guru harus memiliki NIP untuk dibuatkan akun.');
        }

        if ($guru->akun()->exists() || Pengguna::where('username', $guru->nip)->exists()) {
            return back()->with('error', 'Guru ini sudah memiliki akun.');
        }

        if ($guru->email && Pengguna::where('email', $guru->email)->exists()) {
            return back()->with('error', 'Email guru sudah digunakan oleh akun lain.');
        }

        $sandiSementara = Str::password(16);

        DB::transaction(function () use ($guru, $request, $sandiSementara): void {
            $guruTerkunci = GuruBiodata::whereNull('deleted_at')->lockForUpdate()->findOrFail($guru->id);
            if ($guruTerkunci->akun()->exists() || Pengguna::where('username', $guruTerkunci->nip)->exists()) {
                abort(409, 'Guru ini sudah memiliki akun.');
            }
            if ($guruTerkunci->email && Pengguna::where('email', $guruTerkunci->email)->exists()) {
                abort(409, 'Email guru sudah digunakan oleh akun lain.');
            }

            Pengguna::create([
                'peran' => 'guru',
                'username' => $guruTerkunci->nip,
                'email' => $guruTerkunci->email,
                'password' => Hash::make($sandiSementara),
                'aktif' => true,
                'guru_id' => $guruTerkunci->id,
                'wajib_ganti_password' => true,
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran,
                'aksi' => 'buat_akun_guru',
                'keterangan' => "Membuat akun guru untuk {$guruTerkunci->nama} (NIP: {$guruTerkunci->nip}).",
                'objek_tipe' => 'guru',
                'objek_id' => $guruTerkunci->id,
                'alamat_ip' => $request->ip(),
            ]);

        });

        session()->flash('sandi_sementara', [
            'username' => $guru->nip,
            'password' => $sandiSementara,
        ]);

        return redirect()->route('admin.data-guru')->with('success', "Akun untuk {$guru->nama} berhasil dibuat.");
    }

    public function resetSandi(int $id, Request $request)
    {
        $guru = GuruBiodata::whereNull('deleted_at')->findOrFail($id);
        $akun = $guru->akun;
        if (! $akun) {
            return back()->with('error', 'Guru ini belum memiliki akun.');
        }

        $sandiSementara = Str::password(16);

        $akunTerkunci = DB::transaction(function () use ($guru, $akun, $request, $sandiSementara): Pengguna {
            $akunTerkunci = Pengguna::where('guru_id', $guru->id)->whereKey($akun->id)->lockForUpdate()->firstOrFail();
            $akunTerkunci->password = Hash::make($sandiSementara);
            $akunTerkunci->wajib_ganti_password = true;
            $akunTerkunci->save();

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran,
                'aksi' => 'reset_sandi_guru',
                'keterangan' => "Mereset sandi akun guru {$guru->nama} (NIP: {$guru->nip}).",
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);

            return $akunTerkunci;
        });

        session()->flash('sandi_sementara', [
            'username' => $akunTerkunci->username,
            'password' => $sandiSementara,
        ]);

        return redirect()->route('admin.data-guru')->with('success', "Sandi akun {$guru->nama} berhasil direset.");
    }
}
