<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FormattedTableExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogAktivitasController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $lf = $request->input('lf', '');
        $lq = trim($request->input('lq', ''));

        $query = CatatanAktivitas::with('pengguna')
            ->forAdminLog($lf, $lq)
            ->latest('id');
        $rows = $query->get();

        $exportRows = $rows->map(static function (CatatanAktivitas $item): array {
            $waktu = $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-';
            $pengguna = $item->pengguna->username ?? $item->peran ?? 'Sistem';
            $aktivitas = $item->keterangan ?: $item->aksi;

            return [$waktu, $pengguna, $aktivitas];
        })->all();

        return Excel::download(
            new FormattedTableExport(
                'Log Aktivitas',
                ['Waktu', 'Pengguna', 'Aktivitas'],
                $exportRows,
                ['A' => 22, 'B' => 24, 'C' => 64]
            ),
            'log-aktivitas-'.now()->format('Ymd-His').'.xlsx',
            ExcelWriter::XLSX
        );
    }
}
