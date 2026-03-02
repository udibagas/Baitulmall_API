<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssetLoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get active loans first, then history
        // Simple sort for SQLite compatibility
        $loans = \App\Models\AssetLoan::with('asset')->orderByDesc('id')->get();
        return response()->json($loans);
    }

    public function loan(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'borrower_name' => 'required',
            'borrower_phone' => 'required',
            'loan_date' => 'required|date',
            'expected_return_date' => 'required|date|after:loan_date',
            'notes' => 'nullable',
        ]);

        // Check availability
        $asset = \App\Models\Asset::find($validated['asset_id']);
        if (!$asset->is_lendable) {
            return response()->json(['message' => 'Asset is not lendable'], 400);
        }
        if ($asset->condition == 'lost') {
            return response()->json(['message' => 'Asset is lost'], 400);
        }

        // Check if currently borrowed
        $activeLoan = \App\Models\AssetLoan::where('asset_id', $validated['asset_id'])
                                            ->whereIn('status', ['active', 'overdue'])
                                            ->first();
        if ($activeLoan) {
            return response()->json(['message' => 'Asset is currently borrowed'], 400);
        }

        $loan = \App\Models\AssetLoan::create(array_merge($validated, ['status' => 'active']));
        return response()->json($loan, 201);
    }

    public function returnLoan(Request $request, $id)
    {
        $loan = \App\Models\AssetLoan::findOrFail($id);
        
        if ($loan->status == 'returned') {
            return response()->json(['message' => 'Loan already returned'], 400);
        }

        $validated = $request->validate([
            'actual_return_date' => 'required|date',
            'condition' => 'nullable|in:good,damaged,lost', // Update asset condition if needed
            'notes' => 'nullable',
        ]);

        $loan->update([
            'actual_return_date' => $validated['actual_return_date'],
            'status' => 'returned',
            'notes' => $validated['notes'] ? $loan->notes . "\n[Return]: " . $validated['notes'] : $loan->notes
        ]);

        if (isset($validated['condition'])) {
            $loan->asset->update(['condition' => $validated['condition']]);
        }

        return response()->json($loan);
    }
}
