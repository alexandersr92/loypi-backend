<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerCampaignController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $business = Business::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $campaigns = Campaign::where('business_id', $business->id)
            ->when($request->has('active'), fn($q) => $request->boolean('active') ? $q->where('active', true) : $q->where('active', false))
            ->with(['rewards', 'customerCampaigns.customer'])
            ->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    public function show(Request $request, string $slug, string $id): JsonResponse
    {
        $user = $request->user();
        $business = Business::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)
            ->with([
                'rewards',
                'customerCampaigns' => function($query) {
                    $query->with(['customer', 'stamps', 'rewardUnlocks.reward']);
                },
                'customerStreaks.customer'
            ])
            ->findOrFail($id);

        // Agregar estadísticas
        $campaign->stats = [
            'total_customers' => $campaign->customerCampaigns->count(),
            'total_stamps' => $campaign->customerCampaigns->sum('stamps'),
            'total_redemptions' => $campaign->redeemed_count,
            'active_streaks' => $campaign->customerStreaks->where('current_streak', '>', 0)->count(),
        ];

        return response()->json($campaign);
    }

    public function customers(Request $request, string $slug, string $campaignId): JsonResponse
    {
        $user = $request->user();
        $business = Business::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $campaign = Campaign::where('business_id', $business->id)->findOrFail($campaignId);

        $customerCampaigns = \App\Models\CustomerCampaign::where('campaign_id', $campaign->id)
            ->with([
                'customer',
                'stamps' => function($query) {
                    $query->latest()->limit(5);
                },
                'stamps.staff',
                'rewardUnlocks.reward',
            ])
            ->when($request->has('search'), function($q) use ($request) {
                $search = $request->search;
                $q->whereHas('customer', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('short_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('stamps', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'campaign' => $campaign->makeHidden(['created_at', 'updated_at']),
            'customers' => $customerCampaigns,
        ]);
    }
}

