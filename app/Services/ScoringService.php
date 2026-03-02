<?php

namespace App\Services;

use App\Models\Asnaf;

class ScoringService
{
    /**
     * Calculate score for an Asnaf record based on weighted criteria
     * Method: Simple Additive Weighting (SAW) concept
     * Range: 0 - 100
     */
    public function calculateScore(Asnaf $asnaf)
    {
        $score = 0;
        $details = [];

        // CHECK: Custom Scoring for Fisabilillah & Amil
        if (in_array($asnaf->kategori, ['Fisabilillah', 'Amil'])) {
            return $this->calculateCustomScore($asnaf);
        }

        // 1. PENDAPATAN (Max 60 Poin)
        $incomeScore = 0;
        $income = $asnaf->pendapatan;

        if ($income > 3000000) {
            $incomeScore = 0;
        } elseif ($income > 2000000) {
            $incomeScore = 10;
        } elseif ($income > 1500000) {
            $incomeScore = 20;
        } elseif ($income > 1000000) {
            $incomeScore = 30;
        } elseif ($income > 500000) {
            $incomeScore = 45;
        } else {
            // <= 500.000
            $incomeScore = 60;
        }

        $score += $incomeScore;
        $details['income'] = [
            'value' => $income,
            'points' => $incomeScore,
            'max' => 60
        ];

        // 2. TEMPAT TINGGAL (Max 50 Poin)
        $housingScore = 0;

        // A. Status Rumah (Max 30)
        $statusPoints = 0;
        switch ($asnaf->status_rumah_detail) {
            case 'numpang': $statusPoints = 30; break;
            case 'sewa': $statusPoints = 20; break;
            case 'milik_tak_layak': $statusPoints = 10; break;
            case 'milik_layak': default: $statusPoints = 0; break;
        }

        // B. Kondisi Bangunan (Max 10)
        $conditionPoints = 0;
        switch ($asnaf->kondisi_bangunan) {
            case 'tidak_permanen': $conditionPoints = 10; break;
            case 'semi_permanen': $conditionPoints = 5; break;
            case 'permanen_baik': default: $conditionPoints = 0; break;
        }

        // C. Fasilitas Dasar (Max 10)
        $facilityPoints = 0;
        switch ($asnaf->fasilitas_dasar) {
            case 'keduanya_terbatas': $facilityPoints = 10; break;
            case 'salah_satu_terbatas': $facilityPoints = 5; break;
            case 'layak': default: $facilityPoints = 0; break;
        }

        $housingScore = $statusPoints + $conditionPoints + $facilityPoints;
        $score += $housingScore;

        $details['housing'] = [
            'status_points' => $statusPoints,
            'condition_points' => $conditionPoints,
            'facility_points' => $facilityPoints,
            'total_housing' => $housingScore,
            'max' => 50
        ];

        // TOTAL SCORE
        // Max Possible = 110
        // Fakir >= 80
        // Miskin 60-79

        return [
            'total_score' => $score,
            'details' => $details
        ];
    }

    /**
     * Calculate score for Fisabilillah and Amil
     */
    private function calculateCustomScore(Asnaf $asnaf)
    {
        $score = 0;
        $details = [];
        $criteria = $asnaf->custom_criteria ?? [];

        if ($asnaf->kategori === 'Fisabilillah') {
            // 1. Mengajar ngaji (35)
            if (!empty($criteria['mengajar_ngaji'])) {
                $score += 35;
                $details['mengajar_ngaji'] = 35;
            }
            // 2. Mengajar madrasah (35)
            if (!empty($criteria['mengajar_madrasah'])) {
                $score += 35;
                $details['mengajar_madrasah'] = 35;
            }
            // 3. Menjadi imam masjid/mushola (30)
            if (!empty($criteria['imam_masjid'])) {
                $score += 30;
                $details['imam_masjid'] = 30;
            }
        } elseif ($asnaf->kategori === 'Amil') {
            // 1. Menjadi pengurus zakat fitrah (35)
            if (!empty($criteria['pengurus_zakat'])) {
                $score += 35;
                $details['pengurus_zakat'] = 35;
            }
            // 2. Menjadi Pengurus kotak sedekah (35)
            if (!empty($criteria['pengurus_kotak_sedekah'])) {
                $score += 35;
                $details['pengurus_kotak_sedekah'] = 35;
            }
            // 3. Menyalurkan Bantuan sedekah (30)
            if (!empty($criteria['penyalur_bantuan'])) {
                $score += 30;
                $details['penyalur_bantuan'] = 30;
            }
        }

        return [
            'total_score' => $score,
            'details' => $details
        ];
    }
}
