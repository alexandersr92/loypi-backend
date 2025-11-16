<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request, string $slug, string $campaignId): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);

        $rewards = Reward::where('campaign_id', $campaign->id)
            ->when($request->has('active'), fn($q) => $q->where('active', $request->boolean('active')))
            ->with('rewardUnlocks')
            ->paginate($request->get('per_page', 15));

        return response()->json($rewards);
    }

    public function store(Request $request, string $slug, string $campaignId): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:punch,streak,points',
            'threshold_int' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'per_customer_limit' => 'nullable|integer|min:0',
            'global_limit' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'reward_json' => 'nullable|array',
        ]);

        $reward = Reward::create([
            ...$validated,
            'campaign_id' => $campaign->id,
            'redeemed_count' => 0,
            'active' => $validated['active'] ?? true,
        ]);

        return response()->json($reward, 201);
    }

    public function show(string $slug, string $campaignId, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);
        
        $reward = Reward::where('campaign_id', $campaign->id)
            ->with(['rewardUnlocks', 'redemptions'])
            ->findOrFail($id);

        return response()->json($reward);
    }

    public function update(Request $request, string $slug, string $campaignId, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);
        
        $reward = Reward::where('campaign_id', $campaign->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:punch,streak,points',
            'threshold_int' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'per_customer_limit' => 'nullable|integer|min:0',
            'global_limit' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'reward_json' => 'nullable|array',
        ]);

        $reward->update($validated);

        return response()->json($reward);
    }

    public function destroy(string $slug, string $campaignId, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);
        
        $reward = Reward::where('campaign_id', $campaign->id)->findOrFail($id);
        $reward->delete();

        return response()->json(['message' => 'Premio eliminado']);
    }
}

