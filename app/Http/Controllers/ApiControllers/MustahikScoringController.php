<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use Illuminate\Http\Request;

class MustahikScoringController extends Controller
{
    /**
     * Calculate Priority Scores for all active Asnaf
     */
    public function calculate(Request $request)
    {
        $asnafList = Asnaf::with('rt')->where('status', 'active')->get();

        if ($asnafList->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Weights
        $weights = [
            'pendapatan' => 0.40,
            'jumlah_jiwa' => 0.20,
            'kondisi_rumah' => 0.15,
            'kondisi_bangunan' => 0.15,
            'status_rumah' => 0.10,
        ];

        // Ranges for normalization
        $maxIncome = $asnafList->max('pendapatan') ?: 1000000;
        $maxFamily = $asnafList->max('jumlah_jiwa') ?: 1;

        $scoredData = $asnafList->map(function ($item) use ($weights, $maxIncome, $maxFamily) {
            // 1. Income (Benefit type but inverse normalization: less is better/higher priority)
            // Normalized value = (Max - Value) / (Max - Min) or simply Max / (Value + 1)
            // Using: (Max - Value) / Max
            $normIncome = $maxIncome > 0 ? ($maxIncome - $item->pendapatan) / $maxIncome : 1;

            // 2. Family Size (Benefit type: more is higher priority)
            $normFamily = $maxFamily > 0 ? $item->jumlah_jiwa / $maxFamily : 0;

            // 3. House Condition (Categorical mapping)
            $normHouse = $this->mapCategoryToScore($item->kondisi_rumah);

            // 4. Building Condition (Categorical mapping)
            $normBuilding = $this->mapCategoryToScore($item->kondisi_bangunan);

            // 5. Ownership Status (Categorical mapping)
            $normOwnership = $this->mapOwnershipToScore($item->status_rumah_detail);

            // Final Weighted Score (0 to 1)
            $finalScore = (
                ($normIncome * $weights['pendapatan']) +
                ($normFamily * $weights['jumlah_jiwa']) +
                ($normHouse * $weights['kondisi_rumah']) +
                ($normBuilding * $weights['kondisi_bangunan']) +
                ($normOwnership * $weights['status_rumah'])
            );

            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'rt_kode' => $item->rt->kode ?? '??',
                'kategori' => $item->kategori,
                'score' => round($finalScore * 100, 2), // Percentage 0-100
                'metrics' => [
                    'income' => $item->pendapatan,
                    'family' => $item->jumlah_jiwa,
                    'status_rumah' => $item->status_rumah_detail,
                ],
                'ranking_label' => $this->getRankingLabel($finalScore)
            ];
        });

        // Sort by score descending
        $sorted = $scoredData->sortByDesc('score')->values();

        return response()->json([
            'success' => true,
            'data' => $sorted
        ]);
    }

    private function mapCategoryToScore($value)
    {
        $value = strtolower($value);
        if (str_contains($value, 'sangat buruk') || str_contains($value, 'roboh')) return 1.0;
        if (str_contains($value, 'buruk') || str_contains($value, 'rusak')) return 0.8;
        if (str_contains($value, 'cukup') || str_contains($value, 'rata')) return 0.5;
        if (str_contains($value, 'baik')) return 0.2;
        return 0;
    }

    private function mapOwnershipToScore($value)
    {
        $value = strtolower($value);
        if (str_contains($value, 'kontrak') || str_contains($value, 'sewa')) return 1.0;
        if (str_contains($value, 'numpang') || str_contains($value, 'wali')) return 0.8;
        if (str_contains($value, 'milik sendiri')) return 0.2;
        return 0.5;
    }

    private function getRankingLabel($score)
    {
        if ($score >= 0.8) return 'Prioritas Utama';
        if ($score >= 0.6) return 'Prioritas Tinggi';
        if ($score >= 0.4) return 'Prioritas Menengah';
        return 'Monitor';
    }
}
