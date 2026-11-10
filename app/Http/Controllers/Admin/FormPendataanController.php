<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBerkas;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class FormPendataanController extends Controller
{
    private const STRING_LIMITS = [
        'nama_panggilan' => 64,
        'nomor_induk' => 64,
        'tempat_lahir' => 100,
        'agama' => 64,
        'kewarganegaraan' => 64,
        'jumlah_saudara' => 64,
        'status_yatim' => 64,
        'bahasa_sehari_hari' => 64,
        'alamat_jalan' => 255,
        'alamat_rt_rw' => 64,
        'alamat_desa' => 100,
        'alamat_kecamatan' => 100,
        'alamat_kabupaten' => 100,
        'alamat_provinsi' => 100,
        'alamat_kode_pos' => 10,
        'nomor_hp' => 64,
        'tempat_tinggal' => 64,
        'golongan_darah' => 5,
        'penyakit' => 255,
        'kelainan_fisik' => 255,
        'sekolah_asal' => 150,
        'tanggal_ijazah_smp' => 64,
        'nomor_ijazah_smp' => 64,
        'jenis_registrasi' => 64,
        'pindahan_dari_sekolah' => 150,
        'alasan_pindah' => 255,
        'kelas_diterima' => 10,
        'rombel_diterima' => 10,
        'nama_ayah' => 150,
        'agama_ayah' => 64,
        'kewarganegaraan_ayah' => 64,
        'pendidikan_ayah' => 64,
        'pekerjaan_ayah' => 100,
        'penghasilan_ayah' => 64,
        'nomor_hp_ayah' => 64,
        'keadaan_ayah' => 64,
        'nama_ibu' => 150,
        'agama_ibu' => 64,
        'kewarganegaraan_ibu' => 64,
        'pendidikan_ibu' => 64,
        'pekerjaan_ibu' => 100,
        'penghasilan_ibu' => 64,
        'nomor_hp_ibu' => 64,
        'keadaan_ibu' => 64,
        'ortu_alamat_jalan' => 255,
        'ortu_alamat_rt_rw' => 64,
        'ortu_alamat_desa' => 100,
        'ortu_alamat_kecamatan' => 100,
        'ortu_alamat_kabupaten' => 100,
        'ortu_alamat_provinsi' => 100,
        'ortu_alamat_kode_pos' => 10,
        'nama_wali' => 150,
        'agama_wali' => 64,
        'kewarganegaraan_wali' => 64,
        'pendidikan_wali' => 64,
        'pekerjaan_wali' => 100,
        'penghasilan_wali' => 64,
        'nomor_hp_wali' => 64,
        'wali_alamat_jalan' => 255,
        'wali_alamat_rt_rw' => 64,
        'wali_alamat_desa' => 100,
        'wali_alamat_kecamatan' => 100,
        'wali_alamat_kabupaten' => 100,
        'wali_alamat_provinsi' => 100,
        'wali_alamat_kode_pos' => 10,
        'kelas_keluar' => 64,
        'alasan_keluar' => 255,
        'sekolah_tujuan' => 150,
        'nomor_peserta_psaj' => 64,
        'nomor_skl' => 64,
        'nomor_ijazah_sma' => 64,
        'nomor_transkrip' => 64,
        'kegiatan_setelah_lulus' => 255,
    ];

    public function index(): View
    {
        $tahunAjaranList = $this->academicYearOptions();
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);
        $tahunAjaranTerpilih = SekolahTahunAjaran::where('sedang_berjalan', 1)->value('nama')
            ?? $tahunAjaranList[0]
            ?? null;

        return view('admin.form-pendataan', [
            'edit' => null,
            'kelasList' => $kelasList,
            'tahunAjaranList' => $tahunAjaranList,
            'tahunAjaranTerpilih' => $tahunAjaranTerpilih,
        ]);
    }

    public function edit(int $id): View
    {
        $tahunAjaranList = $this->academicYearOptions();
        $kelasList = SekolahKelas::orderBy('nama')->pluck('nama')->all();
        sort($kelasList, SORT_NATURAL);
        $siswa = SiswaBiodata::with(['berkas', 'riwayatKelas' => function ($query) {
            $query->with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id');
        }])->findOrFail($id);
        $tahunAjaranAktif = SekolahTahunAjaran::where('sedang_berjalan', 1)->first();
        $tahunAjaranTerpilih = $siswa->riwayatKelas
            ->firstWhere('tahun_ajaran_id', $tahunAjaranAktif?->id)
            ?->tahunAjaran?->nama
            ?? $siswa->riwayatKelas->first()?->tahunAjaran?->nama
            ?? $tahunAjaranAktif?->nama
            ?? $tahunAjaranList[0]
            ?? null;

        return view('admin.form-pendataan', [
            'edit' => $siswa,
            'kelasList' => $kelasList,
            'tahunAjaranList' => $tahunAjaranList,
            'tahunAjaranTerpilih' => $tahunAjaranTerpilih,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->validationRules());
        $tahunAjaran = SekolahTahunAjaran::firstOrCreate(
            ['nama' => $data['tahun_ajaran']],
            ['sedang_berjalan' => false]
        );
        $kelas = SekolahKelas::where('nama', $data['kelas'])->firstOrFail();

        unset($data['kelas'], $data['status'], $data['tahun_ajaran'], $data['foto_file'], $data['berkas']);
        $this->normalizeDocumentFlags($request, $data);
        $fotoPath = null;
        $uploadedAttachmentPaths = [];
        $replacedAttachmentPaths = [];

        try {
            $siswa = DB::transaction(function () use ($request, $data, $tahunAjaran, $kelas, &$fotoPath, &$uploadedAttachmentPaths, &$replacedAttachmentPaths): SiswaBiodata {
                $this->storePhoto($request, $data, $fotoPath);
                $siswa = SiswaBiodata::create($data);
                $this->storeAttachments($request, $siswa, $uploadedAttachmentPaths, $replacedAttachmentPaths);

                SiswaRiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'kelas_id' => $kelas->id,
                    'status' => $request->input('status') ?: 'Aktif',
                ]);

                CatatanAktivitas::create([
                    'pengguna_id' => auth()->id(),
                    'peran' => auth()->user()->peran ?? 'admin',
                    'aksi' => 'tambah_siswa',
                    'keterangan' => "Menambahkan siswa baru: {$siswa->nama_lengkap} ({$siswa->nisn})",
                    'objek_tipe' => 'siswa',
                    'objek_id' => $siswa->id,
                    'alamat_ip' => $request->ip(),
                ]);

                return $siswa;
            });
        } catch (\Throwable $exception) {
            if ($fotoPath !== null) {
                Storage::disk('local')->delete($fotoPath);
            }
            $this->deleteStoredFiles($uploadedAttachmentPaths);

            if (! $exception instanceof RuntimeException) {
                throw $exception;
            }

            Log::error('Gagal menyimpan biodata siswa.', [
                'admin_id' => auth()->id(),
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Gagal menyimpan data siswa. Periksa log aplikasi atau hubungi administrator.');
        }

        $this->deleteStoredFiles($replacedAttachmentPaths);

        $routeName = auth()->user()?->peran === 'guru' ? 'guru.data-siswa' : 'admin.data-siswa';

        return redirect()
            ->route($routeName, [
                'kelas' => $kelas->nama,
                'tahun_ajaran' => $tahunAjaran->id,
            ])
            ->with('success', 'Data siswa berhasil disimpan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $siswa = SiswaBiodata::findOrFail($id);
        $data = $request->validate($this->validationRules($siswa->id));
        $tahunAjaran = SekolahTahunAjaran::firstOrCreate(
            ['nama' => $data['tahun_ajaran']],
            ['sedang_berjalan' => false]
        );
        $kelas = SekolahKelas::where('nama', $data['kelas'])->firstOrFail();

        unset($data['kelas'], $data['status'], $data['tahun_ajaran'], $data['foto_file'], $data['berkas']);
        $this->normalizeDocumentFlags($request, $data);
        $fotoPath = null;
        $uploadedAttachmentPaths = [];
        $replacedAttachmentPaths = [];

        try {
            DB::transaction(function () use ($request, $data, $siswa, $tahunAjaran, $kelas, &$fotoPath, &$uploadedAttachmentPaths, &$replacedAttachmentPaths): void {
                $this->storePhoto($request, $data, $fotoPath);
                $siswa->update($data);
                $this->storeAttachments($request, $siswa, $uploadedAttachmentPaths, $replacedAttachmentPaths);
                $riwayat = SiswaRiwayatKelas::withTrashed()->firstOrNew([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ]);
                $riwayat->kelas_id = $kelas->id;
                $riwayat->status = $request->input('status') ?: ($riwayat->status ?? 'Aktif');
                $riwayat->deleted_at = null;
                $riwayat->save();

                CatatanAktivitas::create([
                    'pengguna_id' => auth()->id(),
                    'peran' => auth()->user()->peran ?? 'admin',
                    'aksi' => 'edit_siswa',
                    'keterangan' => "Memperbarui biodata siswa: {$siswa->nama_lengkap} ({$siswa->nisn})",
                    'objek_tipe' => 'siswa',
                    'objek_id' => $siswa->id,
                    'alamat_ip' => $request->ip(),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($fotoPath !== null) {
                Storage::disk('local')->delete($fotoPath);
            }
            $this->deleteStoredFiles($uploadedAttachmentPaths);

            if (! $exception instanceof RuntimeException) {
                throw $exception;
            }

            Log::error('Gagal memperbarui biodata siswa.', [
                'admin_id' => auth()->id(),
                'siswa_id' => $siswa->id,
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui data siswa. Periksa log aplikasi atau hubungi administrator.');
        }

        $this->deleteStoredFiles($replacedAttachmentPaths);

        return redirect()
            ->route('admin.data-siswa', [
                'kelas' => $kelas->nama,
                'tahun_ajaran' => $tahunAjaran->id,
            ])
            ->with('success', 'Perubahan biodata siswa berhasil disimpan.');
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    private function validationRules(?int $siswaId = null): array
    {
        $nisnUnique = Rule::unique('siswa_biodata', 'nisn')->whereNull('deleted_at');
        if ($siswaId !== null) {
            $nisnUnique->ignore($siswaId);
        }

        $rules = [
            'nama_lengkap' => 'required|string|max:150',
            'nisn' => ['required', 'digits:10', $nisnUnique],
            'jenis_kelamin' => 'nullable|in:L,P',
            'nik' => 'nullable|digits:16',
            'tanggal_lahir' => 'nullable|date_format:Y-m-d',
            'tanggal_diterima' => 'nullable|date_format:Y-m-d',
            'tanggal_keluar' => 'nullable|date_format:Y-m-d',
            'tanggal_ijazah_sma' => 'nullable|date_format:Y-m-d',
            'anak_ke' => 'nullable|integer|between:0,255',
            'lama_belajar_tahun' => 'nullable|integer|between:0,255',
            'jarak_ke_sekolah_km' => 'nullable|numeric|between:0,9999.99',
            'tinggi_badan_cm' => 'nullable|integer|between:0,65535',
            'berat_badan_kg' => 'nullable|integer|between:0,65535',
            'tahun_lulus' => 'nullable|digits:4',
            'catatan' => 'nullable|string|max:16000',
            'kelas' => ['required', 'string', Rule::exists('sekolah_kelas', 'nama')],
            'tahun_ajaran' => ['required', 'string', Rule::in($this->academicYearOptions())],
            'status' => ['nullable', Rule::in(['Aktif', 'Pindah keluar', 'Keluar', 'Lulus'])],
            'foto_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kk_ada' => 'nullable|boolean',
            'akta_ada' => 'nullable|boolean',
            'ijazah_smp_ada' => 'nullable|boolean',
            'surat_pindah_ada' => 'nullable|boolean',
            'berkas' => ['sometimes', 'array'],
            'berkas.kk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.akta' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.ijazah_smp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'berkas.surat_pindah' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        foreach (self::STRING_LIMITS as $column => $maxLength) {
            $rules[$column] = ['nullable', 'string', 'max:'.$maxLength];
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function academicYearOptions(): array
    {
        $years = [];
        for ($year = 2024; $year <= 2049; $year++) {
            $years[] = $year.'/'.($year + 1);
        }

        $years = array_merge($years, SekolahTahunAjaran::orderBy('nama')->pluck('nama')->all());
        $years = array_values(array_unique($years));
        sort($years, SORT_NATURAL);

        return $years;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeDocumentFlags(Request $request, array &$data): void
    {
        foreach (['kk_ada', 'akta_ada', 'ijazah_smp_ada', 'surat_pindah_ada'] as $field) {
            $data[$field] = $request->boolean($field);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storePhoto(Request $request, array &$data, ?string &$fotoPath): void
    {
        $foto = $request->file('foto_file');
        if (! $foto) {
            return;
        }

        $filename = 'foto_'.$data['nisn'].'_'.Str::uuid().'.'.$foto->extension();
        $fotoPath = $foto->storeAs('siswa/foto', $filename, 'local');
        if ($fotoPath === false) {
            throw new RuntimeException('Foto siswa tidak dapat disimpan.');
        }

        $data['foto'] = $fotoPath;
        $data['foto_ada'] = true;
    }

    /**
     * @param  array<int, string>  $uploadedPaths
     * @param  array<int, string>  $replacedPaths
     */
    private function storeAttachments(
        Request $request,
        SiswaBiodata $siswa,
        array &$uploadedPaths,
        array &$replacedPaths
    ): void {
        $documentTypes = [
            'kk' => 'kk_ada',
            'akta' => 'akta_ada',
            'ijazah_smp' => 'ijazah_smp_ada',
            'surat_pindah' => 'surat_pindah_ada',
        ];

        foreach ($documentTypes as $type => $flag) {
            $file = $request->file('berkas.'.$type);
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $filename = $type.'_'.Str::uuid().'.'.$file->extension();
            $path = $file->storeAs('siswa/berkas/'.$siswa->id, $filename, 'local');
            if ($path === false) {
                throw new RuntimeException("Berkas {$type} tidak dapat disimpan.");
            }
            $uploadedPaths[] = $path;

            $attachment = SiswaBerkas::firstOrNew([
                'siswa_id' => $siswa->id,
                'jenis' => $type,
            ]);
            if ($attachment->exists) {
                $replacedPaths[] = $attachment->lokasi;
            }

            $attachment->fill([
                'nama_file' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'lokasi' => $path,
                'tipe_file' => $file->getMimeType(),
                'ukuran_byte' => $file->getSize(),
            ])->save();

            $siswa->forceFill([$flag => true])->save();
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteStoredFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (! Storage::disk('local')->delete($path)) {
                Log::warning('Berkas siswa tidak dapat dihapus dari penyimpanan.', ['path' => $path]);
            }
        }
    }
}
