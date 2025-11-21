<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCampaignCustomFieldRequest;
use App\Models\Campaign;
use App\Models\CampaignCustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 🎯 Campaigns - Custom Fields
 * 
 * Gestión de custom fields asociados a campaigns. Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class CampaignCustomFieldController extends Controller
{
    /**
     * Associate custom fields to a campaign.
     * @authenticated
     */
    public function store(StoreCampaignCustomFieldRequest $request, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business.',
            ], 403);
        }

        $campaign = Campaign::where('business_id', $user->business->id)
            ->findOrFail($campaignId);

        $this->authorize('update', $campaign);

        $validated = $request->validated();
        $fieldIds = $validated['custom_field_ids'];
        $sortOrders = $validated['sort_orders'] ?? [];
        $requiredOverrides = $validated['required_overrides'] ?? [];

        DB::beginTransaction();
        try {
            foreach ($fieldIds as $index => $fieldId) {
                $campaign->customFields()->syncWithoutDetaching([
                    $fieldId => [
                        'sort_order' => $sortOrders[$index] ?? $index,
                        'required_override' => $requiredOverrides[$index] ?? null,
                    ],
                ]);
            }

            DB::commit();
            $campaign->load(['customFields.options', 'customFields.validations']);

            return response()->json([
                'success' => true,
                'message' => 'Custom fields associated successfully.',
                'data' => $campaign->customFields,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to associate custom fields: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get custom fields of a campaign.
     * @authenticated
     */
    public function index(Request $request, string $campaignId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business.',
            ], 403);
        }

        $campaign = Campaign::where('business_id', $user->business->id)
            ->findOrFail($campaignId);

        $this->authorize('view', $campaign);

        $fields = $campaign->customFields()
            ->with(['options', 'validations'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fields,
        ], 200);
    }

    /**
     * Disassociate a custom field from a campaign.
     * @authenticated
     */
    public function destroy(Request $request, string $campaignId, string $fieldId): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business.',
            ], 403);
        }

        $campaign = Campaign::where('business_id', $user->business->id)
            ->findOrFail($campaignId);

        $this->authorize('update', $campaign);

        $campaign->customFields()->detach($fieldId);

        return response()->json([
            'success' => true,
            'message' => 'Custom field disassociated successfully.',
        ], 200);
    }
}
