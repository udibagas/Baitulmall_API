<?php

namespace App\Services;

use App\Models\Asnaf;
use Illuminate\Support\Facades\DB;

class AsnafAnalyticsService
{
    /**
     * System 1: Detect Anomalies / Fraud
     * Flags records with logical contradictions between categories, income, and assets.
     */
    public function detectAnomalies()
    {
        $currentYear = date('Y') > 2026 ? date('Y') : 2026;
        $data = Asnaf::where('tahun', $currentYear)->get();
        $anomalies = [];

        foreach ($data as $asnaf) {
            $flags = [];

            // Case A: High income but Fakir/Miskin
            if (in_array($asnaf->kategori, ['Fakir', 'Miskin']) && $asnaf->pendapatan > 2500000) {
                $flags[] = "Pendapatan tinggi (Rp " . number_format($asnaf->pendapatan, 0, ',', '.') . ") namun terdaftar sebagai " . $asnaf->kategori;
            }

            // Case B: No income but has rich assets
            if ($asnaf->pendapatan <= 0 && $asnaf->status_rumah_detail === 'milik_layak' && $asnaf->kondisi_bangunan === 'permanen_baik') {
                $flags[] = "Pendapatan Rp 0 namun memiliki rumah sendiri (Layak) dengan bangunan (Permanen Baik)";
            }

            // Case C: Lots of dependents but physically capable, low score, yet not categorized as poor
            if ($asnaf->jumlah_jiwa >= 5 && in_array($asnaf->kategori, ['Gharim', 'Ibnu Sabil', 'Fisabilillah']) && $asnaf->pendapatan <= 1000000) {
                $flags[] = "Tanggungan sangat besar (>= 5) pendapatan minim, namun kategori bukan Fakir/Miskin";
            }

            if (!empty($flags)) {
                $anomalies[] = [
                    'id' => $asnaf->id,
                    'nama' => $asnaf->nama,
                    'rt' => $asnaf->rt->kode ?? $asnaf->rt_id,
                    'kategori' => $asnaf->kategori,
                    'flags' => $flags
                ];
            }
        }

        return $anomalies;
    }

    /**
     * System 2: RT Vulnerability Heatmap
     * Aggregates demographic and economic data to find the most vulnerable neighborhoods.
     */
    public function calculateRtHeatmap()
    {
        $currentYear = date('Y') > 2026 ? date('Y') : 2026;
        
        // This query requires RT relation or parsing. Assuming RT is available via rt_id mapping.
        $raw = Asnaf::with('rt')
            ->where('tahun', $currentYear)
            ->whereIn('kategori', ['Fakir', 'Miskin'])
            ->get();

        $heatmap = [];

        foreach ($raw as $asnaf) {
            $rtKode = $asnaf->rt->kode ?? 'Unknown';
            
            if (!isset($heatmap[$rtKode])) {
                $heatmap[$rtKode] = [
                    'rt' => $rtKode,
                    'total_kk_miskin' => 0,
                    'total_jiwa_rentan' => 0,
                    'avg_score' => 0,
                    'score_sum' => 0
                ];
            }

            $heatmap[$rtKode]['total_kk_miskin'] += 1;
            $heatmap[$rtKode]['total_jiwa_rentan'] += $asnaf->jumlah_jiwa;
            $heatmap[$rtKode]['score_sum'] += $asnaf->score;
        }

        // Calculate average and convert to array
        $result = [];
        foreach ($heatmap as $h) {
            $h['avg_score'] = round($h['score_sum'] / max(1, $h['total_kk_miskin']), 1);
            unset($h['score_sum']);
            
            // Calculate a vulnerability index (higher is worse)
            // Vulnerability = (Total Jiwa * 0.6) + (Score * 0.4) 
            $h['vulnerability_index'] = round(($h['total_jiwa_rentan'] * 0.6) + ($h['avg_score'] * 0.4), 1);
            
            $result[] = $h;
        }

        // Sort by most vulnerable
        usort($result, function($a, $b) {
            return $b['vulnerability_index'] <=> $a['vulnerability_index'];
        });

        return $result;
    }

