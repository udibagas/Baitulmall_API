<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\ImpactStory;
use App\Models\ZakatFitrah;
use App\Models\ZakatMall;
use App\Models\Sedekah;
use App\Models\SantunanDonation;
use App\Models\Santunan;
use App\Models\Asnaf;
use App\Models\RT;
use App\Models\Distribusi;
use App\Models\CrowdfundingDonation;
use App\Models\Muzaki;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    /**
     * Get aggregated transparency statistics for public consumption
     */
    public function statistics()
    {
        try {
            $currentYear = date('Y');

            // 1. Zakat Fitrah Aggregation (Optimized)
            try {
                $zakatFitrah = DB::table('muzaki')->where('tahun', $currentYear)
                    ->select(
                        DB::raw('SUM(CAST(jumlah_beras_kg AS NUMERIC)) as total_beras'),
                        DB::raw('SUM(jumlah_jiwa) as total_jiwa'),
                        DB::raw('COUNT(id) as total_muzaki')
                    )->first();
            } catch (\Throwable $e) {
                $zakatFitrah = (object) ['total_beras' => 0, 'total_jiwa' => 0, 'total_muzaki' => 0];
            }

            // 2. Simple Aggregations with Null Coalescing
            $zakatMal = (float) DB::table('zakat_malls')->whereYear('tanggal', $currentYear)->sum('jumlah');
            $sedekah = (float) DB::table('sedekah')->whereYear('tanggal', $currentYear)->where('jenis', 'penerimaan')->sum('jumlah');
            $santunan = (float) DB::table('santunan_donations')->whereYear('tanggal', $currentYear)->sum('jumlah');

            // 3. Distribution Aggregations
            $fitrahDistributed = (float) DB::table('distribusi')->where('tahun', $currentYear)->sum('jumlah_kg');
            $sedekahDistributed = (float) DB::table('sedekah')->whereYear('tanggal', $currentYear)->where('jenis', 'penyaluran')->sum('jumlah');
            $malDistributed = (float) DB::table('santunan')->where('tahun', $currentYear)->sum('besaran');
            
            // Bypass SoftDeletes by using DB::table for Asnaf
            $totalMustahikJiwa = 0;
            try {
                $totalMustahikJiwa = (int) DB::table('asnaf')->where('tahun', $currentYear)->where('status', 'active')->sum('jumlah_jiwa');
            } catch (\Throwable $e) {}

            // 4. RT Impact Aggregation - Raw DB
            $rtImpact = collect(DB::select("SELECT id, kode FROM rts"))
                ->map(function($rt) use ($currentYear) {
                    $jiwa = (int) DB::table('asnaf')->where('rt_id', $rt->id)->where('tahun', $currentYear)->sum('jumlah_jiwa');
                    $fitrah = (float) DB::table('distribusi')
                        ->join('asnaf', 'distribusi.asnaf_id', '=', 'asnaf.id')
                        ->where('asnaf.rt_id', $rt->id)
                        ->where('distribusi.tahun', $currentYear)
                        ->sum('distribusi.jumlah_kg');
                    $cash = (float) DB::table('santunan')
                        ->where('rt_id', $rt->id)
                        ->where('tahun', $currentYear)
                        ->sum('besaran');

                    return [
                        'rt' => 'RT ' . $rt->kode,
                        'fitrah' => $fitrah,
                        'cash' => $cash,
                        'jiwa' => $jiwa
                    ];
                });

            // 5. Monthly Trends
            $lastYear = $currentYear - 1;
            
            $zakatMalTrends = DB::table('zakat_malls')->where('tanggal', '>=', "$lastYear-01-01")
                ->select('tanggal', 'jumlah')
                ->get()
                ->groupBy(function($item) {
                    try { return \Carbon\Carbon::parse($item->tanggal)->format('Y-n'); } catch (\Exception $e) { return 'unknown'; }
                })
                ->map(fn($group) => $group->sum('jumlah'));

            $sedekahTrends = DB::table('sedekah')->where('tanggal', '>=', "$lastYear-01-01")
                ->where('jenis', 'penerimaan')
                ->select('tanggal', 'jumlah')
                ->get()
                ->groupBy(function($item) {
                    try { return \Carbon\Carbon::parse($item->tanggal)->format('Y-n'); } catch (\Exception $e) { return 'unknown'; }
                })
                ->map(fn($group) => $group->sum('jumlah'));

            $trends = collect(range(1, 12))->map(function($month) use ($currentYear, $lastYear, $zakatMalTrends, $sedekahTrends) {
                $curKey = "{$currentYear}-{$month}";
                $lastKey = "{$lastYear}-{$month}";
                return [
                    'month' => date('M', mktime(0, 0, 0, $month, 1)),
                    'current' => (float) (($zakatMalTrends->get($curKey) ?? 0) + ($sedekahTrends->get($curKey) ?? 0)),
                    'last' => (float) (($zakatMalTrends->get($lastKey) ?? 0) + ($sedekahTrends->get($lastKey) ?? 0))
                ];
            });

            // 6. Recent Activities
            $recentZakat = DB::table('muzaki')->latest()->limit(3)->get()->map(fn($z) => [
                'nama' => $this->maskName($z->nama), 'tipe' => 'Zakat Fitrah', 'nominal' => $z->jumlah_beras_kg . ' KG', 'tanggal' => (string)$z->created_at
            ]);
            $recentZakatMal = DB::table('zakat_malls')->latest('id')->limit(3)->get()->map(fn($zm) => [
                'nama' => $this->maskName($zm->nama_muzaki), 'tipe' => 'Zakat Mal', 'nominal' => (float) $zm->jumlah, 'tanggal' => (string)$zm->tanggal
            ]);
            $recentSedekah = DB::table('sedekah')->where('jenis', 'penerimaan')->latest('id')->limit(3)->get()->map(fn($s) => [
                'nama' => $this->maskName($s->nama_donatur), 'tipe' => 'Sedekah/Infaq', 'nominal' => (float) $s->jumlah, 'tanggal' => (string)$s->tanggal
            ]);

            $recent = $recentZakat->concat($recentZakatMal)->concat($recentSedekah)->sortByDesc('tanggal')->values()->take(10);

            return response()->json([
                'success' => true,
                'v' => '9.9.9',
                'current_year' => $currentYear,
                'stats' => [
                    'zakat' => [
                        'beras' => (float) ($zakatFitrah->total_beras ?? 0),
                        'jiwa' => (int) ($zakatFitrah->total_jiwa ?? 0),
                        'muzaki' => (int) ($zakatFitrah->total_muzaki ?? 0),
                        'mustahik_jiwa' => $totalMustahikJiwa,
                    ],
                    'zakat_mal' => $zakatMal,
                    'sedekah' => $sedekah,
                    'santunan' => $santunan,
                    'grand_total_cash' => $zakatMal + $sedekah + $santunan,
                    'distributed' => [
                        'fitrah_beras' => $fitrahDistributed,
                        'sedekah_infaq' => $sedekahDistributed,
                        'zakat_mal' => $malDistributed,
                    ],
                    'analytics' => [
                        'rt_impact' => $rtImpact,
                        'asnaf_breakdown' => DB::table('asnaf')->where('tahun', $currentYear)
                            ->select('kategori', DB::raw('count(*) as count'), DB::raw('sum(jumlah_jiwa) as jiwa'))
                            ->groupBy('kategori')->get(),
                        'trends' => $trends,
                        'targets' => [
                            'zakat_mal' => ['goal' => 50000000, 'current' => $zakatMal],
                            'sedekah' => ['goal' => 25000000, 'current' => $sedekah],
                            'beras' => ['goal' => 2000, 'current' => (float) ($zakatFitrah->total_beras ?? 0)],
                        ]
                    ]
                ],
                'recent_activity' => $recent
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 200);
        }
    }

    /**
     * Get published community impact stories
     */
    public function stories()
    {
        // Absolute database-agnostic filtering to avoid SQL type mismatch
        $stories = \App\Models\ImpactStory::all()
            ->filter(fn($s) => (bool)$s->is_published)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Publicly accessible receipt download
     */
    public function downloadReceipt($type, $id)
    {
        $donation = null;

        switch ($type) {
            case 'fitrah': $donation = Muzaki::find($id); break;
            case 'mall': $donation = ZakatMall::find($id); break;
            case 'sedekah': $donation = Sedekah::find($id); break;
        }

        if (!$donation || !$donation->receipt_path) {
            return abort(404, 'Receipt not found.');
        }

        if (!Storage::disk('public')->exists($donation->receipt_path)) {
            return abort(404, 'Receipt file missing.');
        }

        return Storage::disk('public')->download($donation->receipt_path);
    }

    /**
     * Get real-time aggregated statistics for live counters
     */
    public function liveStats()
    {
        return Cache::remember('public_live_stats', 30, function () {
            $currentYear = date('Y');

            // 1. Zakat Fitrah
            try {
                $zakatFitrah = Muzaki::where('tahun', $currentYear)->select(
                    DB::raw('SUM(jumlah_beras_kg) as total_beras'),
                    DB::raw('COUNT(id) as total_tx')
                )->first();
            } catch (\Throwable $e) { $zakatFitrah = null; }

            // 2. Zakat Mal
            try {
                $zakatMal = ZakatMall::whereYear('tanggal', $currentYear)->select(
                    DB::raw('SUM(jumlah) as total_cash'),
                    DB::raw('COUNT(id) as total_tx')
                )->first();
            } catch (\Throwable $e) { $zakatMal = null; }

            // 3. Sedekah & Infaq
            try {
                $sedekah = Sedekah::whereYear('tanggal', $currentYear)
                    ->where('jenis', 'penerimaan')
                    ->select(
                        DB::raw('SUM(jumlah) as total_cash'),
                        DB::raw('COUNT(id) as total_tx')
                    )->first();
            } catch (\Throwable $e) { $sedekah = null; }

            // 4. Santunan Donations
            try {
                $santunan = SantunanDonation::whereYear('tanggal', $currentYear)->select(
                    DB::raw('SUM(jumlah) as total_cash'),
                    DB::raw('COUNT(id) as total_tx')
                )->first();
            } catch (\Throwable $e) { $santunan = null; }

            // 5. Crowdfunding
            try {
                $crowdfunding = CrowdfundingDonation::whereYear('created_at', $currentYear)->select(
                    DB::raw('SUM(amount) as total_cash'),
                    DB::raw('COUNT(id) as total_tx')
                )->first();
            } catch (\Throwable $e) { $crowdfunding = null; }

            // Donor count approximation
            try {
                $uniqueDonors = Muzaki::where('tahun', $currentYear)->distinct('nama')->count('nama') +
                                ZakatMall::whereYear('tanggal', $currentYear)->distinct('nama_muzaki')->count('nama_muzaki') +
                                Sedekah::whereYear('tanggal', $currentYear)->where('jenis', 'penerimaan')->distinct('nama_donatur')->count('nama_donatur');
            } catch (\Throwable $e) { $uniqueDonors = 0; }

            $totalTransactions = ($zakatFitrah->total_tx ?? 0) + 
                                ($zakatMal->total_tx ?? 0) + 
                                ($sedekah->total_tx ?? 0) + 
                                ($santunan->total_tx ?? 0) + 
                                ($crowdfunding->total_tx ?? 0);

            $totalCash = ($zakatMal->total_cash ?? 0) + 
                        ($sedekah->total_cash ?? 0) + 
                        ($santunan->total_cash ?? 0) + 
                        ($crowdfunding->total_cash ?? 0);

            return [
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'overall' => [
                        'total_donasi_cash' => $totalCash,
                        'total_donasi_beras' => $zakatFitrah->total_beras ?? 0,
                        'jumlah_donatur' => $uniqueDonors,
                        'jumlah_transaksi' => $totalTransactions,
                    ],
                    'categories' => [
                        'zakat_fitrah' => [
                            'beras' => $zakatFitrah->total_beras ?? 0,
                            'transaksi' => $zakatFitrah->total_tx ?? 0
                        ],
                        'zakat_mal' => [
                            'nominal' => $zakatMal->total_cash ?? 0,
                            'transaksi' => $zakatMal->total_tx ?? 0
                        ],
                        'sedekah' => [
                            'nominal' => $sedekah->total_cash ?? 0,
                            'transaksi' => $sedekah->total_tx ?? 0
                        ],
                        'santunan' => [
                            'nominal' => $santunan->total_cash ?? 0,
                            'transaksi' => $santunan->total_tx ?? 0
                        ],
                        'donasi_tematik' => [
                            'nominal' => $crowdfunding->total_cash ?? 0,
                            'transaksi' => $crowdfunding->total_tx ?? 0
                        ]
                    ]
                ]
            ];
        });
    }

    /**
     * Helper to mask names for privacy
     */
    private function maskName($name)
    {
        if (!$name) return 'Hamba Allah';
        $parts = explode(' ', $name);
        $masked = array_map(function($part) {
            if (strlen($part) <= 2) return $part;
            return substr($part, 0, 1) . str_repeat('*', strlen($part) - 2) . substr($part, -1);
        }, $parts);
        
        return implode(' ', $masked);
    }
}
