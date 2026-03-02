<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Muzaki;
use App\Services\WhatsAppService;
use App\Services\ReceiptService;

class ZakatFitrahController extends Controller
{
    protected $whatsAppService;
    protected $receiptService;

    public function __construct(WhatsAppService $whatsAppService, ReceiptService $receiptService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->receiptService = $receiptService;
    }
    public function summary(Request $request, $routeTahun = null)
    {
        $tahun = $routeTahun ?? $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan');
        $cacheKey = "zakat_fitrah_summary_{$tahun}_" . ($bulan ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($tahun, $bulan) {
            $query = Muzaki::where('tahun', $tahun);
            if ($bulan) {
                $query->whereMonth('tanggal_bayar', $bulan);
            }
            
            $totalBeras = (float)(clone $query)->sum('jumlah_beras_kg');
            $totalJiwa = (int)(clone $query)->sum('jumlah_jiwa');
            $totalUang = (float)(clone $query)->sum('jumlah_uang'); 

            return [
                'success' => true,
                'data' => [
                    'total_penerimaan_beras' => $totalBeras,
                    'total_penerimaan_uang' => $totalUang,
                    'total_jiwa' => $totalJiwa,
                    '_cached_at' => now()->toDateTimeString()
                ]
            ];
        });
    }

    private function clearFitrahCache($tahun)
    {
        Cache::forget("zakat_fitrah_summary_{$tahun}_all");
        Cache::forget("zakat_fitrah_stats_{$tahun}");
        // Also clear monthly if needed, or just flush if small enough
    }

    public function stats(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan');
        
        $cacheKey = $bulan ? "zakat_fitrah_stats_{$tahun}_{$bulan}" : "zakat_fitrah_stats_{$tahun}";

        // Cache stats for 10 minutes
        $stats = Cache::remember($cacheKey, 600, function () use ($tahun, $bulan) {
            $query = Muzaki::where('tahun', $tahun);
            if ($bulan) {
                $query->whereMonth('tanggal_bayar', $bulan);
            }

            $totalJiwa = (clone $query)->sum('jumlah_jiwa');
            $totalBeras = (clone $query)->sum('jumlah_beras_kg');
            $totalUang = (clone $query)->sum('jumlah_uang');

            return [
                'total_jiwa' => $totalJiwa,
                'total_beras' => $totalBeras,
                'total_uang' => $totalUang
            ];
        });

        return response()->json($stats);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'rt_id' => 'required|exists:rts,id',
            'jumlah_jiwa' => 'required|integer',
            'jumlah_beras_kg' => 'required|numeric',
            'jumlah_uang' => 'nullable|numeric',
            'no_hp' => 'nullable|string',
            'status_bayar' => 'required|string',
            'tahun' => 'required|string'
        ]);

        $zakat = Muzaki::create($validated); // Assuming Muzaki model is used for ZakatFitrah data
        
        // Generate Receipt PDF
        $receiptPath = $this->receiptService->generateReceipt('fitrah', $zakat);
        $receiptUrl = $this->receiptService->getReceiptUrl($receiptPath);

        // Update with receipt path
        $zakat->update(['receipt_path' => $receiptPath]);

        // Send WhatsApp Notification
        if ($zakat->no_hp) {
            $message = "Terima kasih Bpk/Ibu *" . ($zakat->nama) . "*\n\n";
            $message .= "Kami telah menerima pembayaran *Zakat Fitrah* Anda untuk " . $zakat->jumlah_jiwa . " jiwa.\n";
            if ($zakat->jumlah_uang > 0) $message .= "Nominal: *Rp " . number_format($zakat->jumlah_uang, 0, ',', '.') . "*\n";
            if ($zakat->jumlah_beras_kg > 0) $message .= "Beras: *" . number_format($zakat->jumlah_beras_kg, 1, ',', '.') . " Kg*\n";
            $message .= "\nSemoga Allah mensucikan jiwa dan harta Anda. Aamiin.\n\n";
            $message .= "📄 *Download Kwitansi Digital:* \n" . $receiptUrl . "\n\n";
            $message .= "_Baitulmal Masjid_";

            $this->whatsAppService->send($zakat->no_hp, $message);
        }
        
        // Clear cache
        $this->clearFitrahCache($zakat->tahun);

        return response()->json(['message' => 'Data recorded', 'data' => $zakat], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $zakat = Muzaki::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string',
            'rt_id' => 'required|exists:rts,id',
            'jumlah_jiwa' => 'required|integer',
            'jumlah_beras_kg' => 'required|numeric',
            'jumlah_uang' => 'nullable|numeric',
            'status_bayar' => 'required|string',
            'tahun' => 'required|string'
        ]);

        $zakat->update($validated);
        
        // Clear cache for both old and new year just in case
        $this->clearFitrahCache($zakat->tahun);

        return response()->json(['message' => 'Data updated', 'data' => $zakat->load('rt')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $zakat = Muzaki::findOrFail($id);
        $tahun = $zakat->tahun;
        $zakat->delete();
        
        // Clear cache
        $this->clearFitrahCache($tahun);

        return response()->json(['message' => 'Data deleted']);
    }
}
