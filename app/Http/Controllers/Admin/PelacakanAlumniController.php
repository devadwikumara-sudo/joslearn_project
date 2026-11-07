<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\SiswaBiodata;
use App\Models\SiswaPelacakanAlumni;
use App\Models\Admin\CatatanAktivitas;
use Illuminate\Support\Facades\Auth;

class PelacakanAlumniController extends Controller
{
    public function index(Request $request): View
    {
        $y = $request->input('lcy', '');

        $allAlumni = SiswaBiodata::graduated()
        ->with('pelacakan')
        ->get();

        // Distinct graduation years
        $ys = $allAlumni->map(function($s) {
            return $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
        })
        ->filter(fn($val) => $val !== '-')
        ->unique()
        ->values()
        ->toArray();
        rsort($ys);

        // Stats calculation
        $tot = $allAlumni->count();
        $tr = 0;
        $cntKuliah = 0;
        $cntBekerja = 0;

        foreach ($allAlumni as $s) {
            $last = $s->pelacakan->last();
            if ($last) {
                $tr++;
                if ($last->jenis === 'Kuliah') $cntKuliah++;
                if ($last->jenis === 'Bekerja') $cntBekerja++;
            }
        }

        // Filtered list
        $filteredAlumni = $allAlumni;
        if ($y !== '') {
            $filteredAlumni = $filteredAlumni->filter(function($s) use ($y) {
                $thn = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                return $thn === $y;
            });
        }

        // Sort by year desc, then name
        $sortedAlumni = $filteredAlumni->sortByDesc(function($s) {
            return $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '0000');
        })->values();

        // Paginate manually
        $perPage = 25;
        $currentPage = (int)$request->input('page', 1);
        $paginatedAlumni = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedAlumni->forPage($currentPage, $perPage),
            $sortedAlumni->count(),
            $perPage,
            $currentPage,
            ['path' => route('admin.pelacakan-alumni'), 'query' => $request->query()]
        );

        return view('admin.pelacakan-alumni', compact(
            'tot',
            'tr',
            'cntKuliah',
            'cntBekerja',
            'ys',
            'y',
            'paginatedAlumni'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'    => 'required|exists:siswa_biodata,id',
            'jenis'       => 'required|string',
            'nama_tempat' => 'required|string|max:150',
            'tahun'       => 'required|digits:4',
            'keterangan'  => 'nullable|string|max:255',
        ]);

        $pelacakan = SiswaPelacakanAlumni::create($validated);

        // Update siswa_biodata kegiatan_setelah_lulus with the latest tracking info
        $siswa = SiswaBiodata::findOrFail($validated['siswa_id']);
        $siswa->kegiatan_setelah_lulus = "{$validated['jenis']} · {$validated['nama_tempat']}";
        $siswa->save();

        // Log Aktivitas
        CatatanAktivitas::create([
            'pengguna_id' => Auth::id(),
            'peran'       => Auth::user()->peran ?? 'admin',
            'aksi'        => 'lacak_alumni',
            'keterangan'  => "Mencatat pelacakan alumni: {$siswa->nama_lengkap} ({$validated['jenis']} di {$validated['nama_tempat']})",
            'objek_tipe'  => 'siswa',
            'objek_id'    => $siswa->id,
            'alamat_ip'   => $request->ip(),
        ]);

        return redirect()->back()->with('success', "Riwayat pelacakan {$siswa->nama_lengkap} berhasil disimpan.");
    }

    public function destroy($id)
    {
        $pelacakan = SiswaPelacakanAlumni::findOrFail($id);
        $siswaId = $pelacakan->siswa_id;
        $pelacakan->delete();

        // Sync kegiatan_setelah_lulus with remaining latest entry
        $siswa = SiswaBiodata::find($siswaId);
        if ($siswa) {
            $latest = SiswaPelacakanAlumni::where('siswa_id', $siswaId)->latest('id')->first();
            $siswa->kegiatan_setelah_lulus = $latest ? "{$latest->jenis} · {$latest->nama_tempat}" : null;
            $siswa->save();
        }

        return redirect()->back()->with('success', 'Riwayat pelacakan berhasil dihapus.');
    }
}
