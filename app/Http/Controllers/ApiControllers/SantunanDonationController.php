<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\SantunanDonation;
use Illuminate\Http\Request;

class SantunanDonationController extends Controller
{
    public function index(Request $request)
    {
        $query = SantunanDonation::query();

        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        return response()->json($query->latest('tanggal')->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_donatur' => 'required|string',
            'jumlah' => 'required|numeric',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'keterangan' => 'nullable|string',
        ]);

        $donation = SantunanDonation::create($validated);
        $this->clearCache();

        return response()->json([
            'message' => 'Santunan donation recorded successfully',
            'data' => $donation
        ], 201);
    }

    public function destroy($id)
    {
        $donation = SantunanDonation::findOrFail($id);
        $donation->delete();
        $this->clearCache();
        return response()->json(['message' => 'Donation deleted successfully']);
    }

    private function clearCache()
    {
        \Illuminate\Support\Facades\Cache::forget('public_stats_aggregation_v2');
        \Illuminate\Support\Facades\Cache::forget('public_live_stats');
    }
}
