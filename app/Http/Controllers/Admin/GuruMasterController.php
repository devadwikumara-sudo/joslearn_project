<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru\GuruBiodata;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruMasterController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'guru');
        abort_unless(is_string($tab) && in_array($tab, ['guru', 'kelas', 'mapel'], true), 404);

        if ($tab === 'guru') {
            $tahunAjaranAktif = SekolahTahunAjaran::where('sedang_berjalan', 1)->first()
                ?? SekolahTahunAjaran::latest('id')->first();
            $tahunAjaranAktifId = $tahunAjaranAktif?->id ?? 0;

            $guruList = GuruBiodata::whereNull('deleted_at')
                ->with([
                    'akun',
                    'waliKelas' => fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranAktifId)->with('kelas'),
                    'mengajar' => fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranAktifId)->with(['mapel', 'kelas']),
                ])
                ->get();

            $totalGuru = $guruList->count();
            $totalGuruMapel = $guruList->filter(fn ($guru) => $guru->mengajar && $guru->mengajar->count() > 0)->count();
            $totalWaliKelas = $guruList->filter(fn ($guru) => $guru->waliKelas && $guru->waliKelas->count() > 0)->count();

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

            return view('admin.guru-master', compact(
                'tab',
                'guruList',
                'totalGuru',
                'totalGuruMapel',
                'totalWaliKelas',
                'kelasList',
                'mapelList'
            ));
        }

        if ($tab === 'kelas') {
            $kelasList = SekolahKelas::orderBy('tingkat')->orderBy('nama')->get();

            return view('admin.guru-master', compact('tab', 'kelasList'));
        }

        $mapelList = SekolahMapel::orderBy('nama')->get();

        return view('admin.guru-master', compact('tab', 'mapelList'));
    }
}
