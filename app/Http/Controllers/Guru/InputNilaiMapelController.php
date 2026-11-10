<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\SekolahKelas;
use App\Models\SekolahMapel;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use App\Services\AksesGuru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InputNilaiMapelController extends Controller
{
    public function index(Request $request, AksesGuru $aksesGuru): View
    {
        $pengguna = $request->user();
        $kelasList = $aksesGuru->kelasDiajar($pengguna);
        $kelasTerpilih = null;
        $mapelList = collect();
        $mapelTerpilih = null;
        $siswa = collect();
        $semester = $request->query('semester', 'Ganjil');

        abort_unless(in_array($semester, ['Ganjil', 'Genap'], true), 422, 'Semester tidak valid.');

        if ($request->filled('kelas')) {
            $kelasTerpilih = $kelasList->firstWhere('nama', $request->query('kelas'));
            abort_unless($kelasTerpilih, 403, 'Anda tidak mengajar di kelas yang dipilih.');

            $mapelList = $aksesGuru->mapelDiajar($pengguna, $kelasTerpilih);
            $mapelId = $request->query('mapel_id');
            if ($mapelId !== null && $mapelId !== '') {
                $mapelTerpilih = $mapelList->firstWhere('id', (int) $mapelId);
                abort_unless($mapelTerpilih, 403, 'Anda tidak mengajar mapel yang dipilih di kelas ini.');
            } elseif ($mapelList->count() === 1) {
                $mapelTerpilih = $mapelList->first();
            }

            $tahunAjaranId = SekolahTahunAjaran::query()
                ->where('sedang_berjalan', true)
                ->value('id');

            if ($tahunAjaranId !== null && $mapelTerpilih !== null) {
                $siswa = SiswaRiwayatKelas::query()
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->where('kelas_id', $kelasTerpilih->id)
                    ->where('status', 'Aktif')
                    ->whereHas('siswa')
                    ->with([
                        'siswa',
                        'nilai' => fn ($query) => $query
                            ->where('semester', $semester)
                            ->where('mapel_id', $mapelTerpilih->id),
                    ])
                    ->get()
                    ->sortBy(fn (SiswaRiwayatKelas $riwayat): string => $riwayat->siswa->nama_lengkap)
                    ->values();
            }
        }

        return view('guru.input-nilai-mapel', compact(
            'kelasList',
            'kelasTerpilih',
            'mapelList',
            'mapelTerpilih',
            'semester',
            'siswa'
        ));
    }

    public function store(Request $request, AksesGuru $aksesGuru): RedirectResponse
    {
        $data = $request->validate([
            'kelas' => ['required', 'string', 'exists:sekolah_kelas,nama'],
            'mapel_id' => ['required', 'integer', 'exists:sekolah_mapel,id'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $pengguna = $request->user();
        $kelas = SekolahKelas::query()->where('nama', $data['kelas'])->firstOrFail();
        $mapel = SekolahMapel::query()->findOrFail($data['mapel_id']);

        abort_unless(
            $aksesGuru->bolehIsiNilai($pengguna, (int) $kelas->id, (int) $mapel->id),
            403,
            "Anda bukan guru mapel {$mapel->nama} di kelas {$kelas->nama}."
        );

        $tahunAjaranId = SekolahTahunAjaran::query()
            ->where('sedang_berjalan', true)
            ->value('id');
        abort_unless($tahunAjaranId !== null, 403, 'Tidak ada tahun ajaran berjalan.');

        $riwayatSiswa = SiswaRiwayatKelas::query()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('kelas_id', $kelas->id)
            ->where('status', 'Aktif')
            ->whereHas('siswa')
            ->get()
            ->keyBy('id');
        $nilaiInput = $data['nilai'] ?? [];
        $riwayatTidakValid = array_diff(array_map('strval', array_keys($nilaiInput)), $riwayatSiswa->keys()->map('strval')->all());
        abort_if($riwayatTidakValid !== [], 422, 'Daftar siswa tidak sesuai dengan kelas aktif yang dipilih.');

        DB::transaction(function () use ($nilaiInput, $riwayatSiswa, $data, $kelas, $mapel, $pengguna, $request): void {
            $jumlahDisimpan = 0;
            foreach ($nilaiInput as $riwayatId => $nilai) {
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                SiswaNilai::query()->updateOrCreate(
                    [
                        'riwayat_kelas_id' => $riwayatSiswa->get($riwayatId)->id,
                        'semester' => $data['semester'],
                        'mapel_id' => $mapel->id,
                    ],
                    ['nilai_angka' => $nilai]
                );
                $jumlahDisimpan++;
            }

            CatatanAktivitas::create([
                'pengguna_id' => $pengguna->id,
                'peran' => $pengguna->peran,
                'aksi' => 'input_nilai_mapel',
                'keterangan' => "Menyimpan {$jumlahDisimpan} nilai {$mapel->nama} kelas {$kelas->nama} semester {$data['semester']}.",
                'objek_tipe' => 'kelas',
                'objek_id' => $kelas->id,
                'alamat_ip' => $request->ip(),
            ]);
        });

        return redirect()->route('guru.input-nilai-mapel', [
            'kelas' => $kelas->nama,
            'mapel_id' => $mapel->id,
            'semester' => $data['semester'],
        ])->with('success', 'Nilai berhasil disimpan.');
    }
}
