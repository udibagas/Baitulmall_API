<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\ZakatProduktif;
use App\Models\ZakatProduktifMonitoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZakatProduktifController extends Controller
{
    public function index(Request $request)
    {
        $query = ZakatProduktif::with('asnaf.rt')->withSum('monitoring', 'laba');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asnaf_id' => 'required|exists:asnaf,id',
            'nama_usaha' => 'required|string|max:255',
            'modal_awal' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'nullable|in:aktif,mandiri,gagal'
        ]);

        $zakatProduktif = ZakatProduktif::create($validated);

        return response()->json([
            'message' => 'Zakat Produktif created successfully',
            'data' => $zakatProduktif->load('asnaf.rt')
        ], 201);
    }

    public function show($id)
    {
        $zakatProduktif = ZakatProduktif::with(['asnaf.rt', 'monitoring' => function($q) {
            $q->orderBy('tanggal_laporan', 'desc');
        }])->findOrFail($id);

        return response()->json($zakatProduktif);
    }

    public function update(Request $request, $id)
    {
        $zakatProduktif = ZakatProduktif::findOrFail($id);

        $validated = $request->validate([
            'nama_usaha' => 'sometimes|string|max:255',
            'modal_awal' => 'sometimes|numeric|min:0',
            'tanggal_mulai' => 'sometimes|date',
            'keterangan' => 'nullable|string',
            'status' => 'sometimes|in:aktif,mandiri,gagal'
        ]);

        $zakatProduktif->update($validated);

        return response()->json([
            'message' => 'Zakat Produktif updated successfully',
            'data' => $zakatProduktif->fresh()->load('asnaf.rt')
        ]);
    }

    public function destroy($id)
    {
        $zakatProduktif = ZakatProduktif::findOrFail($id);
        $zakatProduktif->delete();

        return response()->json(['message' => 'Zakat Produktif deleted successfully']);
    }

    // Monitoring Actions
    public function storeMonitoring(Request $request, $id)
    {
        $zakatProduktif = ZakatProduktif::findOrFail($id);

        $validated = $request->validate([
            'tanggal_laporan' => 'required|date',
            'omzet' => 'required|numeric|min:0',
            'laba' => 'required|numeric',
            'catatan' => 'nullable|string'
        ]);

        $monitoring = $zakatProduktif->monitoring()->create($validated);

        return response()->json([
            'message' => 'Monitoring report added successfully',
            'data' => $monitoring
        ], 201);
    }

    public function getSummary()
    {
        $totalZakatMal = \App\Models\ZakatMall::sum('jumlah');
        $totalModalDisalurkan = ZakatProduktif::sum('modal_awal');
        
        $availableBalance = $totalZakatMal - $totalModalDisalurkan;

        $activeProjects = ZakatProduktif::where('status', 'aktif')->count();
        $successfulProjects = ZakatProduktif::where('status', 'mandiri')->count();
        
        $totalLaba = ZakatProduktifMonitoring::sum('laba');

        return response()->json([
            'total_zakat_mal' => (float)$totalZakatMal,
            'total_modal_disalurkan' => (float)$totalModalDisalurkan,
            'saldo_tersedia' => (float)$availableBalance,
            'proyek_aktif' => $activeProjects,
            'mustahik_mandiri' => $successfulProjects,
            'total_laba_kumulatif' => (float)$totalLaba
        ]);
    }
}
