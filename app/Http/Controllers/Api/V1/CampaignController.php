<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCampaignRequest;
use App\Http\Requests\Api\V1\UpdateCampaignRequest;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignReward;
use App\Models\Reward;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\CustomFieldValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 🎯 Campaigns
 * 
 * CRUD de campaigns (campañas). Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view campaigns.',
            ], 403);
        }

        $campaigns = Campaign::where('business_id', $user->business->id)
            ->with(['business', 'rewards'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => CampaignResource::collection($campaigns),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $this->authorize('create', Campaign::class);

        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to create campaigns.',
            ], 403);
        }

        $validated = $request->validated();
        $rewardIds = $validated['reward_ids'] ?? [];
        $rewards = $validated['rewards'] ?? [];
        $customFieldIds = $validated['custom_field_ids'] ?? [];
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['rewards'], $validated['reward_ids'], $validated['custom_field_ids'], $validated['custom_fields']);

        $validated['business_id'] = $user->business->id;
        $validated['redeemed_count'] = 0;
        $validated['active'] = $validated['active'] ?? true;

        DB::beginTransaction();
        try {
            $campaign = Campaign::create($validated);

            // Si se proporcionaron reward_ids, asociar rewards existentes
            if (!empty($rewardIds)) {
                $pivotDataArray = $validated['reward_pivot_data'] ?? [];
                
                foreach ($rewardIds as $index => $rewardId) {
                    $reward = Reward::findOrFail($rewardId);
                    
                    // Validar que el tipo coincida
                    if ($reward->type !== $campaign->type) {
                        throw new \Exception("Reward type ({$reward->type}) does not match campaign type ({$campaign->type}).");
                    }
                    
                    // Validar que el reward pertenezca al business
                    if ($reward->business_id !== $user->business->id) {
                        throw new \Exception("Reward does not belong to your business.");
                    }
                    
                    // Datos del pivot (pueden venir en reward_pivot_data[index])
                    $pivot = $pivotDataArray[$index] ?? [];
                    
                    // Si es punch, validar que solo haya uno
                    if ($campaign->type === 'punch' && $index > 0) {
                        throw new \Exception("Campaigns of type 'punch' can only have one reward.");
                    }
                    
                    $campaign->rewards()->attach($rewardId, [
                        'threshold_int' => $pivot['threshold_int'] ?? 1,
                        'per_customer_limit' => $pivot['per_customer_limit'] ?? null,
                        'global_limit' => $pivot['global_limit'] ?? null,
                        'redeemed_count' => 0,
                        'active' => $pivot['active'] ?? true,
                        'sort_order' => $pivot['sort_order'] ?? $index,
                    ]);
                }
            } else {
                // Crear nuevos rewards y asociarlos
                foreach ($rewards as $index => $rewardData) {
                    // Validar que el tipo coincida con la campaign
                    if (isset($rewardData['type']) && $rewardData['type'] !== $campaign->type) {
                        throw new \Exception("Reward type ({$rewardData['type']}) does not match campaign type ({$campaign->type}).");
                    }
                    
                    // Si es punch, validar que solo haya uno
                    if ($campaign->type === 'punch' && $index > 0) {
                        throw new \Exception("Campaigns of type 'punch' can only have one reward.");
                    }
                    
                    // Extraer datos del pivot
                    $pivotData = [
                        'threshold_int' => $rewardData['threshold_int'] ?? 1,
                        'per_customer_limit' => $rewardData['per_customer_limit'] ?? null,
                        'global_limit' => $rewardData['global_limit'] ?? null,
                        'redeemed_count' => 0,
                        'active' => $rewardData['active'] ?? true,
                        'sort_order' => $rewardData['sort_order'] ?? $index,
                    ];
                    
                    // Remover datos del pivot del reward
                    unset($rewardData['threshold_int'], $rewardData['per_customer_limit'], 
                          $rewardData['global_limit'], $rewardData['active'], $rewardData['sort_order']);
                    
                    // Crear el reward
                    $rewardData['business_id'] = $user->business->id;
                    $rewardData['type'] = $campaign->type; // Asegurar que el tipo coincida
                    $reward = Reward::create($rewardData);
                    
                    // Asociar a la campaign con datos del pivot
                    $campaign->rewards()->attach($reward->id, $pivotData);
                }
            }

            // Manejar custom fields
            if (!empty($customFieldIds)) {
                // Asociar custom fields existentes
                foreach ($customFieldIds as $index => $fieldId) {
                    $field = CustomField::findOrFail($fieldId);
                    
                    // Validar que el field pertenezca al business
                    if ($field->business_id !== $user->business->id) {
                        throw new \Exception("Custom field does not belong to your business.");
                    }
                    
                    $campaign->customFields()->attach($fieldId, [
                        'sort_order' => $index,
                        'required_override' => null,
                    ]);
                }
            } elseif (!empty($customFields)) {
                // Crear nuevos custom fields y asociarlos
                foreach ($customFields as $index => $fieldData) {
                    $options = $fieldData['options'] ?? [];
                    $validations = $fieldData['validations'] ?? [];
                    unset($fieldData['options'], $fieldData['validations']);
                    
                    // Crear el custom field
                    $fieldData['business_id'] = $user->business->id;
                    $fieldData['required'] = $fieldData['required'] ?? false;
                    $fieldData['active'] = $fieldData['active'] ?? true;
                    $field = CustomField::create($fieldData);
                    
                    // Crear opciones si es tipo select
                    if ($field->type === 'select' && !empty($options)) {
                        foreach ($options as $optIndex => $option) {
                            CustomFieldOption::create([
                                'custom_field_id' => $field->id,
                                'value' => $option['value'],
                                'label' => $option['label'],
                                'sort_order' => $option['sort_order'] ?? $optIndex,
                            ]);
                        }
                    }
                    
                    // Crear validaciones si se proporcionaron
                    if (!empty($validations)) {
                        foreach ($validations as $validation) {
                            CustomFieldValidation::create([
                                'custom_field_id' => $field->id,
                                'operator' => $validation['operator'],
                                'value_string' => $validation['value_string'] ?? null,
                                'value_number' => $validation['value_number'] ?? null,
                                'value_date' => $validation['value_date'] ?? null,
                                'message' => $validation['message'] ?? null,
                            ]);
                        }
                    }
                    
                    // Asociar a la campaign
                    $campaign->customFields()->attach($field->id, [
                        'sort_order' => $index,
                        'required_override' => null,
                    ]);
                }
            }

            DB::commit();

            $campaign->load(['business', 'rewards', 'customFields.options', 'customFields.validations']);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully.',
                'data' => new CampaignResource($campaign),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::with(['business', 'rewards'])->findOrFail($id);

        $this->authorize('view', $campaign);

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 200);
    }

    /**
     * Obtener campaign por código
     * 
     * Obtiene una campaign completa usando su código único de 4 caracteres.
     * Este endpoint es público y se usa para que los customers puedan acceder
     * a una campaign usando su código único.
     * 
     * Incluye información del negocio, rewards asociados y custom fields.
     * 
     * @unauthenticated
     */
    public function getByCode(Request $request, string $code): JsonResponse
    {
        $campaign = Campaign::where('code', strtoupper($code))
            ->with(['business', 'rewards', 'customFields.options', 'customFields.validations'])
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampaignRequest $request, string $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('update', $campaign);

        $validated = $request->validated();

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully.',
            'data' => new CampaignResource($campaign->fresh()->load(['business', 'rewards'])),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully.',
        ], 200);
    }
}
