<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProgressController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $customer = $request->user('customer');

        if (!$customer) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $customerCampaigns = $customer->customerCampaigns()
            ->whereHas('campaign', fn($q) => $q->where('business_id', $business->id))
            ->with(['campaign', 'stamps'])
            ->get();

        $streaks = $customer->customerStreaks()
            ->whereHas('campaign', fn($q) => $q->where('business_id', $business->id))
            ->with('campaign')
            ->get();

        $unlockedRewards = \App\Models\RewardUnlock::whereHas('customerCampaign', function($q) use ($customer, $business) {
            $q->where('customer_id', $customer->id)
              ->whereHas('campaign', fn($q2) => $q2->where('business_id', $business->id));
        })
        ->with(['reward', 'customerCampaign.campaign'])
        ->get();

        return response()->json([
            'customer_campaigns' => $customerCampaigns,
            'streaks' => $streaks,
            'unlocked_rewards' => $unlockedRewards,
        ]);
    }
}

