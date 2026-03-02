<?php

namespace App\Services;

use App\Models\ZakatFitrah;
use App\Models\Distribusi;
use App\Models\Asnaf;

class ZakatCalculationService
{
    /**
     * Calculate total collected zakat for a given year
     *
     * @param int $tahun
     * @return array ['total_kg' => float, 'total_rupiah' => float]
     */
    public function getTotalCollected(int $tahun): array
    {
        $zakat = ZakatFitrah::where('tahun', $tahun)->get();

        return [
            'total_kg' => $zakat->sum('jumlah_kg'),
            'total_rupiah' => $zakat->sum('jumlah_rupiah'),
            'total_jiwa' => $zakat->sum('jumlah_jiwa'),
            'total_transactions' => $zakat->count(),
        ];
    }

    /**
     * Calculate distribution per kategori asnaf
     *
     * @param int $tahun
     * @return array
     */
    public function calculateDistribution(int $tahun): array
    {
        $distributed = Distribusi::where('tahun', $tahun)
            ->where('status', '!=', 'planned')
            ->get();

        $perKategori = [];
        foreach (['Fakir', 'Miskin', 'Fisabilillah', 'Amil'] as $kategori) {
            $filtered = $distributed->where('kategori_asnaf', $kategori);
            $perKategori[$kategori] = [
                'jumlah_kg' => $filtered->sum('jumlah_kg'),
                'jumlah_rupiah' => $filtered->sum('jumlah_rupiah'),
                'jumlah_penerima' => $filtered->count(),
            ];
        }

        return $perKategori;
    }

    /**
     * Get remaining stock (collected - distributed)
     *
     * @param int $tahun
     * @return array ['remaining_kg' => float, 'remaining_rupiah' => float]
     */
    public function getRemainingStock(int $tahun): array
    {
        $collected = $this->getTotalCollected($tahun);
        $distributed = Distribusi::where('tahun', $tahun)
            ->where('status', '!=', 'planned')
            ->get();

        return [
            'remaining_kg' => $collected['total_kg'] - $distributed->sum('jumlah_kg'),
            'remaining_rupiah' => $collected['total_rupiah'] - $distributed->sum('jumlah_rupiah'),
        ];
    }

    /**
     * Validate muzaki data before saving
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function validateMuzaki(array $data): bool
    {
        if ($data['jumlah_jiwa'] <= 0) {
            throw new \Exception('Jumlah jiwa harus lebih dari 0');
        }

        if (isset($data['jumlah_beras_kg']) && $data['jumlah_beras_kg'] <= 0) {
            throw new \Exception('Jumlah beras harus lebih dari 0');
        }

        return true;
    }

    /**
     * Auto-calculate jumlah beras (kg) from jiwa
     *
     * @param int $jiwa
     * @param float $kgPerJiwa Default 2.5 kg per person
     * @return float
     */
    public function calculateBerasFromJiwa(int $jiwa, float $kgPerJiwa = 2.5): float
    {
        return round($jiwa * $kgPerJiwa, 2);
    }

    /**
     * Calculate recommended distribution per Asnaf category
     *
     * @param int $tahun
     * @return array
     */
    public function calculateRecommendedDistribution(int $tahun): array
    {
        $stock = $this->getRemainingStock($tahun);
        $asnafCounts = Asnaf::where('tahun', $tahun)
            ->where('status', 'active')
            ->selectRaw('kategori, COUNT(*) as jumlah, SUM(jumlah_jiwa) as total_jiwa')
            ->groupBy('kategori')
            ->get()
            ->keyBy('kategori');

        // Distribution ratios (configurable)
        $ratios = [
            'Fakir' => 0.35,
            'Miskin' => 0.35,
            'Fisabilillah' => 0.15,
            'Amil' => 0.15,
        ];

        $recommendations = [];
        foreach ($ratios as $kategori => $ratio) {
            $allocation = $stock['remaining_kg'] * $ratio;
            $count = $asnafCounts->get($kategori);
            
            $recommendations[$kategori] = [
                'total_allocation_kg' => round($allocation, 2),
                'jumlah_penerima' => $count ? $count->jumlah : 0,
                'per_penerima_kg' => $count && $count->jumlah > 0 
                    ? round($allocation / $count->jumlah, 2) 
                    : 0,
            ];
        }

        return $recommendations;
    }
}
