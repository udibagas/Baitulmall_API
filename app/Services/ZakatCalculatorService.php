<?php

namespace App\Services;

use App\Models\ZakatCalculationHistory;

class ZakatCalculatorService
{
    protected $nisabGram = 85; 

    /**
     * Calculate Zakat based on type.
     * 
     * @param string $type ('Maal', 'Profesi', 'Perdagangan')
     * @param array $data Input data
     * @param float $goldPricePerGram Current Gold Price
     */
    public function calculate($type, array $data, $goldPricePerGram)
    {
        $nisabThreshold = $this->nisabGram * $goldPricePerGram;
        
        switch ($type) {
            case 'Maal':
                return $this->calculateMaal($data, $nisabThreshold);
            case 'Perdagangan':
                return $this->calculatePerdagangan($data, $nisabThreshold);
            case 'Profesi':
                return $this->calculateProfesi($data, $nisabThreshold, $goldPricePerGram); 
                // Note: Some scholars use 85g gold, some use 520kg rice for Profesi.
                // We will stick to 85g gold for consistency unless asked (or 653kg Gabah).
                // BAZNAS often uses 85g Emas equivalent for annual income.
            default:
                throw new \InvalidArgumentException("Unknown Zakat Type: $type");
        }
    }

    protected function calculateMaal($data, $nisab)
    {
        $harta = $data['total_harta'] ?? 0;
        $hutang = $data['hutang'] ?? 0;
        $cleanAssets = $harta - $hutang;

        $isPayable = $cleanAssets >= $nisab;
        $amount = $isPayable ? ($cleanAssets * 0.025) : 0;

        return [
            'total_assets' => $harta,
            'deductible_debt' => $hutang,
            'clean_assets' => $cleanAssets,
            'nisab_threshold' => $nisab,
            'is_payable' => $isPayable,
            'calculated_amount' => $amount
        ];
    }

    protected function calculatePerdagangan($data, $nisab)
    {
        $modal = $data['modal_diputar'] ?? 0;
        $keuntungan = $data['keuntungan'] ?? 0;
        $piutang = $data['piutang_lancar'] ?? 0;
        $hutang = $data['hutang_jatuh_tempo'] ?? 0;

        $totalAssets = $modal + $keuntungan + $piutang;
        $cleanAssets = $totalAssets - $hutang;

        $isPayable = $cleanAssets >= $nisab;
        $amount = $isPayable ? ($cleanAssets * 0.025) : 0;

        return [
            'total_assets' => $totalAssets,
            'deductible_debt' => $hutang,
            'clean_assets' => $cleanAssets,
            'nisab_threshold' => $nisab,
            'is_payable' => $isPayable,
            'calculated_amount' => $amount,
            'details' => [
                'modal' => $modal,
                'keuntungan' => $keuntungan,
                'piutang' => $piutang
            ]
        ];
    }

    protected function calculateProfesi($data, $nisab, $goldPrice)
    {
        // Zakat Profesi: often calculated monthly OR yearly.
        // If Monthly: Nisab is roughly 520kg Rice (~Rp 6-7jt).
        // If Yearly: Nisab is 85g Gold (~Rp 85jt).
        
        // Approach: Convert input period to annual for check against Gold Nisab?
        // Or supporting BAZNAS standard: Nisab 524kg Rice for monthly.
        
        // For simplicity and alignment with User Request (85g Gold rules):
        // We will assume the calculation is for accumulated income (or we normalize).
        // However, if user inputs "Monthly Income", comparing to 85g Gold is unfair (too high).
        
        // Hybrid Approach:
        // Use 85g Gold for Yearly Income.
        // Use 85g Gold / 12 for Monthly Income ~ 7g Gold.
        
        $period = $data['period'] ?? 'monthly';
        $income = $data['income'] ?? 0; // Gaji + Tunjangan
        $bonus = $data['bonus'] ?? 0; 
        $needs = $data['needs'] ?? 0; // Some opinions deduct basic needs
        $debt = $data['debt'] ?? 0;

        $gross = $income + $bonus;
        $net = $gross - $needs - $debt;

        $adjustedNisab = ($period === 'monthly') ? ($nisab / 12) : $nisab;

        $isPayable = $net >= $adjustedNisab;
        $amount = $isPayable ? ($net * 0.025) : 0;
        
        return [
            'total_assets' => $gross,
            'deductible_debt' => $debt + $needs,
            'clean_assets' => $net,
            'nisab_threshold' => $adjustedNisab,
            'is_payable' => $isPayable,
            'calculated_amount' => $amount,
            'details' => [
                'period' => $period,
                'income' => $income,
                'bonus' => $bonus,
                'monthly_needs' => $needs
            ]
        ];
    }
}
