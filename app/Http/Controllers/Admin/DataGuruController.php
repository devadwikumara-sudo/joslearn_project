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
        $request->validate([
            'nama' => 'required|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            $guru = GuruBiodata::create([
                'nama' => $request->input('nama'),
                'nip' => $request->input('nip'),
                'nuptk' => $request->input('nuptk'),
                'nik' => $request->input('nik'),
                'jenis_kelamin' => $request->input('jenis_kelamin', 'L'),
                'tempat_lahir' => $request->input('tempat_lahir'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'pendidikan_terakhir' => $request->input('pendidikan_terakhir'),
                'status_kepegawaian' => $request->input('status_kepegawaian', 'PNS'),
                'mulai_bertugas' => $request->input('mulai_bertugas'),
                'nomor_hp' => $request->input('nomor_hp'),
                'email' => $request->input('email'),
                'alamat' => $request->input('alamat'),
            ]);

            $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                ?? SekolahTahunAjaran::latest('id')->first();
            $taId = $taAktifObj ? $taAktifObj->id : 1;

            // Wali Kelas
            if ($request->has('is_wali') && $request->filled('wali_kelas')) {
                $kObj = SekolahKelas::firstOrCreate(['nama' => $request->input('wali_kelas')], ['tingkat' => 'X']);
                DB::table('guru_wali_kelas')->updateOrInsert(
                    ['tahun_ajaran_id' => $taId, 'kelas_id' => $kObj->id],
                    ['guru_id' => $guru->id, 'updated_at' => now()]
                );
                DB::table('siswa_riwayat_kelas')
                    ->where('tahun_ajaran_id', $taId)
                    ->where('kelas_id', $kObj->id)
                    ->update([
                        'wali_guru_id' => $guru->id,
                        'nama_wali_kelas' => $guru->nama,
                        'nip_wali_kelas' => $guru->nip,
                    ]);
            }

            // Guru Mapel
            if ($request->has('is_mapel') && $request->filled('mapel_ampu') && $request->filled('kelas_ajar')) {
                foreach ($request->input('mapel_ampu') as $mKode) {
                    $mObj = SekolahMapel::where('kode', $mKode)->first();
                    if (! $mObj) {
                        continue;
                    }
                    foreach ($request->input('kelas_ajar') as $kNama) {
                        $kObj = SekolahKelas::firstOrCreate(['nama' => $kNama], ['tingkat' => 'X']);
                        DB::table('guru_mengajar')->updateOrInsert(
                            ['tahun_ajaran_id' => $taId, 'kelas_id' => $kObj->id, 'mapel_id' => $mObj->id],
                            ['guru_id' => $guru->id]
                        );
                    }
                }
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'tambah_guru',
                'keterangan' => "Menambahkan guru baru: {$guru->nama} (NIP: ".($guru->nip ?? '-').')',
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.data-guru')->with('success', "Data guru {$guru->nama} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menyimpan data guru: '.$e->getMessage());
        }
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

        $request->validate([
            'nama' => 'required|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            $guru->update([
                'nama' => $request->input('nama'),
                'nip' => $request->input('nip'),
                'nuptk' => $request->input('nuptk'),
                'nik' => $request->input('nik'),
                'jenis_kelamin' => $request->input('jenis_kelamin', 'L'),
                'tempat_lahir' => $request->input('tempat_lahir'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'pendidikan_terakhir' => $request->input('pendidikan_terakhir'),
                'status_kepegawaian' => $request->input('status_kepegawaian', 'PNS'),
                'mulai_bertugas' => $request->input('mulai_bertugas'),
                'nomor_hp' => $request->input('nomor_hp'),
                'email' => $request->input('email'),
                'alamat' => $request->input('alamat'),
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'edit_guru',
                'keterangan' => "Memperbarui data guru: {$guru->nama}",
                'objek_tipe' => 'guru',
                'objek_id' => $guru->id,
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.data-guru')->with('success', "Data guru {$guru->nama} berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui guru: '.$e->getMessage());
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
