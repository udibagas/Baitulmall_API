<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistribusiController extends Controller
{
    public function index(Request $request)
    {
        $query = Distribusi::with(['asnaf.rt']);

        if ($request->has('tahun')) {
            $query->where('tahun', $request->input('tahun'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 50);
        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        try {
            // Handle bulk insert if an array is passed
            $distributions = $request->input('distributions');
            
            if (is_array($distributions)) {
                DB::beginTransaction();
                $created = [];
                foreach ($distributions as $item) {
                    $distribusi = Distribusi::updateOrCreate(
                        [
                            'asnaf_id' => $item['asnaf_id'],
                            'tahun' => $item['tahun'],
                        ],
                        [
                            'kategori_asnaf' => $item['kategori_asnaf'],
                            'jumlah_kg' => collect($item)->get('jumlah_kg', 0),
                            'tanggal' => collect($item)->get('tanggal', now()->toDateString()),
                            'status' => collect($item)->get('status', 'distributed'),
                            'distributed_by' => 'Admin'
                        ]
                    );
                    $created[] = $distribusi;
                }
                DB::commit();
                $this->clearCache();
                return response()->json(['success' => true, 'message' => 'Distribusi berhasil disimpan', 'data' => $created], 201);
            }

            // Single insert fallback (if any)
            $distribusi = Distribusi::create($request->all());
            $this->clearCache();
            return response()->json(['success' => true, 'data' => $distribusi], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Distribusi Store Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan distribusi'], 500);
        }
    }

    public function show($id)
    {
        $distribusi = Distribusi::with('asnaf')->find($id);
        if (!$distribusi) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($distribusi);
    }

    public function update(Request $request, $id)
    {
        $distribusi = Distribusi::find($id);
        if (!$distribusi) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $distribusi->update($request->all());
        $this->clearCache();
        return response()->json(['success' => true, 'data' => $distribusi]);
    }

    public function destroy($id)
    {
        $distribusi = Distribusi::find($id);
        if (!$distribusi) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $distribusi->delete();
        $this->clearCache();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    private function clearCache()
    {
        \Illuminate\Support\Facades\Cache::forget('public_stats_aggregation_v2');
        \Illuminate\Support\Facades\Cache::forget('public_live_stats');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        try {
            Distribusi::whereIn('id', $ids)->delete();
            $this->clearCache();
            return response()->json(['success' => true, 'message' => count($ids) . ' records deleted']);
        } catch (\Exception $e) {
            Log::error('Distribusi Bulk Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete records'], 500);
        }
    }
}
