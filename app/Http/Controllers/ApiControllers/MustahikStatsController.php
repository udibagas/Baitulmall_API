<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\Muzaki;
use App\Models\Sedekah;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MustahikStatsController extends Controller
{
    /**
     * Get real-time Mustahik statistics summary.
     */
    public function index(Request $request): JsonResponse
    {
        $tahun = $request->get('tahun', date('Y'));
        
        // Use cache with short TTL (10s) as safety. 
        // Observer will clear this cache on data changes for true real-time.
        $cacheKey = "dashboard_stats_summary_{$tahun}";

        $stats = Cache::remember($cacheKey, 10, function () use ($tahun) {
            try {
                // Asnaf Stats
                $asnafQuery = Asnaf::where('tahun', $tahun);
                $totalKK = (clone $asnafQuery)->count();
                $totalJiwa = (clone $asnafQuery)->sum('jumlah_jiwa');

                $categories = $asnafQuery->selectRaw('kategori, COUNT(*) as jumlah, SUM(jumlah_jiwa) as total_jiwa')
                    ->groupBy('kategori')
                    ->get()
                    ->keyBy('kategori');

                // Muzaki Stats
                $totalMuzakiJiwa = Muzaki::where('tahun', $tahun)->sum('jumlah_jiwa');

                // Sedekah Stats
                $totalSedekah = Sedekah::where('tahun', $tahun)->sum('jumlah');

                return [
                    'tahun' => $tahun,
                    'total_kk' => $totalKK,
                    'total_jiwa' => $totalJiwa,
                    'total_muzaki_jiwa' => (int)$totalMuzakiJiwa,
                    'total_sedekah' => (float)$totalSedekah,
                    'fakir' => [
                        'kk' => $categories['Fakir']->jumlah ?? 0,
                        'jiwa' => $categories['Fakir']->total_jiwa ?? 0,
                    ],
                    'miskin' => [
                        'kk' => $categories['Miskin']->jumlah ?? 0,
                        'jiwa' => $categories['Miskin']->total_jiwa ?? 0,
                    ],
                    'amil' => [
                        'kk' => $categories['Amil']->jumlah ?? 0,
                        'jiwa' => $categories['Amil']->total_jiwa ?? 0,
                    ],
                    'last_updated' => now()->toIso8601String(),
                ];
            } catch (\Throwable $e) {
                return [
                    'tahun' => $tahun,
                    'total_kk' => 0,
                    'total_jiwa' => 0,
                    'total_muzaki_jiwa' => 0,
                    'total_sedekah' => 0,
                    'fakir' => ['kk' => 0, 'jiwa' => 0],
                    'miskin' => ['kk' => 0, 'jiwa' => 0],
                    'amil' => ['kk' => 0, 'jiwa' => 0],
                    'error' => $e->getMessage(),
                    'last_updated' => now()->toIso8601String(),
                ];
            }
        });

        return response()->json($stats);
    }
}
