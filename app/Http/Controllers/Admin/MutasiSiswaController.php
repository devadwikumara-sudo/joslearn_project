<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiSiswaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id' => 'required|integer|exists:siswa_biodata,id',
            'jenis' => 'required|in:Pindah keluar,Keluar',
            'tanggal' => 'required|date',
            'sekolah_tujuan' => 'nullable|string|max:255',
            'tahun_ajaran' => 'nullable|integer|exists:sekolah_tahun_ajaran,id',
            'kelas' => 'nullable|string|max:100|exists:sekolah_kelas,nama',
        ]);

        $taAktifObj = isset($validated['tahun_ajaran'])
            ? SekolahTahunAjaran::findOrFail((int) $validated['tahun_ajaran'])
            : SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                ?? SekolahTahunAjaran::latest('id')->first();
        abort_unless($taAktifObj, 422, 'Tahun ajaran tidak ditemukan.');
        $taAktifId = (int) $taAktifObj->id;
        $kelasId = isset($validated['kelas'])
            ? (int) SekolahKelas::query()->where('nama', $validated['kelas'])->value('id')
            : null;

        $siswa = SiswaBiodata::aktif($taAktifId)
            ->whereKey((int) $validated['siswa_id'])
            ->whereHas('riwayatKelas', function (Builder $query) use ($taAktifId, $kelasId): void {
                $query->where('tahun_ajaran_id', $taAktifId)
                    ->where('status', 'Aktif')
                    ->whereNull('deleted_at')
                    ->when($kelasId !== null, fn (Builder $historyQuery): Builder => $historyQuery->where('kelas_id', $kelasId));
            })
            ->first();

        if (! $siswa) {
            return back()
                ->withInput()
                ->withErrors(['siswa_id' => 'Siswa harus aktif pada tahun ajaran dan kelas yang dipilih.']);
        }

        $riwayatKelas = $siswa->riwayatKelas()
            ->where('tahun_ajaran_id', $taAktifId)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->when($kelasId !== null, fn (Builder $query): Builder => $query->where('kelas_id', $kelasId))
            ->firstOrFail();
        $kelasLama = $riwayatKelas->kelas?->nama ?? ($siswa->kelas_diterima ?? '-');

        DB::beginTransaction();
        try {
            $siswa->update([
                'tanggal_keluar' => $validated['tanggal'],
                'alasan_keluar' => $validated['jenis'],
                'sekolah_tujuan' => $validated['sekolah_tujuan'] ?? '-',
                'kelas_keluar' => $kelasLama,
            ]);

            $riwayatKelas->update(['status' => $validated['jenis']]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'mutasi_siswa',
                'keterangan' => "Mencatat mutasi {$validated['jenis']} untuk {$siswa->nama_lengkap} ({$siswa->nisn})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.kenaikan-mutasi', [
                'tab' => 'mutasi',
                'tahun_ajaran' => $taAktifId,
                'kelas' => $kelasLama,
            ])->with('success', "Mutasi {$siswa->nama_lengkap} ({$validated['jenis']}) berhasil dicatat.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mencatat mutasi: '.$e->getMessage());
        }
    }
}
