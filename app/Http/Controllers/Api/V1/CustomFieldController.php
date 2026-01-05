<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomFieldRequest;
use App\Http\Requests\Api\V1\UpdateCustomFieldRequest;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\CustomFieldValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 📋 Custom Fields
 * 
 * CRUD de custom fields (campos personalizados). Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class CustomFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view custom fields.',
            ], 403);
        }

        $fields = CustomField::where('business_id', $user->business->id)
            ->with(['business', 'options', 'validations'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fields,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     * @authenticated
     */
    public function store(StoreCustomFieldRequest $request): JsonResponse
    {
        $user = $request->user()->load('business');
        


        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to create custom fields.',
            ], 403);
        }

        $this->authorize('create', CustomField::class);

        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        $validations = $validated['validations'] ?? [];
        unset($validated['options'], $validated['validations']);

        $validated['business_id'] = $user->business->id;
        $validated['required'] = $validated['required'] ?? false;
        $validated['active'] = $validated['active'] ?? true;

        DB::beginTransaction();
        try {
            $field = CustomField::create($validated);

            // Crear opciones si es tipo select
            if ($field->type === 'select' && !empty($options)) {
                foreach ($options as $index => $option) {
                    CustomFieldOption::create([
                        'custom_field_id' => $field->id,
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'sort_order' => $option['sort_order'] ?? $index,
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

            DB::commit();
            $field->load(['business', 'options', 'validations']);

            return response()->json([
                'success' => true,
                'message' => 'Custom field created successfully.',
                'data' => $field,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create custom field: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * @authenticated
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $field = CustomField::where('business_id', $user->business->id)
            ->with(['business', 'options', 'validations'])
            ->findOrFail($id);

        $this->authorize('view', $field);

        return response()->json([
            'success' => true,
            'data' => $field,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     * @authenticated
     */
    public function update(UpdateCustomFieldRequest $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $field = CustomField::where('business_id', $user->business->id)
            ->findOrFail($id);

        $this->authorize('update', $field);

        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        $validations = $validated['validations'] ?? [];
        unset($validated['options'], $validated['validations']);

        DB::beginTransaction();
        try {
            $field->update($validated);

            // Actualizar opciones si es tipo select
            if ($field->type === 'select' && !empty($options)) {
                // Eliminar opciones que no están en el request
                $optionIds = collect($options)->pluck('id')->filter();
                CustomFieldOption::where('custom_field_id', $field->id)
                    ->whereNotIn('id', $optionIds)
                    ->delete();

                // Crear o actualizar opciones
                foreach ($options as $index => $option) {
                    if (isset($option['id'])) {
                        CustomFieldOption::where('id', $option['id'])
                            ->update([
                                'value' => $option['value'],
                                'label' => $option['label'],
                                'sort_order' => $option['sort_order'] ?? $index,
                            ]);
                    } else {
                        CustomFieldOption::create([
                            'custom_field_id' => $field->id,
                            'value' => $option['value'],
                            'label' => $option['label'],
                            'sort_order' => $option['sort_order'] ?? $index,
                        ]);
                    }
                }
            }

            // Actualizar validaciones
            if (!empty($validations)) {
                // Eliminar validaciones que no están en el request
                $validationIds = collect($validations)->pluck('id')->filter();
                CustomFieldValidation::where('custom_field_id', $field->id)
                    ->whereNotIn('id', $validationIds)
                    ->delete();

                // Crear o actualizar validaciones
                foreach ($validations as $validation) {
                    if (isset($validation['id'])) {
                        CustomFieldValidation::where('id', $validation['id'])
                            ->update([
                                'operator' => $validation['operator'],
                                'value_string' => $validation['value_string'] ?? null,
                                'value_number' => $validation['value_number'] ?? null,
                                'value_date' => $validation['value_date'] ?? null,
                                'message' => $validation['message'] ?? null,
                            ]);
                    } else {
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
            }

            DB::commit();
            $field->load(['business', 'options', 'validations']);

            return response()->json([
                'success' => true,
                'message' => 'Custom field updated successfully.',
                'data' => $field,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update custom field: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @authenticated
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $field = CustomField::where('business_id', $user->business->id)
            ->findOrFail($id);

        $this->authorize('delete', $field);

        // Verificar si tiene valores de customers
        if ($field->hasCustomerValues()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete custom field that has customer values. You can only disable it.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Eliminar opciones y validaciones (cascade)
            $field->options()->delete();
            $field->validations()->delete();
            $field->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Custom field deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete custom field: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status of the custom field.
     * @authenticated
     */
    public function toggle(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        $field = CustomField::where('business_id', $user->business->id)
            ->findOrFail($id);

        $this->authorize('update', $field);

        $field->active = !$field->active;
        $field->save();

        return response()->json([
            'success' => true,
            'message' => 'Custom field status updated successfully.',
            'data' => $field,
        ], 200);
    }
}
