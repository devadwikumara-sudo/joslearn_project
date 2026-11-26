<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Admin\CatatanCadangan;
use App\Models\Auth\AkunSiswa;
use App\Models\Auth\Pengguna;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Services\CadanganDataSekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends Controller
{
    private const TABS = [
        'profil' => 'Profil Sekolah',
        'tahun-ajaran' => 'Tahun Ajaran',
        'cadangan' => 'Cadangan',
        'sampah' => 'Tempat Sampah',
        'log' => 'Log Aktivitas',
        'akun' => 'Akun',
    ];

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'profil');
        abort_unless(is_string($tab) && array_key_exists($tab, self::TABS), 404);

        $data = [];

        if ($tab === 'profil') {
            $setting = DB::table('sekolah_identitas')->where('id', 1)->first();
            $pengaturan = DB::table('sekolah_pengaturan')->pluck('isi', 'kunci');
            $kktp = $pengaturan->get('kktp', '75');
            $data = compact('setting', 'kktp');
        } elseif ($tab === 'tahun-ajaran') {
            $tahunAjaranList = SekolahTahunAjaran::query()->orderByDesc('id')->get();
            $semesterBerjalan = DB::table('sekolah_pengaturan')
                ->where('kunci', 'semester_berjalan')
                ->value('isi');
            $semesterBerjalan = in_array($semesterBerjalan, ['Ganjil', 'Genap'], true)
                ? $semesterBerjalan
                : 'Ganjil';
            $data = compact('tahunAjaranList', 'semesterBerjalan');
        } elseif ($tab === 'cadangan') {
            $cadanganList = CatatanCadangan::query()->latest('id')->limit(5)->get();
            $data = compact('cadanganList');
        } elseif ($tab === 'sampah') {
            $siswaTerhapus = SiswaBiodata::onlyTrashed()->orderByDesc('deleted_at')->get();
            $guruTerhapus = GuruBiodata::onlyTrashed()->orderByDesc('deleted_at')->get();
            $data = compact('siswaTerhapus', 'guruTerhapus');
        } elseif ($tab === 'log') {
            $lf = $request->query('lf', '');
            $lq = trim($request->query('lq', ''));
            $who2 = CatatanAktivitas::query()
                ->select('peran')
                ->whereNotNull('peran')
                ->distinct()
                ->pluck('peran')
                ->toArray();
            $userList = Pengguna::query()->pluck('username')->toArray();
            $whoList = array_values(array_unique(array_filter(array_merge($who2, $userList))));
            $query = CatatanAktivitas::with('pengguna')
                ->forAdminLog($lf, $lq)
                ->latest('id');
            $totalLogs = CatatanAktivitas::query()->count();
            $logs = $query->paginate(25)->withQueryString();
            $data = compact('lf', 'lq', 'whoList', 'totalLogs', 'logs');
        } elseif ($tab === 'akun') {
            $pengguna = Pengguna::with('guru')->orderBy('peran')->orderBy('username')->get();
            $akunSiswa = AkunSiswa::with('siswa:id,nama_lengkap,nisn')
                ->orderBy('username')
                ->paginate(25)
                ->withQueryString();
            $data = compact('pengguna', 'akunSiswa');
        }

        return view('admin.setting', [
            ...$data,
            'tab' => $tab,
            'tabs' => self::TABS,
        ]);
    }

    public function pulihkan(Request $request)
    {
        $tipe = $request->input('tipe');
        $id = $request->input('id');

        if ($tipe === 'siswa') {
            $item = SiswaBiodata::withTrashed()->findOrFail($id);
            $item->restore();

            return back()->with('success', "Siswa {$item->nama_lengkap} berhasil dipulihkan.");
        }

        if ($tipe === 'guru') {
            $item = GuruBiodata::withTrashed()->findOrFail($id);
            $item->restore();

            return back()->with('success', "Guru {$item->nama} berhasil dipulihkan.");
        }

        return back()->with('error', 'Tipe tidak dikenal.');
    }

    public function hapusPermanenSampah(Request $request)
    {
        $tipe = $request->input('tipe');
        $id = $request->input('id');

        if ($tipe === 'siswa') {
            $item = SiswaBiodata::withTrashed()->findOrFail($id);
            $nama = $item->nama_lengkap;
            $item->forceDelete();

            return back()->with('success', "Siswa {$nama} dihapus permanen.");
        }

        if ($tipe === 'guru') {
            $item = GuruBiodata::withTrashed()->findOrFail($id);
            $nama = $item->nama;
            $item->forceDelete();

            return back()->with('success', "Guru {$nama} dihapus permanen.");
        }

        return back()->with('error', 'Tipe tidak dikenal.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:30'],
            'tahun_ajaran_aktif_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('sekolah_tahun_ajaran', 'id'),
            ],
            'semester_berjalan' => ['sometimes', 'required', Rule::in(['Ganjil', 'Genap'])],
            'kktp' => ['required', 'numeric', 'between:0,100'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $identitas = [
                'nama' => $validated['nama'],
                'npsn' => $validated['npsn'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'desa' => $validated['desa'] ?? null,
                'kecamatan' => $validated['kecamatan'] ?? null,
                'kabupaten' => $validated['kabupaten'] ?? null,
                'provinsi' => $validated['provinsi'] ?? null,
                'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'] ?? null,
                'nip_kepala_sekolah' => $validated['nip_kepala_sekolah'] ?? null,
                'updated_at' => now(),
            ];

            if (DB::table('sekolah_identitas')->where('id', 1)->exists()) {
                DB::table('sekolah_identitas')->where('id', 1)->update($identitas);
            } else {
                DB::table('sekolah_identitas')->insert([
                    'id' => 1,
                    ...$identitas,
                    'created_at' => now(),
                ]);
            }

            if (isset($validated['tahun_ajaran_aktif_id'])) {
                $this->jadikanTahunBerjalan((int) $validated['tahun_ajaran_aktif_id']);
            }

            $pengaturan = ['kktp' => (string) $validated['kktp']];
            if (isset($validated['semester_berjalan'])) {
                $pengaturan['semester_berjalan'] = $validated['semester_berjalan'];
            }

            foreach ($pengaturan as $key => $value) {
                DB::table('sekolah_pengaturan')->updateOrInsert(
                    ['kunci' => $key],
                    ['isi' => $value, 'updated_at' => now()]
                );
            }

            $this->catatAktivitas($request, 'update_setting', 'Memperbarui identitas dan pengaturan sekolah.', 'setting');
        });

        return back()->with('success', 'Pengaturan sekolah berhasil diperbarui.');
    }

    public function tambahTahunAjaran(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_ta' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('sekolah_tahun_ajaran', 'nama'),
            ],
        ], [
            'nama_ta.regex' => 'Format tahun ajaran harus seperti 2026/2027.',
            'nama_ta.unique' => 'Tahun ajaran ini sudah ada.',
        ]);

        $aktif = $request->boolean('aktif')
            || ! SekolahTahunAjaran::query()->where('sedang_berjalan', true)->exists();

        DB::transaction(function () use ($aktif, $request, $validated): void {
            if ($aktif) {
                SekolahTahunAjaran::query()->update(['sedang_berjalan' => false]);
            }

            $tahunAjaran = SekolahTahunAjaran::query()->create([
                'nama' => $validated['nama_ta'],
                'sedang_berjalan' => $aktif,
            ]);

            $this->catatAktivitas(
                $request,
                'tambah_tahun_ajaran',
                "Menambahkan tahun ajaran {$tahunAjaran->nama}.",
                'tahun_ajaran',
                $tahunAjaran->id
            );
        });

        return back()->with('success', "Tahun Ajaran {$validated['nama_ta']} berhasil ditambahkan.");
    }

    public function setTahunAjaranAktif(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ta_id' => ['required', 'integer', Rule::exists('sekolah_tahun_ajaran', 'id')],
            'semester_berjalan' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $tahunAjaran = $this->jadikanTahunBerjalan((int) $validated['ta_id']);
            DB::table('sekolah_pengaturan')->updateOrInsert(
                ['kunci' => 'semester_berjalan'],
                ['isi' => $validated['semester_berjalan'], 'updated_at' => now()]
            );
            $this->catatAktivitas(
                $request,
                'ubah_tahun_ajaran_berjalan',
                "Menetapkan {$tahunAjaran->nama} sebagai tahun ajaran berjalan dengan semester {$validated['semester_berjalan']}.",
                'tahun_ajaran',
                $tahunAjaran->id
            );
        });

        return back()->with('success', 'Tahun ajaran aktif dan semester berjalan berhasil diperbarui.');
    }

    public function unduhCadangan(Request $request, CadanganDataSekolah $cadangan): Response
    {
        $backup = $cadangan->buatCadangan((int) $request->user()->id);
        $this->catatAktivitas(
            $request,
            'unduh_cadangan',
            "Mengunduh cadangan data {$backup->nama_file}.",
            'cadangan',
            $backup->id
        );

        return response()->download(
            Storage::disk('local')->path($backup->lokasi),
            $backup->nama_file,
            ['Content-Type' => 'application/json']
        );
    }

    public function pulihkanCadangan(Request $request, CadanganDataSekolah $cadangan): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json', 'extensions:json', 'max:102400'],
            'konfirmasi' => ['required', 'string', Rule::in(['PULIHKAN'])],
        ], [
            'konfirmasi.in' => 'Ketik PULIHKAN untuk menyetujui pemulihan cadangan.',
            'backup_file.mimes' => 'File yang dipilih harus berupa cadangan JSON.',
            'backup_file.extensions' => 'Ekstensi file cadangan harus .json.',
        ]);

        $restorePoint = $cadangan->pulihkan(
            $validated['backup_file']->get(),
            (int) $request->user()->id
        );
        $this->catatAktivitas(
            $request,
            'pulihkan_cadangan',
            "Memulihkan data dari file {$validated['backup_file']->getClientOriginalName()}. Cadangan sebelum pemulihan: {$restorePoint->nama_file}.",
            'cadangan',
            $restorePoint->id
        );

        return back()->with('success', 'Data berhasil dipulihkan. Cadangan sebelum pemulihan juga telah dibuat.');
    }

    public function hapusDataTahunAjaran(Request $request, CadanganDataSekolah $cadangan): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('sekolah_tahun_ajaran', 'id'),
            ],
            'konfirmasi' => ['required', 'string'],
        ]);

        $tahunAjaran = SekolahTahunAjaran::query()->findOrFail($validated['tahun_ajaran_id']);
        $teksKonfirmasi = 'HAPUS '.$tahunAjaran->nama;

        if (! hash_equals($teksKonfirmasi, $validated['konfirmasi'])) {
            throw ValidationException::withMessages([
                'konfirmasi' => "Ketik {$teksKonfirmasi} untuk menghapus data tahun ajaran ini.",
            ]);
        }

        $backup = $cadangan->buatCadangan((int) $request->user()->id);

        DB::transaction(function () use ($request, $tahunAjaran, $backup): void {
            $riwayatIds = DB::table('siswa_riwayat_kelas')
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->pluck('id');

            if ($riwayatIds->isNotEmpty()) {
                DB::table('siswa_nilai')->whereIn('riwayat_kelas_id', $riwayatIds)->delete();
                DB::table('siswa_kehadiran')->whereIn('riwayat_kelas_id', $riwayatIds)->delete();
                DB::table('siswa_ekskul')->whereIn('riwayat_kelas_id', $riwayatIds)->delete();
                DB::table('siswa_riwayat_mutasi')->whereIn('riwayat_kelas_id', $riwayatIds)->delete();
                DB::table('siswa_riwayat_kelas')->whereIn('id', $riwayatIds)->delete();
            }

            DB::table('guru_wali_kelas')->where('tahun_ajaran_id', $tahunAjaran->id)->delete();
            DB::table('guru_mengajar')->where('tahun_ajaran_id', $tahunAjaran->id)->delete();
            DB::table('catatan_kenaikan_kelas')
                ->where('tahun_ajaran_asal_id', $tahunAjaran->id)
                ->orWhere('tahun_ajaran_tujuan_id', $tahunAjaran->id)
                ->delete();

            $this->catatAktivitas(
                $request,
                'hapus_data_tahun_ajaran',
                "Menghapus seluruh data akademik tahun ajaran {$tahunAjaran->nama}; cadangan {$backup->nama_file} dibuat terlebih dahulu.",
                'tahun_ajaran',
                $tahunAjaran->id
            );
        });

        return back()->with(
            'success',
            "Seluruh data akademik {$tahunAjaran->nama} berhasil dihapus. Cadangan {$backup->nama_file} telah dibuat."
        );
    }

    public function hapusAkun(int $id): RedirectResponse
    {
        if ($id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        $pengguna = Pengguna::findOrFail($id);
        $pengguna->delete();

        return back()->with('success', "Akun {$pengguna->username} berhasil dihapus.");
    }

    public function updateProfilAkun(Request $request, int $id): RedirectResponse
    {
        $pengguna = Pengguna::query()
            ->whereIn('peran', ['admin', 'guru'])
            ->findOrFail($id);

        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        if ($request->has('aktif') && $pengguna->peran !== 'guru') {
            throw ValidationException::withMessages([
                'aktif' => 'Status akun hanya dapat diubah untuk akun guru.',
            ]);
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:120',
                Rule::unique('akun_pengguna', 'email')->ignore($pengguna->id),
            ],
            'aktif' => ['sometimes', 'boolean'],
        ], [
            'nama.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
        ]);

        $nama = trim($validated['nama']);
        $statusAkunBaru = $pengguna->peran === 'guru' && array_key_exists('aktif', $validated)
            ? (bool) $validated['aktif']
            : $pengguna->aktif;
        $statusAkunBerubah = $pengguna->peran === 'guru' && $statusAkunBaru !== $pengguna->aktif;

        DB::transaction(function () use ($pengguna, $nama, $validated, $statusAkunBaru): void {
            $pengguna->nama = $nama;
            $pengguna->email = $validated['email'];
            if ($pengguna->peran === 'guru') {
                $pengguna->aktif = $statusAkunBaru;
            }
            $pengguna->save();

            if ($pengguna->peran === 'guru' && $pengguna->guru) {
                $pengguna->guru->update([
                    'nama' => $nama,
                    'email' => $validated['email'],
                ]);
            }
        });

        $keterangan = "Memperbarui nama dan email akun {$pengguna->username}.";
        if ($statusAkunBerubah) {
            $keterangan .= ' Status akun guru menjadi '.($statusAkunBaru ? 'aktif' : 'nonaktif').'.';
        }

        $this->catatAktivitas(
            $request,
            'ubah_profil_akun',
            $keterangan,
            'akun_pengguna',
            $pengguna->id
        );

        return back()->with(
            'success',
            $request->has('aktif')
                ? 'Nama, email, dan status akun guru berhasil diperbarui.'
                : 'Nama dan email akun berhasil diperbarui.'
        );
    }

    public function hapusLog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lf' => ['nullable', 'string', 'max:50'],
            'lq' => ['nullable', 'string', 'max:255'],
        ]);

        $lf = $validated['lf'] ?? '';
        $lq = trim($validated['lq'] ?? '');

        return DB::transaction(function () use ($request, $lf, $lq): RedirectResponse {
            $query = CatatanAktivitas::query()->forAdminLog($lf, $lq);
            $jumlah = $query->count();

            if ($jumlah > 0) {
                $query->delete();
                $this->catatAktivitas(
                    $request,
                    'hapus_log_aktivitas',
                    "Menghapus {$jumlah} catatan aktivitas sesuai filter.",
                    'catatan_aktivitas'
                );
            }

            $pesan = $jumlah > 0
                ? "{$jumlah} catatan aktivitas sesuai filter berhasil dihapus."
                : 'Tidak ada catatan aktivitas yang cocok untuk dihapus.';

            return redirect()
                ->route('admin.setting', ['tab' => 'log', 'lf' => $lf, 'lq' => $lq])
                ->with('success', $pesan);
        });
    }

    private function jadikanTahunBerjalan(int $id): SekolahTahunAjaran
    {
        $tahunAjaran = SekolahTahunAjaran::query()->lockForUpdate()->findOrFail($id);
        SekolahTahunAjaran::query()->update(['sedang_berjalan' => false]);
        $tahunAjaran->update(['sedang_berjalan' => true]);

        return $tahunAjaran;
    }

    private function catatAktivitas(
        Request $request,
        string $aksi,
        string $keterangan,
        string $objekTipe,
        ?int $objekId = null
    ): void {
        CatatanAktivitas::query()->create([
            'pengguna_id' => $request->user()->id,
            'peran' => $request->user()->peran,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'objek_tipe' => $objekTipe,
            'objek_id' => $objekId,
            'alamat_ip' => $request->ip(),
        ]);
    }
}
