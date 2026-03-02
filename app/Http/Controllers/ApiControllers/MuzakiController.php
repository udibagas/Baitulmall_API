<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Muzaki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MuzakiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Muzaki::with(['rt', 'creator', 'updater']);

            if ($request->has('tahun')) {
                $query->where('tahun', $request->tahun);
            }

            if ($request->has('bulan')) {
                $query->whereMonth('tanggal_bayar', $request->bulan);
            }

            if ($request->has('rt_id')) {
                $query->where('rt_id', $request->rt_id);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where('nama', 'like', "%{$search}%");
            }

            // Default to latest
            $query->latest('updated_at');

            if ($request->has('nopage')) {
                return response()->json($query->get());
            }

            return response()->json($query->paginate($request->get('per_page', 20)));
        } catch (\Throwable $e) {
            // Log or handle error without crashing
            \Illuminate\Support\Facades\Log::error("Muzaki index error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'rt_id' => 'required|exists:rts,id',
            'jumlah_jiwa' => 'required|integer|min:1',
            'jumlah_beras_kg' => 'required|numeric|min:0',
            'status_bayar' => 'required|in:Lunas,Belum Lunas',
            'tahun' => 'required|integer|digits:4',
            'tanggal_bayar' => 'nullable|date',
        ]);

        // Map frontend status to DB enum
        $statusMap = ['Lunas' => 'lunas', 'Belum Lunas' => 'belum'];
        if (isset($statusMap[$validated['status_bayar']])) {
            $validated['status_bayar'] = $statusMap[$validated['status_bayar']];
        }
        
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        $muzaki = Muzaki::create($validated);
        $this->clearCache($muzaki->tahun);

        return response()->json([
            'message' => 'Muzaki created successfully',
            'data' => $muzaki->load('rt')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $muzaki = Muzaki::with(['rt', 'creator', 'updater'])->findOrFail($id);
        return response()->json(['data' => $muzaki]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $muzaki = Muzaki::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'rt_id' => 'sometimes|required|exists:rts,id',
            'jumlah_jiwa' => 'sometimes|required|integer|min:1',
            'jumlah_beras_kg' => 'sometimes|required|numeric|min:0',
            'status_bayar' => 'sometimes|required|in:Lunas,Belum Lunas',
            'tahun' => 'sometimes|required|integer|digits:4',
            'tanggal_bayar' => 'nullable|date',
        ]);

        // Map frontend status to DB enum
        if (isset($validated['status_bayar'])) {
            $statusMap = ['Lunas' => 'lunas', 'Belum Lunas' => 'belum'];
            if (isset($statusMap[$validated['status_bayar']])) {
                $validated['status_bayar'] = $statusMap[$validated['status_bayar']];
            }
        }

        $validated['updated_by'] = $request->user()?->id;

        $muzaki->update($validated);
        $this->clearCache($muzaki->tahun);
        if (isset($validated['tahun']) && $validated['tahun'] != $muzaki->getOriginal('tahun')) {
            $this->clearCache($muzaki->getOriginal('tahun'));
        }

        return response()->json([
            'message' => 'Muzaki updated successfully',
            'data' => $muzaki->load('rt')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $muzaki = Muzaki::findOrFail($id);
        $muzaki->delete();
        $this->clearCache($muzaki->tahun);

        return response()->json(['message' => 'Muzaki deleted successfully']);
    }

    /**
     * Get aggregate stats for Zakat Fitrah
     */
    public function stats(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $cacheKey = "muzaki_stats_{$tahun}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($tahun) {
            try {
                $totalBeras = Muzaki::where('tahun', $tahun)->sum('jumlah_beras_kg');
                $totalJiwa = Muzaki::where('tahun', $tahun)->sum('jumlah_jiwa');
                $totalMuzaki = Muzaki::where('tahun', $tahun)->count();

                return [
                    'tahun' => $tahun,
                    'total_beras' => (float) $totalBeras,
                    'total_jiwa' => (int) $totalJiwa,
                    'total_muzaki' => $totalMuzaki,
                    '_cached_at' => now()->toDateTimeString()
                ];
            } catch (\Throwable $e) {
                return [
                    'tahun' => $tahun,
                    'total_beras' => 0,
                    'total_jiwa' => 0,
                    'total_muzaki' => 0,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    private function clearCache($tahun)
    {
        \Illuminate\Support\Facades\Cache::forget("muzaki_stats_{$tahun}");
    }
}
