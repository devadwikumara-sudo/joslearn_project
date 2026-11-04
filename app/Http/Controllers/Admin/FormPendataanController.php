<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\CatatanAktivitas;

class FormPendataanController extends Controller
{
    public function index(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        return view('admin.form-pendataan', [
            'edit'      => null,
            'taAktif'   => $taAktif,
            'kelasList' => $kelasList,
        ]);
    }

    public function edit(int $id): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        $kelasList = SekolahKelas::pluck('nama')->toArray();
        if (empty($kelasList)) {
            $kelasList = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2'];
        }

        $siswa = SiswaBiodata::with(['riwayatKelas.kelas', 'riwayatKelas.tahunAjaran'])->findOrFail($id);

        return view('admin.form-pendataan', [
            'edit'      => $siswa,
            'taAktif'   => $taAktif,
            'kelasList' => $kelasList,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'nisn'         => 'required|digits:10',
            'kelas'        => 'required|string',
        ]);

        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';

        DB::beginTransaction();
        try {
            $data = $request->except(['_token', 'kelas', 'status', 'foto_file']);

            // Upload foto jika ada
            if ($request->hasFile('foto_file')) {
                $file = $request->file('foto_file');
                $filename = 'foto_' . $request->input('nisn') . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/foto'), $filename);
                $data['foto'] = 'uploads/foto/' . $filename;
                $data['foto_ada'] = 1;
            }

            $siswa = SiswaBiodata::create($data);

            // Cari atau buat Kelas
            $kelasNama = $request->input('kelas');
            $tingkat = 'X';
            if (str_starts_with(strtoupper($kelasNama), 'XII')) $tingkat = 'XII';
            elseif (str_starts_with(strtoupper($kelasNama), 'XI')) $tingkat = 'XI';

            $kelasObj = SekolahKelas::firstOrCreate(
                ['nama' => $kelasNama],
                ['tingkat' => $tingkat]
            );

            // Buat Riwayat Kelas
            SiswaRiwayatKelas::create([
                'siswa_id'        => $siswa->id,
                'tahun_ajaran_id' => $taAktifObj ? $taAktifObj->id : 1,
                'kelas_id'        => $kelasObj->id,
                'status'          => $request->input('status', 'Aktif'),
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran'       => auth()->user()->peran ?? 'admin',
                'aksi'        => 'tambah_siswa',
                'keterangan'  => "Menambahkan siswa baru: {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe'  => 'siswa',
                'objek_id'    => $siswa->id,
                'alamat_ip'   => $request->ip(),
            ]);

            DB::commit();
            return redirect()->route('admin.data-siswa', ['kelas' => $kelasNama])->with('success', 'Data siswa berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan siswa: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $siswa = SiswaBiodata::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'nisn'         => 'required|digits:10',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['_token', '_method', 'kelas', 'status', 'foto_file']);

            if ($request->hasFile('foto_file')) {
                $file = $request->file('foto_file');
                $filename = 'foto_' . $request->input('nisn') . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/foto'), $filename);
                $data['foto'] = 'uploads/foto/' . $filename;
                $data['foto_ada'] = 1;
            }

            $siswa->update($data);

            // Update kelas & status jika diberikan
            if ($request->filled('kelas')) {
                $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                    ?? SekolahTahunAjaran::latest('id')->first();

                $kelasNama = $request->input('kelas');
                $tingkat = 'X';
                if (str_starts_with(strtoupper($kelasNama), 'XII')) $tingkat = 'XII';
                elseif (str_starts_with(strtoupper($kelasNama), 'XI')) $tingkat = 'XI';

                $kelasObj = SekolahKelas::firstOrCreate(
                    ['nama' => $kelasNama],
                    ['tingkat' => $tingkat]
                );

                $rk = SiswaRiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $taAktifObj ? $taAktifObj->id : 1)
                    ->first();

                if ($rk) {
                    $rk->update([
                        'kelas_id' => $kelasObj->id,
                        'status'   => $request->input('status', $rk->status),
                    ]);
                }
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran'       => auth()->user()->peran ?? 'admin',
                'aksi'        => 'edit_siswa',
                'keterangan'  => "Memperbarui biodata siswa: {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe'  => 'siswa',
                'objek_id'    => $siswa->id,
                'alamat_ip'   => $request->ip(),
            ]);

            DB::commit();
            return redirect()->route('admin.data-siswa')->with('success', 'Perubahan biodata siswa berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui siswa: ' . $e->getMessage());
        }
    }
}
