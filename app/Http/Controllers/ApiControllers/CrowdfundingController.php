<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\CrowdfundingCampaign;
use App\Models\CrowdfundingDonation;

class CrowdfundingController extends Controller
{
    // === Campaign Management ===

    public function index()
    {
        // Public should only see active, but for Admin list we might want all
        // For now, let's return all and let frontend filter
        $campaigns = CrowdfundingCampaign::orderByDesc('created_at')->get();
        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'target_amount' => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'image_url' => 'nullable',
            'description' => 'nullable',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(6);

        $campaign = CrowdfundingCampaign::create($validated);
        return response()->json($campaign, 201);
    }

    public function show($id)
    {
        return CrowdfundingCampaign::with('donations')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $campaign = CrowdfundingCampaign::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required',
            'target_amount' => 'sometimes|required|numeric',
        ]);

        $data = $request->all();
        if (isset($data['title'])) {
             $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        $campaign->update($data);
        return response()->json($campaign);
    }

    public function destroy($id)
    {
        CrowdfundingCampaign::destroy($id);
        return response()->json(['message' => 'Campaign deleted']);
    }

    // === Donation ===

    public function donate(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:crowdfunding_campaigns,id',
            'amount' => 'required|numeric|min:1000',
            'donor_name' => 'nullable',
            'donor_phone' => 'nullable',
            'payment_method' => 'nullable',
            'notes' => 'nullable',
        ]);

        // Default donor name
        if (empty($validated['donor_name'])) {
            $validated['donor_name'] = 'Hamba Allah';
        }

        $donation = CrowdfundingDonation::create($validated);

        // Update collected amount in Campaign
        $campaign = CrowdfundingCampaign::find($validated['campaign_id']);
        $campaign->increment('collected_amount', $validated['amount']);

        return response()->json($donation, 201);
    }
}
