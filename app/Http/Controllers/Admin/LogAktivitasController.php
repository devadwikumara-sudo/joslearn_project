<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CatatanAktivitas;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function export(Request $request)
    {
        $lf = $request->input('lf', '');
        $lq = trim($request->input('lq', ''));

        $query = CatatanAktivitas::with('pengguna')
            ->forAdminLog($lf, $lq)
            ->latest('id');
        $rows = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="log-aktivitas-'.date('Ymd-His').'.csv"',
        ];

        $callback = function () use ($rows) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['Waktu', 'Pengguna', 'Aktivitas']);

            foreach ($rows as $item) {
                $waktu = $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-';
                $pengguna = $item->pengguna->username ?? $item->peran ?? 'Sistem';
                $aktivitas = $item->keterangan ?: $item->aksi;
                fputcsv($output, [$waktu, $pengguna, $aktivitas]);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
