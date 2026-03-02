<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = \App\Models\Asset::orderBy('name')->get();
        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'code' => 'required|unique:assets,code',
            'category' => 'required',
            'condition' => 'required|in:good,damaged,lost',
            'value' => 'numeric',
            'is_lendable' => 'boolean',
        ]);

        $asset = \App\Models\Asset::create($validated);
        return response()->json($asset, 201);
    }

    public function show($id)
    {
        return \App\Models\Asset::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $asset = \App\Models\Asset::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required',
            'code' => 'sometimes|required|unique:assets,code,' . $id,
            'category' => 'sometimes|required',
            'condition' => 'sometimes|required|in:good,damaged,lost',
            'value' => 'numeric',
            'is_lendable' => 'boolean',
        ]);

        $asset->update($validated);
        return response()->json($asset);
    }

    public function destroy($id)
    {
        \App\Models\Asset::destroy($id);
        return response()->json(['message' => 'Asset deleted']);
    }
}
