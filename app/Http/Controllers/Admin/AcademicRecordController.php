<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Auth\Pengguna;
use App\Models\SiswaBiodata;
use App\Models\SiswaEkskul;
use App\Models\SiswaKehadiran;
use App\Models\SiswaNilai;
use App\Models\SiswaRiwayatKelas;
use App\Services\AcademicRecordAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicRecordController extends Controller
{
    private const FIELDS = [
        'nilai' => ['riwayat_kelas_id', 'semester', 'mapel_id', 'nilai_angka'],
        'kehadiran' => ['riwayat_kelas_id', 'semester', 'sakit', 'izin', 'alpa', 'kokurikuler'],
        'ekskul' => ['riwayat_kelas_id', 'semester', 'nama_kegiatan', 'predikat'],
    ];

    public function store(Request $request, SiswaBiodata $siswa, string $jenis, AcademicRecordAccess $access): RedirectResponse
    {
        $this->assertType($jenis);
        $data = $this->validatedData($request, $jenis);
        $history = $this->historyForStudent($siswa, (int) $data['riwayat_kelas_id']);
        $access->authorize($request->user(), $jenis, $history, $data['mapel_id'] ?? null);
        $model = $this->newRecord($jenis);

        DB::transaction(function () use ($model, $data, $jenis, $request, $siswa): void {
            $model->fill($data);
            $model->save();
            $this->recordActivity($request->user(), $jenis, 'tambah', $model, $siswa->id, null, $model->only(self::FIELDS[$jenis]));
        });

        return back()->with('success', 'Data akademik berhasil ditambahkan.');
    }

    public function update(Request $request, SiswaBiodata $siswa, string $jenis, int $record, AcademicRecordAccess $access): RedirectResponse
    {
        $this->assertType($jenis);
        $model = $this->recordForStudent($jenis, $siswa, $record);
        $oldHistory = $model->riwayatKelas()->firstOrFail();
        $access->authorize($request->user(), $jenis, $oldHistory, $model->getAttribute('mapel_id'));
        $data = $this->validatedData($request, $jenis, $record);
        $newHistory = $this->historyForStudent($siswa, (int) $data['riwayat_kelas_id']);
        $access->authorize($request->user(), $jenis, $newHistory, $data['mapel_id'] ?? null);
        $before = $model->only(self::FIELDS[$jenis]);

        DB::transaction(function () use ($model, $data, $jenis, $request, $siswa, $before): void {
            $model->fill($data);
            $model->save();
            $this->recordActivity($request->user(), $jenis, 'ubah', $model, $siswa->id, $before, $model->only(self::FIELDS[$jenis]));
        });

        return back()->with('success', 'Data akademik berhasil diperbarui.');
    }

    public function destroy(Request $request, SiswaBiodata $siswa, string $jenis, int $record, AcademicRecordAccess $access): RedirectResponse
    {
        $this->assertType($jenis);
        $model = $this->recordForStudent($jenis, $siswa, $record);
        $history = $model->riwayatKelas()->firstOrFail();
        $access->authorize($request->user(), $jenis, $history, $model->getAttribute('mapel_id'));
        $before = $model->only(self::FIELDS[$jenis]);

        DB::transaction(function () use ($model, $jenis, $request, $siswa, $before): void {
            $this->recordActivity($request->user(), $jenis, 'hapus', $model, $siswa->id, $before, null);
            $model->delete();
        });

        return back()->with('success', 'Data akademik berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, string $jenis, ?int $ignoreId = null): array
    {
        $historyId = $request->input('riwayat_kelas_id');
        $semester = $request->input('semester');
        $rules = [
            'riwayat_kelas_id' => ['required', 'integer', 'exists:siswa_riwayat_kelas,id'],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ];

        if ($jenis === 'nilai') {
            $rules += [
                'mapel_id' => ['required', 'integer', 'exists:sekolah_mapel,id'],
                'nilai_angka' => ['required', 'numeric', 'between:0,100'],
            ];
            $unique = Rule::unique('siswa_nilai')
                ->where('riwayat_kelas_id', $historyId)
                ->where('semester', $semester)
                ->where('mapel_id', $request->input('mapel_id'));
            if ($ignoreId !== null) {
                $unique->ignore($ignoreId);
            }
            $rules['mapel_id'][] = $unique;
        } elseif ($jenis === 'kehadiran') {
            $rules += [
                'sakit' => ['required', 'integer', 'min:0', 'max:65535'],
                'izin' => ['required', 'integer', 'min:0', 'max:65535'],
                'alpa' => ['required', 'integer', 'min:0', 'max:65535'],
                'kokurikuler' => ['nullable', 'string'],
            ];
            $unique = Rule::unique('siswa_kehadiran')
                ->where('riwayat_kelas_id', $historyId)
                ->where('semester', $semester);
            if ($ignoreId !== null) {
                $unique->ignore($ignoreId);
            }
            $rules['semester'][] = $unique;
        } else {
            $rules += [
                'nama_kegiatan' => ['required', 'string', 'max:100'],
                'predikat' => ['nullable', 'string', 'max:30'],
            ];
            $unique = Rule::unique('siswa_ekskul')
                ->where('riwayat_kelas_id', $historyId)
                ->where('semester', $semester)
                ->where('nama_kegiatan', $request->input('nama_kegiatan'));
            if ($ignoreId !== null) {
                $unique->ignore($ignoreId);
            }
            $rules['nama_kegiatan'][] = $unique;
        }

        return $request->validate($rules);
    }

    private function assertType(string $jenis): void
    {
        abort_unless(isset(self::FIELDS[$jenis]), 404);
    }

    private function historyForStudent(SiswaBiodata $siswa, int $historyId): SiswaRiwayatKelas
    {
        return SiswaRiwayatKelas::query()
            ->where('siswa_id', $siswa->id)
            ->findOrFail($historyId);
    }

    private function recordForStudent(string $jenis, SiswaBiodata $siswa, int $recordId): Model
    {
        $modelClass = $this->modelClass($jenis);

        return $modelClass::query()
            ->whereKey($recordId)
            ->whereHas('riwayatKelas', fn ($query) => $query->where('siswa_id', $siswa->id))
            ->firstOrFail();
    }

    private function newRecord(string $jenis): Model
    {
        $modelClass = $this->modelClass($jenis);

        return new $modelClass;
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(string $jenis): string
    {
        return match ($jenis) {
            'nilai' => SiswaNilai::class,
            'kehadiran' => SiswaKehadiran::class,
            'ekskul' => SiswaEkskul::class,
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordActivity(
        Pengguna $user,
        string $jenis,
        string $verb,
        Model $model,
        int $siswaId,
        ?array $before,
        ?array $after
    ): void {
        $payload = json_encode(
            ['sebelum' => $before, 'sesudah' => $after],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $chunks = mb_str_split($payload, 350);
        $table = $model->getTable();
        $count = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $part = $count > 1 ? ' (bagian '.($index + 1).'/'.$count.')' : '';
            CatatanAktivitas::create([
                'pengguna_id' => $user->id,
                'peran' => $user->peran,
                'aksi' => $verb.'_'.$jenis,
                'keterangan' => 'Siswa #'.$siswaId.'; baris '.$table.' #'.$model->getKey().$part.': '.$chunk,
                'objek_tipe' => $table,
                'objek_id' => $model->getKey(),
                'alamat_ip' => request()->ip(),
                'created_at' => now(),
            ]);
        }
    }
}
