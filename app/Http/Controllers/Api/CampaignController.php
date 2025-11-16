<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaigns = Campaign::where('business_id', $business->id)
            ->when($request->has('active'), fn($q) => $q->where('active', $request->boolean('active')))
            ->with(['rewards', 'customerCampaigns'])
            ->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'limit' => 'nullable|integer|min:0',
            'reward_json' => 'nullable|array',
            'required_stamps' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'cover_image' => 'nullable|string',
            'cover_color' => 'nullable|string',
            'logo_url' => 'nullable|string',
        ]);

        $campaign = Campaign::create([
            ...$validated,
            'business_id' => $business->id,
            'redeemed_count' => 0,
            'active' => $validated['active'] ?? true,
        ]);

        return response()->json($campaign->load('rewards'), 201);
    }

    public function show(string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)
            ->with(['rewards', 'customerCampaigns', 'customFields'])
            ->findOrFail($id);

        return response()->json($campaign);
    }

    public function update(Request $request, string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'limit' => 'nullable|integer|min:0',
            'reward_json' => 'nullable|array',
            'required_stamps' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'cover_image' => 'nullable|string',
            'cover_color' => 'nullable|string',
            'logo_url' => 'nullable|string',
        ]);

        $campaign->update($validated);

        return response()->json($campaign->load('rewards'));
    }

    public function destroy(string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($id);
        $campaign->delete();

        return response()->json(['message' => 'Campaña eliminada']);
    }
}

