<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AsnafAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AsnafAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AsnafAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get anomaly/fraud detection results
     */
    public function getFraudDetection(): JsonResponse
    {
        try {
            $anomalies = $this->analyticsService->detectAnomalies();
            return response()->json([
                'status' => 'success',
                'data' => $anomalies,
                'total_anomalies' => count($anomalies)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate anomalies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RT Vulnerability Heatmap
     */
    public function getRtHeatmap(): JsonResponse
    {
        try {
            $heatmap = $this->analyticsService->calculateRtHeatmap();
            return response()->json([
                'status' => 'success',
                'data' => $heatmap
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate RT heatmap: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Had Kifayah Gap Analysis
     */
    public function getHadKifayahAnalysis(Request $request): JsonResponse
    {
        try {
            // Allow dynamic base kifayah input, default to 1,000,000
            $base = $request->query('base_kifayah', 1000000);
            $analysis = $this->analyticsService->calculateHadKifayahGap($base);
            
            return response()->json([
                'status' => 'success',
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate Had Kifayah gap: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Productive Zakat Candidates recommendations
     */
    public function getProductiveZakatCandidates(): JsonResponse
    {
        try {
            $candidates = $this->analyticsService->recommendProductiveZakat();
            return response()->json([
                'status' => 'success',
                'data' => $candidates,
                'total_candidates' => count($candidates)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get recommendations: ' . $e->getMessage()
            ], 500);
        }
    }
}
