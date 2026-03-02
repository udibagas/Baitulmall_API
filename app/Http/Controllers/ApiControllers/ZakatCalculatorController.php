<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use App\Models\ZakatCalculationHistory;
use App\Services\GoldPriceService;
use App\Services\ZakatCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZakatCalculatorController extends Controller
{
    protected $goldService;
    protected $calcService;

    public function __construct(GoldPriceService $goldService, ZakatCalculatorService $calcService)
    {
        $this->goldService = $goldService;
        $this->calcService = $calcService;
    }

    /**
     * Get current gold price reference.
     */
    public function getPrice()
    {
        $price = $this->goldService->getCurrentPrice();
        return response()->json([
            'success' => true,
            'data' => $price
        ]);
    }

    /**
     * Manually update gold price.
     */
    public function updatePrice(Request $request)
    {
        $request->validate([
            'price_per_gram' => 'required|numeric|min:0'
        ]);

        $price = $this->goldService->setManualPrice($request->price_per_gram);

        return response()->json([
            'success' => true,
            'message' => 'Harga emas berhasil diperbarui',
            'data' => $price
        ]);
    }

    /**
     * Calculate Zakat (Preview/Dry Run).
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'zakat_type' => 'required|in:Maal,Profesi,Perdagangan',
            'data' => 'required|array'
        ]);

        $goldPrice = $this->goldService->getCurrentPrice();
        $result = $this->calcService->calculate(
            $request->zakat_type,
            $request->data,
            $goldPrice->price_per_gram
        );

        return response()->json([
            'success' => true,
            'gold_price' => $goldPrice,
            'calculation' => $result
        ]);
    }

    /**
     * Save calculation result to history.
     */
    public function save(Request $request)
    {
        $request->validate([
            'muzaki_id' => 'required|exists:muzaki,id',
            'zakat_type' => 'required|in:Maal,Profesi,Perdagangan',
            'result' => 'required|array'
        ]);

        $data = $request->result;

        $history = ZakatCalculationHistory::create([
            'muzaki_id' => $request->muzaki_id,
            'zakat_type' => $request->zakat_type,
            'total_assets' => $data['total_assets'],
            'deductible_debt' => $data['deductible_debt'],
            'nisab_threshold' => $data['nisab_threshold'],
            'zakat_rates_percent' => 2.50, // Standard
            'calculated_amount' => $data['calculated_amount'],
            'is_payable' => $data['is_payable'],
            'haul_met' => $request->input('haul_met', true),
            'calculation_date' => now(),
            'details' => $data['details'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perhitungan zakat berhasil disimpan',
            'data' => $history
        ]);
    }

    /**
     * Get Calculation History for a Muzaki.
     */
    public function history($muzakiId)
    {
        $history = ZakatCalculationHistory::where('muzaki_id', $muzakiId)
            ->orderBy('calculation_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
    /**
     * Export Calculation History to PDF.
     */
    public function exportPdf($muzakiId)
    {
        $history = ZakatCalculationHistory::where('muzaki_id', $muzakiId)
            ->with('muzaki')
            ->orderBy('calculation_date', 'desc')
            ->get();

        if ($history->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Belum ada riwayat perhitungan'], 404);
        }

        $muzaki = $history->first()->muzaki;
        
        // Use Barryvdh\DomPDF\Facade\Pdf or similar if installed, or just return view for now to test logic
        // Assuming standard Laravel View return for PDF generation libraries like snappypdf or dompdf
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.zakat_calculation_history', compact('history', 'muzaki'));
        
        return $pdf->download('Riwayat_Zakat_' . $muzaki->nama . '.pdf');
    }
}
