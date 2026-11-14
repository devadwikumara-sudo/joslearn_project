<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SiswaBiodata;
use App\Models\SiswaPelacakanAlumni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PelacakanAlumniController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'integer', 'exists:siswa_biodata,id'],
            'jenis' => ['required', 'string', Rule::in(['Kuliah', 'Bekerja', 'Wirausaha', 'Mencari kerja', 'Lainnya'])],
            'nama_tempat' => ['required', 'string', 'max:150'],
            'tahun' => ['required', 'digits:4'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        $siswa = SiswaBiodata::alumni()->whereKey($validated['siswa_id'])->firstOrFail();

        DB::transaction(function () use ($request, $siswa, $validated): void {
            SiswaPelacakanAlumni::create([
                'siswa_id' => $siswa->id,
                'jenis' => $validated['jenis'],
                'nama_tempat' => $validated['nama_tempat'],
                'tahun' => $validated['tahun'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            $siswa->update([
                'kegiatan_setelah_lulus' => "{$validated['jenis']} · {$validated['nama_tempat']}",
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'lacak_alumni',
                'keterangan' => "Mencatat pelacakan alumni: {$siswa->nama_lengkap} ({$validated['jenis']} di {$validated['nama_tempat']})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return back()->with('success', "Riwayat pelacakan {$siswa->nama_lengkap} berhasil disimpan.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $studentName = DB::transaction(function () use ($request, $id): string {
            $tracking = SiswaPelacakanAlumni::findOrFail($id);
            $siswa = SiswaBiodata::withTrashed()->findOrFail($tracking->siswa_id);
            $activityDescription = "{$tracking->jenis} di {$tracking->nama_tempat}";

            $tracking->delete();

            $latestTracking = SiswaPelacakanAlumni::query()
                ->where('siswa_id', $siswa->id)
                ->latest('id')
                ->first();
            $siswa->update([
                'kegiatan_setelah_lulus' => $latestTracking
                    ? "{$latestTracking->jenis} · {$latestTracking->nama_tempat}"
                    : null,
            ]);

            CatatanAktivitas::create([
                'pengguna_id' => auth()->id(),
                'peran' => auth()->user()->peran ?? 'admin',
                'aksi' => 'hapus_pelacakan_alumni',
                'keterangan' => "Menghapus pelacakan alumni: {$siswa->nama_lengkap} ({$activityDescription})",
                'objek_tipe' => 'siswa',
                'objek_id' => $siswa->id,
                'alamat_ip' => $request->ip(),
            ]);

            return (string) $siswa->nama_lengkap;
        });

        return back()->with('success', "Riwayat pelacakan {$studentName} berhasil dihapus.");
    }
}
