<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RewardUnlockResource;
use App\Http\Resources\Api\V1\RewardUnlockResourceCollection;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\CustomerCampaign;
use App\Models\RewardUnlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 🔓 Reward Unlocks
 * 
 * Endpoints de lectura para ver unlocks de premios (solo para owners)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class RewardUnlockController extends Controller
{
    /**
     * Listar unlocks de una campaign (Owner)
     * 
     * Obtiene todos los unlocks de premios de una campaign específica.
     * 
     * @authenticated
     */
    public function campaignUnlocks(Request $request, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view unlocks.',
            ], 403);
        }

        $campaign = Campaign::where('id', $campaignId)
            ->where('business_id', $user->business->id)
            ->firstOrFail();

        $unlocks = RewardUnlock::whereHas('customerCampaign', function ($query) use ($campaignId) {
            $query->where('campaign_id', $campaignId);
        })
        ->with([
            'customerCampaign.customer',
            'customerCampaign.campaign',
            'reward',
            'campaignReward',
            'redemption.staff',
        ])
        ->orderBy('unlocked_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => RewardUnlockResourceCollection::make($unlocks),
        ], 200);
    }

    /**
     * Listar unlocks de un customer (Owner)
     * 
     * Obtiene todos los unlocks de premios de un customer específico.
     * 
     * @authenticated
     */
    public function customerUnlocks(Request $request, string $customerId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view unlocks.',
            ], 403);
        }

        $customer = Customer::where('id', $customerId)
            ->where('business_id', $user->business->id)
            ->firstOrFail();

        $unlocks = RewardUnlock::whereHas('customerCampaign', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })
        ->with([
            'customerCampaign.customer',
            'customerCampaign.campaign',
            'reward',
            'campaignReward',
            'redemption.staff',
        ])
        ->orderBy('unlocked_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => RewardUnlockResourceCollection::make($unlocks),
        ], 200);
    }

    /**
     * Listar unlocks de un customer en una campaign específica (Owner)
     * 
     * Obtiene todos los unlocks de premios de un customer en una campaign específica.
     * 
     * @authenticated
     */
    public function customerCampaignUnlocks(Request $request, string $customerId, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view unlocks.',
            ], 403);
        }

        $customerCampaign = CustomerCampaign::where('customer_id', $customerId)
            ->where('campaign_id', $campaignId)
            ->whereHas('customer', function ($query) use ($user) {
                $query->where('business_id', $user->business->id);
            })
            ->whereHas('campaign', function ($query) use ($user) {
                $query->where('business_id', $user->business->id);
            })
            ->firstOrFail();

        $unlocks = RewardUnlock::where('customer_campaign_id', $customerCampaign->id)
            ->with([
                'customerCampaign.customer',
                'customerCampaign.campaign',
                'reward',
                'campaignReward',
                'redemption.staff',
            ])
            ->orderBy('unlocked_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RewardUnlockResourceCollection::make($unlocks),
        ], 200);
    }

    /**
     * Obtener unlock específico (Owner)
     * 
     * Obtiene la información detallada de un unlock específico.
     * 
     * @authenticated
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view unlocks.',
            ], 403);
        }

        $unlock = RewardUnlock::whereHas('customerCampaign.customer', function ($query) use ($user) {
            $query->where('business_id', $user->business->id);
        })
        ->with([
            'customerCampaign.customer',
            'customerCampaign.campaign',
            'reward',
            'campaignReward',
            'redemption.staff',
        ])
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new RewardUnlockResource($unlock),
        ], 200);
    }
}
