<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRewardRequest;
use App\Http\Requests\Api\V1\UpdateRewardRequest;
use App\Http\Resources\Api\V1\RewardResource;
use App\Models\Campaign;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 🏆 Rewards
 * 
 * CRUD de rewards (premios/templates). Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $this->authorize('viewAny', Reward::class);

        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view rewards.',
            ], 403);
        }

        // Obtener rewards del negocio (templates)
        $rewards = Reward::where('business_id', $user->business->id)
            ->with(['business', 'campaigns'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => RewardResource::collection($rewards),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRewardRequest $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $this->authorize('create', Reward::class);

        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to create rewards.',
            ], 403);
        }

        $validated = $request->validated();
        $validated['business_id'] = $user->business->id;

        // Los rewards ahora son siempre templates (sin campaign_id)
        // Se asocian a campaigns a través de campaign_reward
        
        $reward = Reward::create($validated);
        $reward->load(['business', 'campaigns']);

        return response()->json([
            'success' => true,
            'message' => 'Reward created successfully.',
            'data' => new RewardResource($reward),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $reward = Reward::with(['business', 'campaigns'])->findOrFail($id);

        $this->authorize('view', $reward);

        return response()->json([
            'success' => true,
            'data' => new RewardResource($reward),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRewardRequest $request, string $id): JsonResponse
    {
        $reward = Reward::with(['business', 'campaigns'])->findOrFail($id);

        $this->authorize('update', $reward);

        $validated = $request->validated();

        // Si se está cambiando el tipo, validar que no esté asociado a campaigns con tipo diferente
        if (isset($validated['type']) && $reward->campaigns()->count() > 0) {
            $incompatibleCampaigns = $reward->campaigns()
                ->where('type', '!=', $validated['type'])
                ->get();
            
            if ($incompatibleCampaigns->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot change reward type. This reward is associated with campaigns of different type.",
                ], 422);
            }
        }

        $reward->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reward updated successfully.',
            'data' => new RewardResource($reward->fresh()->load(['business', 'campaigns'])),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $reward = Reward::with(['business', 'campaigns'])->findOrFail($id);

        $this->authorize('delete', $reward);

        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reward deleted successfully.',
        ], 200);
    }
}
