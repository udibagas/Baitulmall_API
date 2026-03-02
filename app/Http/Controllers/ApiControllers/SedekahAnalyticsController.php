<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Sedekah;
use App\Models\RT;
use App\Models\Muzaki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SedekahAnalyticsController extends Controller
{
    /**
     * Capacity Calculator: How many units can be funded by Current Balance?
     */
    public function capacity(Request $request)
    {
        $unitCost = $request->get('unit_cost', 100000); // Default 100rb per unit

        $totalIncome = Sedekah::where('jenis', 'penerimaan')->sum('jumlah');
        $totalExpense = Sedekah::where('jenis', 'penyaluran')->sum('jumlah');
        $netBalance = $totalIncome - $totalExpense;

        $capacityCount = $unitCost > 0 ? floor($netBalance / $unitCost) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'net_balance' => (float)$netBalance,
                'unit_cost' => (float)$unitCost,
                'capacity_count' => (int)$capacityCount,
            ]
        ]);
    }

    /**
     * Donor Loyalty Analysis: RFM segments
     */
    public function loyalty()
    {
        $loyaltyData = DB::table('sedekah')
            ->select(
                'no_hp_donatur',
                'nama_donatur',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('SUM(jumlah) as total_monetary'),
                DB::raw('MAX(tanggal) as last_donation')
            )
            ->where('jenis', 'penerimaan')
            ->groupBy('no_hp_donatur', 'nama_donatur')
            ->get();

        $transformed = $loyaltyData->map(function ($item) {
            $lastDate = new \DateTime($item->last_donation);
            $now = new \DateTime();
            $diff = $now->diff($lastDate);
            $recency = (int)$diff->format('%a');

            // Simple Segmentation
            $status = 'Aktif';
            if ($recency > 90) {
                $status = 'Pasif';
            } elseif ($item->total_monetary > 1000000 && $item->frequency == 1) {
                $status = 'Potensial';
            } elseif ($item->frequency >= 3) {
                $status = 'Loyal';
            }

            return [
                'name' => $item->nama_donatur ?: 'Anonim',
                'phone' => $item->no_hp_donatur,
                'frequency' => (int)$item->frequency,
                'total_amount' => (float)$item->total_monetary,
                'last_date' => $item->last_donation,
                'recency_days' => $recency,
                'status' => $status
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed
        ]);
    }

    /**
     * Participation Heatmap: RT donation density
     */
    public function participation()
    {
        $rtStats = RT::withCount('muzaki')->get();
        
        $sedekahByRT = DB::table('sedekah')
            ->select('rt_id', DB::raw('SUM(jumlah) as total'))
            ->where('jenis', 'penerimaan')
            ->groupBy('rt_id')
            ->pluck('total', 'rt_id');

        $heatmap = $rtStats->map(function ($rt) use ($sedekahByRT) {
            $totalSedekah = $sedekahByRT[$rt->id] ?? 0;
            $muzakiCount = $rt->muzaki_count ?: 1; // Avoid division by zero
            $ratio = $totalSedekah / $muzakiCount;

            return [
                'rt_id' => $rt->id,
                'rt_kode' => $rt->kode,
                'total_sedekah' => (float)$totalSedekah,
                'muzaki_count' => (int)$rt->muzaki_count,
                'participation_ratio' => (float)$ratio,
                'intensity' => $this->calculateIntensity($ratio)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $heatmap
        ]);
    }

    private function calculateIntensity($ratio)
    {
        if ($ratio > 100000) return 'High';
        if ($ratio > 50000) return 'Medium';
        return 'Low';
    }

    /**
     * Runway Analytics: Predict how long funds will last
     */
    public function runway()
    {
        $totalIncome = Sedekah::where('jenis', 'penerimaan')->sum('jumlah');
        $totalExpense = Sedekah::where('jenis', 'penyaluran')->sum('jumlah');
        $netBalance = $totalIncome - $totalExpense;

        // Get monthly spending for last 6 months
        // Using strftime for SQLite compatibility
        $last6Months = DB::table('sedekah')
            ->select(
                DB::raw("strftime('%Y-%m', tanggal) as month"),
                DB::raw('SUM(jumlah) as total_spent')
            )
            ->where('jenis', 'penyaluran')
            ->where('tanggal', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $avgBurnRate = $last6Months->count() > 0 
            ? $last6Months->avg('total_spent') 
            : 0;

        $runwayMonths = $avgBurnRate > 0 
            ? round($netBalance / $avgBurnRate, 1) 
            : 99; // Infinity or no burn

        // Projections
        $projections = [];
        $tempBalance = $netBalance;
        for ($i = 0; $i <= 6; $i++) {
            $monthLabel = now()->addMonths($i)->format('M y');
            $projections[] = [
                'month' => $monthLabel,
                'balance' => max(0, (float)$tempBalance)
            ];
            $tempBalance -= $avgBurnRate;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_balance' => (float)$netBalance,
                'avg_monthly_spent' => (float)$avgBurnRate,
                'runway_months' => (float)$runwayMonths,
                'status' => $this->getRunwayStatus($runwayMonths),
                'projections' => $projections,
                'history' => $last6Months
            ]
        ]);
    }

    private function getRunwayStatus($months)
    {
        if ($months < 1) return 'CRITICAL';
        if ($months < 3) return 'WARNING';
        return 'SAFE';
    }
}
