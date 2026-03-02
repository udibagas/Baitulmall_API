<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\SantunanBeneficiary;
use Illuminate\Http\Request;

class SantunanBeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        $query = SantunanBeneficiary::with('rt');

        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        
        if ($request->has('rt_id')) {
            $query->where('rt_id', $request->rt_id);
        }

        if ($request->has('search')) {
            $query->where('nama_lengkap', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->orderBy('nama_lengkap')->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string',
            'jenis' => 'required|in:yatim,dhuafa',
            'rt_id' => 'required|exists:rts,id',
            'alamat' => 'nullable|string',
            'is_active' => 'boolean',
            'keterangan' => 'nullable|string',
            'data_tambahan' => 'nullable|array',
        ]);

        $beneficiary = SantunanBeneficiary::create($validated);
        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $beneficiary->load('rt')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $beneficiary = SantunanBeneficiary::findOrFail($id);
        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|string',
            'jenis' => 'sometimes|in:yatim,dhuafa',
            'rt_id' => 'sometimes|exists:rts,id',
            'alamat' => 'nullable|string',
            'is_active' => 'boolean',
            'keterangan' => 'nullable|string',
            'data_tambahan' => 'nullable|array',
        ]);

        $beneficiary->update($validated);
        return response()->json([
            'message' => 'Data berhasil diupdate',
            'data' => $beneficiary->load('rt')
        ]);
    }

    public function destroy($id)
    {
        $beneficiary = SantunanBeneficiary::findOrFail($id);
        $beneficiary->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
