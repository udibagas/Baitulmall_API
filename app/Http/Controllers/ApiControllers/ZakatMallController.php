<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\ZakatMall;
use App\Services\WhatsAppService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ZakatMallController extends Controller
{
    protected $whatsAppService;
    protected $receiptService;

    public function __construct(WhatsAppService $whatsAppService, ReceiptService $receiptService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->receiptService = $receiptService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ZakatMall::with('rt');

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('rt_id')) {
            $query->where('rt_id', $request->rt_id);
        }
        
        // Default sort by latest
        $query->latest('tanggal');

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_muzaki' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'rt_id' => 'required|exists:rts,id',
            'kategori' => 'required|string',
            'jumlah' => 'required|numeric|min:1000',
            'keterangan' => 'nullable|string',
            'tanggal' => 'nullable|date'
        ]);

        $zakatMall = ZakatMall::create($validated);
        $this->clearCache();

        // Generate Receipt PDF
        $receiptPath = $this->receiptService->generateReceipt('mall', $zakatMall);
        $receiptUrl = $this->receiptService->getReceiptUrl($receiptPath);

        // Update with receipt path
        $zakatMall->update(['receipt_path' => $receiptPath]);

        // Send WhatsApp Notification
        if ($zakatMall->no_hp) {
            $message = "Terima kasih Bpk/Ibu *" . ($zakatMall->nama_muzaki ?? 'Hamba Allah') . "*\n\n";
            $message .= "Kami telah menerima pembayaran *Zakat Maal* Anda.\n";
            $message .= "Nominal: *Rp " . number_format($zakatMall->jumlah, 0, ',', '.') . "*\n";
            $message .= "Kategori: *" . $zakatMall->kategori . "*\n\n";
            $message .= "Semoga Allah memberikan keberkahan pada harta yang Anda tunaikan. Aamiin.\n\n";
            $message .= "📄 *Download Kwitansi Digital:* \n" . $receiptUrl . "\n\n";
            $message .= "_Baitulmal Masjid_";

            $this->whatsAppService->send($zakatMall->no_hp, $message);
        }

        return response()->json([
            'message' => 'Zakat Mall recorded successfully',
            'data' => $zakatMall->load('rt')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $zakatMall = ZakatMall::with('rt')->findOrFail($id);
        return response()->json($zakatMall);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $zakatMall = ZakatMall::findOrFail($id);

        $validated = $request->validate([
            'nama_muzaki' => 'nullable|string',
            'rt_id' => 'sometimes|exists:rts,id',
            'kategori' => 'sometimes|string',
            'jumlah' => 'sometimes|numeric|min:1000',
            'keterangan' => 'nullable|string',
            'tanggal' => 'nullable|date'
        ]);

        $zakatMall->update($validated);
        $this->clearCache();

        return response()->json([
            'message' => 'Zakat Mall updated successfully',
            'data' => $zakatMall->fresh()->load('rt')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $zakatMall = ZakatMall::findOrFail($id);
        $zakatMall->delete();
        $this->clearCache();

        return response()->json(['message' => 'Zakat Mall deleted successfully']);
    }

    private function clearCache()
    {
        \Illuminate\Support\Facades\Cache::forget('public_stats_aggregation_v2');
        \Illuminate\Support\Facades\Cache::forget('public_live_stats');
    }
}
