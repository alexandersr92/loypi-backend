<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\CustomerCampaign;
use App\Models\Reward;
use App\Models\Staff;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedemptionController extends Controller
{
    public function __construct(
        private RedemptionService $redemptionService
    ) {}

    public function redeem(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $staff = $request->user('staff');

        if (!$staff || $staff->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'customer_campaign_id' => 'required|uuid|exists:customer_campaigns,id',
            'reward_id' => 'required|uuid|exists:rewards,id',
            'meta' => 'nullable|array',
        ]);

        $customerCampaign = CustomerCampaign::findOrFail($validated['customer_campaign_id']);
        $reward = Reward::findOrFail($validated['reward_id']);

        // Verificar que pertenezcan al mismo negocio
        if ($customerCampaign->campaign->business_id !== $business->id ||
            $reward->campaign->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $redemption = $this->redemptionService->redeem(
                $customerCampaign,
                $reward,
                $staff,
                $validated['meta'] ?? []
            );

            return response()->json([
                'redemption' => $redemption->load(['customerCampaign', 'reward', 'staff']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $query = \App\Models\Redemption::query()
            ->whereHas('customerCampaign.campaign', fn($q) => $q->where('business_id', $business->id))
            ->with(['customerCampaign.customer', 'customerCampaign.campaign', 'reward', 'staff']);

        if ($request->has('customer_id')) {
            $query->whereHas('customerCampaign', fn($q) => $q->where('customer_id', $request->customer_id));
        }

        if ($request->has('reward_id')) {
            $query->where('reward_id', $request->reward_id);
        }

        $redemptions = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($redemptions);
    }
}

