<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRewardController extends Controller
{
    public function unlocked(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $customer = $request->user('customer');

        if (!$customer) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $unlockedRewards = \App\Models\RewardUnlock::whereHas('customerCampaign', function($q) use ($customer, $business) {
            $q->where('customer_id', $customer->id)
              ->whereHas('campaign', fn($q2) => $q2->where('business_id', $business->id));
        })
        ->with(['reward.campaign', 'customerCampaign'])
        ->when($request->has('redeemed'), function($q) use ($request) {
            if ($request->boolean('redeemed')) {
                $q->whereNotNull('redeemed_at');
            } else {
                $q->whereNull('redeemed_at');
            }
        })
        ->latest('unlocked_at')
        ->paginate($request->get('per_page', 15));

        return response()->json($unlockedRewards);
    }
}

