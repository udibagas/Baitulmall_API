<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Setting::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key_name' => 'required|string',
            'value' => 'nullable|string',
            'type' => 'required|in:string,number,json,boolean',
            'description' => 'nullable|string'
        ]);

        $setting = Setting::updateOrCreate(
            ['key_name' => $validated['key_name']],
            $validated
        );

        return response()->json(['success' => true, 'data' => $setting], 200);
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function update(Request $request, $id)
    {
        $setting = Setting::findOrFail($id);
        
        $validated = $request->validate([
            'key_name' => 'sometimes|required|string|unique:settings,key_name,' . $id,
            'value' => 'nullable|string',
            'type' => 'sometimes|required|in:string,number,json,boolean',
            'description' => 'nullable|string'
        ]);

        $setting->update($validated);
        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();
        return response()->json(['success' => true, 'message' => 'Setting deleted']);
    }
}
