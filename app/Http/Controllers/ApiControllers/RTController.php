<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\RT;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RTController extends Controller
{
    public function index(): JsonResponse
    {
        $rts = RT::all();
        return response()->json($rts);
    }

    public function show(int $id): JsonResponse
    {
        $rt = RT::findOrFail($id);
        return response()->json($rt);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:2|unique:rts',
            'rw' => 'required|string|max:2',
            'ketua' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $rt = RT::create($validated);
        return response()->json($rt, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rt = RT::findOrFail($id);
        $rt->update($request->all());
        return response()->json($rt);
    }

    public function destroy(int $id): JsonResponse
    {
        RT::destroy($id);
        return response()->json(['message' => 'RT deleted successfully']);
    }

    public function getAsnaf(int $id): JsonResponse
    {
        $rt = RT::with('asnaf')->findOrFail($id);
        return response()->json($rt->asnaf);
    }
}
