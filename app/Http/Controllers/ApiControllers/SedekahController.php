<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Sedekah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Services\WhatsAppService;

class SedekahController extends Controller
{
    protected $whatsAppService;
    protected $receiptService;

    public function __construct(WhatsAppService $whatsAppService, \App\Services\ReceiptService $receiptService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->receiptService = $receiptService;
    }

    public function index(Request $request)
    {
        try {
            $query = Sedekah::with(['rt', 'amil']);

            if ($request->has('jenis')) {
                $query->where('jenis', $request->jenis);
            }

            if ($request->has('tahun')) {
                $query->where('tahun', $request->tahun);
            }

            if ($request->has('bulan')) {
                $query->whereMonth('tanggal', $request->bulan);
            }

            if ($request->has('rt_id')) {
                $query->where('rt_id', $request->rt_id);
            }

            if ($request->has('rt_kode')) {
                $query->whereHas('rt', function($q) use ($request) {
                    $q->where('kode', $request->rt_kode);
                });
            }

            $query->latest('tanggal');

            return response()->json($query->paginate($request->get('per_page', 1000)));
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'data' => []
            ], 200);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amil_id' => 'nullable|exists:asnaf,id',
            'rt_id' => 'nullable|exists:rts,id',
            'rt_kode' => 'nullable|string|max:2', // Added for robustness
            'jumlah' => 'required|numeric',
            'jenis' => 'required|in:penerimaan,penyaluran',
            'tujuan' => 'required|string',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'nama_donatur' => 'nullable|string',
            'no_hp_donatur' => 'nullable|string',
        ]);

        // Resolve RT ID if kode is provided but ID is missing
        if (empty($validated['rt_id']) && !empty($validated['rt_kode'])) {
            $rt = \App\Models\RT::where('kode', $validated['rt_kode'])->first();
            if ($rt) {
                $validated['rt_id'] = $rt->id;
            }
        }

        $sedekah = Sedekah::create($validated);

        // Generate Receipt PDF
        $receiptPath = $this->receiptService->generateReceipt('sedekah', $sedekah);
        $receiptUrl = $this->receiptService->getReceiptUrl($receiptPath);

        // Update with receipt path
        $sedekah->update(['receipt_path' => $receiptPath]);

        // Send WhatsApp Notification
        if ($sedekah->jenis === 'penerimaan' && $sedekah->no_hp_donatur) {
            $message = "Terima kasih Bpk/Ibu *" . ($sedekah->nama_donatur ?? 'Hamba Allah') . "*\n\n";
            $message .= "Kami telah menerima donasi Anda sebesar *Rp " . number_format($sedekah->jumlah, 0, ',', '.') . "*\n";
            $message .= "Semoga Allah membalas kebaikan Anda dengan pahala yang berlipat ganda. Aamiin.\n\n";
            $message .= "📄 *Download Kwitansi Digital:* \n" . $receiptUrl . "\n\n";
            $message .= "_Baitulmal Masjid_";

            $this->whatsAppService->send($sedekah->no_hp_donatur, $message);
        }

        $this->clearSedekahCache();

        return response()->json([
            'message' => 'Sedekah recorded successfully',
            'data' => $sedekah->load(['rt', 'amil'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $sedekah = Sedekah::findOrFail($id);
        $validated = $request->validate([
            'amil_id' => 'sometimes|nullable|exists:asnaf,id',
            'rt_id' => 'sometimes|nullable|exists:rts,id',
            'jumlah' => 'sometimes|required|numeric',
            'jenis' => 'sometimes|required|in:penerimaan,penyaluran',
            'tujuan' => 'sometimes|required|string',
            'tanggal' => 'sometimes|required|date',
            'tahun' => 'sometimes|required|integer',
            'nama_donatur' => 'sometimes|nullable|string',
            'no_hp_donatur' => 'sometimes|nullable|string',
        ]);

        $sedekah->update($validated);

        $this->clearSedekahCache();

        return response()->json([
            'message' => 'Sedekah updated successfully',
            'data' => $sedekah->load(['rt', 'amil'])
        ]);
    }

    public function destroy($id)
    {
        $sedekah = Sedekah::findOrFail($id);
        $sedekah->delete();

        $this->clearSedekahCache(); // Clear cache after data change

        return response()->json(['message' => 'Sedekah deleted successfully']);
    }

    public function summary(Request $request)
    {
        try {
            $tahun = $request->get('tahun', date('Y'));
            $bulan = $request->get('bulan');
            $rtId = $request->get('rt_id');
            
            $cacheKey = "sedekah_summary_{$tahun}_" . ($bulan ?? 'all') . "_" . ($rtId ?? 'all');

            $summaryData = Cache::remember($cacheKey, 900, function () use ($request, $tahun, $bulan, $rtId) {
                $query = Sedekah::query();
                
                if ($tahun) $query->where('tahun', $tahun);
                if ($bulan) $query->whereMonth('tanggal', $bulan);
                if ($rtId) $query->where('rt_id', $rtId);

                $penerimaan = (clone $query)->where('jenis', 'penerimaan')->sum('jumlah');
                $penyaluran = (clone $query)->where('jenis', 'penyaluran')->sum('jumlah');
                $count = $query->count();

                // Optimized breakdown by RT using single aggregate query
                $statsByRT = Sedekah::where('jenis', 'penerimaan')
                    ->select(
                        'rt_id',
                        DB::raw('SUM(jumlah) as total_nominal'),
                        DB::raw('COUNT(*) as transaction_count'),
                        DB::raw('MAX(tanggal) as last_txn_date')
                    )
                    ->when($tahun, fn($q) => $q->where('tahun', $tahun))
                    ->when($bulan, fn($q) => $q->whereMonth('tanggal', $bulan))
                    ->groupBy('rt_id')
                    ->get()
                    ->keyBy('rt_id');

                $breakdownByRT = \App\Models\RT::all()->map(function($rt) use ($statsByRT) {
                    $stats = $statsByRT->get($rt->id);
                    return [
                        'rt_id' => $rt->id,
                        'rt_kode' => $rt->kode,
                        'total_nominal' => (float) ($stats->total_nominal ?? 0),
                        'transaction_count' => (int) ($stats->transaction_count ?? 0),
                        'last_transaction' => $stats->last_txn_date ?? null
                    ];
                });

                return [
                    'success' => true,
                    'data' => [
                        'grand_total' => (float) $penerimaan,
                        'total_expense' => (float) $penyaluran,
                        'net_balance' => (float) ($penerimaan - $penyaluran),
                        'total_transaksi' => $count,
                        'breakdown' => $breakdownByRT,
                        '_cached_at' => now()->toDateTimeString()
                    ]
                ];
            });

            return response()->json($summaryData);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function clearSedekahCache()
    {
        // Targeted cache clearing for better performance
        \Illuminate\Support\Facades\Cache::forget('public_stats_aggregation_v2');
        \Illuminate\Support\Facades\Cache::forget('public_live_stats');
        
        // We could also loop through years, but since summary is year-specific
        // we might want to clear specific years if known. For now, this ensures 
        // the most visible pages (Public & Dashboard) are refreshed.
        \Illuminate\Support\Facades\Log::info("Sedekah/Public Cache Cleared");
    }
}
