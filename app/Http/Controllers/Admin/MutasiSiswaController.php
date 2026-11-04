<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatatanAktivitas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MutasiSiswaController extends Controller
{
    public function index(): View
    {
        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktif = $taAktifObj ? $taAktifObj->nama : '2024/2025';
        $taAktifId = $taAktifObj?->id ?? 0;

        // Siswa aktif untuk dropdown
        $siswaAktif = SiswaBiodata::whereHas('riwayatKelas', function ($q) use ($taAktifId) {
            $q->where('tahun_ajaran_id', $taAktifId)
                ->whereNull('deleted_at')
                ->where('status', 'Aktif');
        })->with(['riwayatKelas' => function ($q) use ($taAktifId) {
            $q->where('tahun_ajaran_id', $taAktifId)
                ->whereNull('deleted_at')
                ->with('kelas');
        }])->get();

        // Riwayat mutasi pada TA ini: status pindah/keluar ATAU jenis_registrasi = 'Pindahan'
        $riwayatMutasi = SiswaBiodata::whereHas('riwayatKelas', function ($q) use ($taAktifId) {
            $q->where('tahun_ajaran_id', $taAktifId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['Pindah keluar', 'Keluar']);
        })->orWhere(function ($query) use ($taAktifId) {
            $query->where('jenis_registrasi', 'Pindahan')
                ->whereHas('riwayatKelas', function ($q) use ($taAktifId) {
                    $q->where('tahun_ajaran_id', $taAktifId)
                        ->whereNull('deleted_at');
                });
        })->with(['riwayatKelas' => function ($q) use ($taAktifId) {
            $q->where('tahun_ajaran_id', $taAktifId)
                ->whereNull('deleted_at')
                ->with('kelas');
        }])->latest('updated_at')->get();

        return view('admin.mutasi-siswa', compact(
            'taAktif',
            'siswaAktif',
            'riwayatMutasi'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa_biodata,id',
            'jenis' => 'required|in:Pindah keluar,Keluar',
            'tanggal' => 'required|date',
        ]);

        $taAktifObj = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
            ?? SekolahTahunAjaran::latest('id')->first();
        $taAktifId = $taAktifObj?->id ?? 0;

        DB::beginTransaction();
        try {
            $siswa = SiswaBiodata::findOrFail($request->input('siswa_id'));
            $rk = SiswaRiwayatKelas::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $taAktifId)
                ->whereNull('deleted_at')
                ->first();

            $kelasLama = $rk?->kelas?->nama ?? ($siswa->kelas_diterima ?? '-');

            $siswa->update([
                'tanggal_keluar' => $request->input('tanggal'),
                'alasan_keluar' => $request->input('jenis'),
                'sekolah_tujuan' => $request->input('sekolah_tujuan', '-'),
                'kelas_keluar' => $kelasLama,
            ]);

            if ($rk) {
                $rk->update([
                    'status' => $request->input('jenis'),
                ]);
            }

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'mutasi_siswa',
                'keterangan' => "Mencatat mutasi {$request->input('jenis')} untuk {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.mutasi-siswa')
                ->with('success', "Mutasi {$siswa->nama_lengkap} ({$request->input('jenis')}) berhasil dicatat.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mencatat mutasi: '.$e->getMessage());
        }
    }
}
