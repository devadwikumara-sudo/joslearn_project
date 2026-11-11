<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Admin\CatatanAktivitas;
use App\Models\Auth\Pengguna;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $lf = $request->input('lf', '');
        $lq = trim($request->input('lq', ''));

        // Distinct user / roles for filter dropdown
        $who2 = CatatanAktivitas::select('peran')
            ->whereNotNull('peran')
            ->distinct()
            ->pluck('peran')
            ->toArray();

        $userList = Pengguna::pluck('username')->toArray();
        $whoList = array_values(array_unique(array_filter(array_merge($who2, $userList))));

        $query = CatatanAktivitas::with('pengguna')->latest('id');

        if ($lf !== '') {
            $query->where(function($q) use ($lf) {
                $q->where('peran', $lf)
                  ->orWhereHas('pengguna', function($pq) use ($lf) {
                      $pq->where('username', $lf);
                  });
            });
        }

        if ($lq !== '') {
            $query->where(function($q) use ($lq) {
                $q->where('aksi', 'like', "%{$lq}%")
                  ->orWhere('keterangan', 'like', "%{$lq}%");
            });
        }

        $totalLogs = CatatanAktivitas::count();
        $logs = $query->paginate(25)->withQueryString();

        return view('admin.log-aktivitas', compact('lf', 'lq', 'whoList', 'totalLogs', 'logs'));
    }

    public function export(Request $request)
    {
        $lf = $request->input('lf', '');
        $lq = trim($request->input('lq', ''));

        $query = CatatanAktivitas::with('pengguna')->latest('id');

        if ($lf !== '') {
            $query->where(function($q) use ($lf) {
                $q->where('peran', $lf)
                  ->orWhereHas('pengguna', function($pq) use ($lf) {
                      $pq->where('username', $lf);
                  });
            });
        }

        if ($lq !== '') {
            $query->where(function($q) use ($lq) {
                $q->where('aksi', 'like', "%{$lq}%")
                  ->orWhere('keterangan', 'like', "%{$lq}%");
            });
        }

        $rows = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="log-aktivitas-' . date('Ymd-His') . '.csv"',
        ];

        $callback = function() use ($rows) {
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
