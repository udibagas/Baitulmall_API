<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Services\AsnafStatisticsService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AsnafController extends Controller
{
    protected $statisticsService;
    protected $scoringService;

    public function __construct(AsnafStatisticsService $statisticsService, ScoringService $scoringService)
    {
        $this->statisticsService = $statisticsService;
        $this->scoringService = $scoringService;
    }

    /**
     * Display a listing of Asnaf
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Asnaf::with('rt:id,kode,rw');

            // Filters
            if ($request->has('kategori')) {
                $query->where('kategori', $request->kategori);
            }

            if ($request->has('rt_id')) {
                $query->where('rt_id', $request->rt_id);
            }

            if ($request->has('tahun')) {
                $query->where('tahun', $request->tahun);
            }

            // Default: only active status if not specified
            if (!$request->has('status')) {
                $query->where('status', 'active');
            }

            $query->orderBy('id', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 50);
            $asnaf = $query->paginate($perPage);

            return response()->json($asnaf);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * Store a newly created Asnaf
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rt_id' => 'required|exists:rts,id',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|in:Fakir,Miskin,Amil,Mualaf,Riqab,Gharim,Fisabilillah,Ibnu Sabil',
            'jumlah_jiwa' => 'required|integer|min:1',
            'tahun' => 'required|integer|min:2020|max:2100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'alamat' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'pendapatan' => 'nullable|numeric',
            'kondisi_rumah' => 'nullable|string',
            // New Detailed Scoring Fields
            'status_rumah_detail' => 'nullable|in:milik_layak,milik_tak_layak,sewa,numpang',
            'kondisi_bangunan' => 'nullable|in:permanen_baik,semi_permanen,tidak_permanen',
            'fasilitas_dasar' => 'nullable|in:layak,salah_satu_terbatas,keduanya_terbatas',
            'custom_criteria' => 'nullable|array',
        ]);

        $asnaf = new Asnaf($validated);

        // Calculate Score
        $scoreResult = $this->scoringService->calculateScore($asnaf);
        $asnaf->score = $scoreResult['total_score'];
        $asnaf->scoring_details = $scoreResult['details'];

        $asnaf->save();
        $this->statisticsService->clearCache($asnaf->tahun);

        return response()->json([
            'message' => 'Asnaf created successfully',
            'data' => $asnaf->load('rt'),
        ], 201);
    }

    /**
     * Display the specified Asnaf
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $asnaf = Asnaf::with('rt', 'distribusi')->findOrFail($id);

        return response()->json($asnaf);
    }

    /**
     * Update the specified Asnaf
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $asnaf = Asnaf::findOrFail($id);

        $validated = $request->validate([
            'rt_id' => 'sometimes|exists:rts,id',
            'nama' => 'sometimes|string|max:255',
            'kategori' => 'sometimes|in:Fakir,Miskin,Amil,Mualaf,Riqab,Gharim,Fisabilillah,Ibnu Sabil',
            'jumlah_jiwa' => 'sometimes|integer|min:1',
            'tahun' => 'sometimes|integer|min:2020|max:2100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'alamat' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'pendapatan' => 'nullable|numeric',
            'kondisi_rumah' => 'nullable|string',
            // New Detailed Scoring Fields
            'status_rumah_detail' => 'nullable|in:milik_layak,milik_tak_layak,sewa,numpang',
            'kondisi_bangunan' => 'nullable|in:permanen_baik,semi_permanen,tidak_permanen',
            'fasilitas_dasar' => 'nullable|in:layak,salah_satu_terbatas,keduanya_terbatas',
            'custom_criteria' => 'nullable|array',
        ]);

        $asnaf->fill($validated);

        // Recalculate Score if relevant fields change
        // Added new fields, removed jumlah_jiwa as it's no longer used for scoring
        if ($request->hasAny(['pendapatan', 'status_rumah_detail', 'kondisi_bangunan', 'fasilitas_dasar', 'custom_criteria'])) {
            $scoreResult = $this->scoringService->calculateScore($asnaf);
            $asnaf->score = $scoreResult['total_score'];
            $asnaf->scoring_details = $scoreResult['details'];
        }

        $asnaf->save();
        $this->statisticsService->clearCache($asnaf->tahun);

        return response()->json([
            'message' => 'Asnaf updated successfully',
            'data' => $asnaf->fresh()->load('rt'),
        ]);
    }

    /**
     * Remove the specified Asnaf (soft delete)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $asnaf = Asnaf::findOrFail($id);
        $asnaf->delete();
        $this->statisticsService->clearCache($asnaf->tahun);

        return response()->json([
            'message' => 'Asnaf deleted successfully',
        ]);
    }

    /**
     * Get statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $tahun = $request->get('tahun', date('Y'));
        $bulan = $request->get('bulan');
        $stats = $this->statisticsService->getOverallSummary($tahun, $bulan);

        return response()->json($stats);
    }

    /**
     * Get map data for visualization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function mapData(Request $request): JsonResponse
    {
        $tahun = $request->get('tahun', date('Y'));
        $kategori = $request->get('kategori');
        $rtId = $request->get('rt_id');

        $mapData = $this->statisticsService->getMapData($tahun, $kategori, $rtId);

        return response()->json([
            'total' => count($mapData),
            'data' => $mapData,
        ]);
    }

    public function recalculateScores()
    {
        $asnafs = Asnaf::where('status', 'active')->get();
        $count = 0;

        foreach ($asnafs as $asnaf) {
            $scoreResult = $this->scoringService->calculateScore($asnaf);
            $asnaf->score = $scoreResult['total_score'];
            $asnaf->scoring_details = $scoreResult['details'];
            $asnaf->save();
            $count++;
        }

        return response()->json(['message' => "Recalculated scores for $count records"]);
    }

    /**
     * Get Mustahik Graduation Index
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function graduationIndex(Request $request): JsonResponse
    {
        $tahun = $request->get('tahun', date('Y'));
        
        $index = $this->statisticsService->getGraduationIndex((int) $tahun);

        return response()->json($index);
    }
}
