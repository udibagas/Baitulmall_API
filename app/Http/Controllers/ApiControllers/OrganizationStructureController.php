<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationStructure;
use Illuminate\Http\Request;

class OrganizationStructureController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => OrganizationStructure::all()
        ]);
    }

    public function eventsIndex()
    {
        return response()->json([
            'success' => true,
            'data' => OrganizationStructure::where('tipe', 'Project')
                ->withCount('assignments')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        // Manually convert empty date strings to null BEFORE validation
        if ($request->has('tanggal_mulai') && $request->input('tanggal_mulai') === '') {
            $request->merge(['tanggal_mulai' => null]);
        }
        if ($request->has('tanggal_selesai') && $request->input('tanggal_selesai') === '') {
            $request->merge(['tanggal_selesai' => null]);
        }

        try {
            $validated = $request->validate([
                'kode_struktur' => 'required|unique:organization_structures,kode_struktur',
                'nama_struktur' => 'required|string',
                'tipe' => 'required|in:Struktural,Kepanitiaan,Project,Event,Panitia',
                'parent_id' => 'nullable|exists:organization_structures,id',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
                'is_active' => 'nullable|boolean'
            ]);

            $structure = OrganizationStructure::create($validated);
            return response()->json(['success' => true, 'data' => $structure], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed for structure creation:', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show($id)
    {
        $structure = OrganizationStructure::find($id);
        if (!$structure) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['success' => true, 'data' => $structure]);
    }

    public function update(Request $request, $id)
    {
        $structure = OrganizationStructure::find($id);
        if (!$structure) return response()->json(['message' => 'Not found'], 404);

        // Convert empty date strings to null
        if ($request->has('tanggal_mulai') && $request->input('tanggal_mulai') === '') {
            $request->merge(['tanggal_mulai' => null]);
        }
        if ($request->has('tanggal_selesai') && $request->input('tanggal_selesai') === '') {
            $request->merge(['tanggal_selesai' => null]);
        }

        try {
            $validated = $request->validate([
                'kode_struktur' => 'sometimes|required|unique:organization_structures,kode_struktur,' . $id,
                'nama_struktur' => 'sometimes|required|string',
                'tipe' => 'sometimes|required|in:Struktural,Kepanitiaan,Project,Event,Panitia',
                'parent_id' => 'nullable|exists:organization_structures,id',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date',
                'is_active' => 'nullable|boolean'
            ]);

            $structure->update($validated);
            return response()->json(['success' => true, 'data' => $structure]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        $structure = OrganizationStructure::find($id);
        if (!$structure) return response()->json(['message' => 'Not found'], 404);

        try {
            // Manually handle restricted foreign key: Delete all assignments first
            \App\Models\Assignment::where('structure_id', $id)->delete();
            
            $structure->delete();
            return response()->json(['success' => true, 'message' => 'Event and its committee members deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Failed to delete structure:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete. ' . $e->getMessage()
            ], 500);
        }
    }
}