    /**
     * System 3: Had Kifayah (Poverty Line Gap Analysis)
     * Calculates the financial deficit of each family against a local living standard.
     */
    public function calculateHadKifayahGap($baseKifayahPerCapita = 1000000)
    {
        $currentYear = date('Y') > 2026 ? date('Y') : 2026;
        $data = Asnaf::with('rt')
            ->where('tahun', $currentYear)
            ->whereIn('kategori', ['Fakir', 'Miskin', 'Mualaf'])
            ->get();

        $deficits = [];
        $totalDeficit = 0;

        foreach ($data as $asnaf) {
            $familyNeeds = $asnaf->jumlah_jiwa * $baseKifayahPerCapita;
            $income = $asnaf->pendapatan ?? 0;
            
            $gap = $familyNeeds - $income;

            if ($gap > 0) {
                $deficitData = [
                    'id' => $asnaf->id,
                    'nama' => $asnaf->nama,
                    'rt' => $asnaf->rt->kode ?? 'Unknown',
                    'jumlah_jiwa' => $asnaf->jumlah_jiwa,
                    'pendapatan' => $income,
                    'kebutuhan_had_kifayah' => $familyNeeds,
                    'defisit' => $gap,
                    // How severe is the poverty? Percentage of gap vs needs
                    'tingkat_keparahan' => round(($gap / $familyNeeds) * 100, 1) // e.g., 85.5% deficit
                ];

                $deficits[] = $deficitData;
                $totalDeficit += $gap;
            }
        }

        // Sort by highest deficit severity
        usort($deficits, function($a, $b) {
            return $b['tingkat_keparahan'] <=> $a['tingkat_keparahan'];
        });

        return [
            'total_defisit_ekstrem' => $totalDeficit,
            'standard_had_kifayah' => $baseKifayahPerCapita,
            'families_below_line' => count($deficits),
            'top_deficits' => array_slice($deficits, 0, 50) // Top 50 deepest poverty cases
        ];
    }

    /**
     * System 4: Capital Assistance Recommender (Zakat Produktif)
     * Ranks Asnaf based on their potential to succeed with business capital.
     */
    public function recommendProductiveZakat()
    {
        $currentYear = date('Y') > 2026 ? date('Y') : 2026;
        $data = Asnaf::with('rt')
            ->where('tahun', $currentYear)
            ->whereNotIn('kategori', ['Amil', 'Fisabilillah', 'Riqab'])
            ->get();

        $candidates = [];

        foreach ($data as $asnaf) {
            $recommendationScore = 0;
            $reasons = [];

            // 1. Dependency Ratio (Higher points for 3-5 dependents, means they have drive but not overburdened like 8+)
            if ($asnaf->jumlah_jiwa >= 3 && $asnaf->jumlah_jiwa <= 6) {
                $recommendationScore += 30;
                $reasons[] = "Tanggungan keluarga ideal (3-6 jiwa)";
            } elseif ($asnaf->jumlah_jiwa > 6) {
                $recommendationScore += 15; // Still good, but survival might eat capital
                $reasons[] = "Tanggungan besar (>6 jiwa)";
            }

            // 2. Income Base (Needs to have some hustle, completely 0 means they need consumptive aid first)
            if ($asnaf->pendapatan > 500000 && $asnaf->pendapatan < 2000000) {
                $recommendationScore += 40;
                $reasons[] = "Sudah memiliki aktivitas ekonomi dasar";
            }

            // 3. Housing Condition (Stable housing is better for business)
            if (in_array($asnaf->status_rumah_detail, ['milik_layak', 'milik_tak_layak'])) {
                $recommendationScore += 20;
                $reasons[] = "Memiliki tempat tinggal tetap";
            } else {
                $recommendationScore -= 10;
                $reasons[] = "Hunian nomaden/sewa (risiko tinggi)";
            }

            // 4. Mustahik Score mapping (Moderately poor is the sweepspot. 100 = complete destitution usually age/sickness)
            if ($asnaf->score >= 50 && $asnaf->score <= 80) {
                $recommendationScore += 10;
                $reasons[] = "Skor kelayakan ideal untuk graduasi";
            }

            if ($recommendationScore >= 50) {
                $candidates[] = [
                    'id' => $asnaf->id,
                    'nama' => $asnaf->nama,
                    'rt' => $asnaf->rt->kode ?? 'Unknown',
                    'kategori' => $asnaf->kategori,
                    'potensi_score' => $recommendationScore,
                    'reasons' => $reasons,
                    'pendapatan' => $asnaf->pendapatan,
                    'jumlah_jiwa' => $asnaf->jumlah_jiwa
                ];
            }
        }

        usort($candidates, function($a, $b) {
            return $b['potensi_score'] <=> $a['potensi_score'];
        });

        return array_slice($candidates, 0, 20); // Return top 20 prospects
    }
}
