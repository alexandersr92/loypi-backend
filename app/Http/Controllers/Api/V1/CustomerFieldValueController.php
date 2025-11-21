<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerFieldValueRequest;
use App\Models\Campaign;
use App\Models\CustomerFieldValue;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 👥 Customers - Field Values
 * 
 * Gestión de valores de custom fields para customers. Requiere token de usuario (owner/admin) o cliente
 * 
 * @authenticated
 */
class CustomerFieldValueController extends Controller
{
    /**
     * Store field values for a customer in a campaign.
     * @authenticated
     */
    public function store(StoreCustomerFieldValueRequest $request, string $campaignId, string $customerId): JsonResponse
    {
        // TODO: Verificar que el customer existe y pertenece al business
        // TODO: Verificar que el customer está registrado en la campaign
        
        $campaign = Campaign::findOrFail($campaignId);
        $validated = $request->validated();
        $values = $validated['values'];

        DB::beginTransaction();
        try {
            foreach ($values as $valueData) {
                $field = CustomField::findOrFail($valueData['custom_field_id']);
                
                // Verificar que el field está asociado a la campaign
                if (!$campaign->customFields()->where('custom_fields.id', $field->id)->exists()) {
                    throw new \Exception("Custom field {$field->id} is not associated with this campaign.");
                }

                // Determinar qué valor usar según el tipo del field
                $valueToStore = null;
                $valueColumn = null;

                switch ($field->type) {
                    case 'text':
                    case 'select':
                        $valueToStore = $valueData['string_value'] ?? null;
                        $valueColumn = 'string_value';
                        break;
                    case 'number':
                        $valueToStore = $valueData['number_value'] ?? null;
                        $valueColumn = 'number_value';
                        break;
                    case 'date':
                        $valueToStore = $valueData['date_value'] ?? null;
                        $valueColumn = 'date_value';
                        break;
                    case 'boolean':
                        $valueToStore = $valueData['boolean_value'] ?? null;
                        $valueColumn = 'boolean_value';
                        break;
                }

                // Verificar required
                $pivot = $campaign->customFields()
                    ->where('custom_fields.id', $field->id)
                    ->first()
                    ->pivot;
                
                $isRequired = $pivot->required_override ?? $field->required;
                
                if ($isRequired && $valueToStore === null) {
                    throw new \Exception("Field {$field->label} is required.");
                }

                // Validar valor según tipo
                $this->validateFieldValue($field, $valueToStore, $valueColumn);

                // Crear o actualizar el valor
                CustomerFieldValue::updateOrCreate(
                    [
                        'customer_id' => $customerId,
                        'campaign_id' => $campaignId,
                        'custom_field_id' => $field->id,
                    ],
                    [
                        'business_id' => $campaign->business_id,
                        $valueColumn => $valueToStore,
                    ]
                );
            }

            DB::commit();

            $savedValues = CustomerFieldValue::where('customer_id', $customerId)
                ->where('campaign_id', $campaignId)
                ->with('customField')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Field values saved successfully.',
                'data' => $savedValues,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save field values: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get field values for a customer in a campaign.
     * @authenticated
     */
    public function index(Request $request, string $campaignId, string $customerId): JsonResponse
    {
        $values = CustomerFieldValue::where('customer_id', $customerId)
            ->where('campaign_id', $campaignId)
            ->with(['customField.options', 'customField.validations'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $values,
        ], 200);
    }

    /**
     * Validate field value according to field type and validations
     */
    private function validateFieldValue(CustomField $field, $value, string $valueColumn): void
    {
        if ($value === null) {
            return;
        }

        // Validar tipo
        switch ($field->type) {
            case 'select':
                $options = $field->options()->pluck('value')->toArray();
                if (!in_array($value, $options)) {
                    throw new \Exception("Value must be one of the field options.");
                }
                break;
        }

        // Validar según validaciones del field
        $validations = $field->validations;
        foreach ($validations as $validation) {
            $this->applyValidation($validation, $value, $valueColumn);
        }
    }

    /**
     * Apply a validation rule
     */
    private function applyValidation($validation, $value, string $valueColumn): void
    {
        $operator = $validation->operator;
        $validationValue = $validation->{"value_{$valueColumn}"} ?? null;

        if ($validationValue === null) {
            return;
        }

        $passed = false;

        switch ($operator) {
            case '=':
                $passed = $value == $validationValue;
                break;
            case '!=':
                $passed = $value != $validationValue;
                break;
            case '>':
                $passed = $value > $validationValue;
                break;
            case '>=':
                $passed = $value >= $validationValue;
                break;
            case '<':
                $passed = $value < $validationValue;
                break;
            case '<=':
                $passed = $value <= $validationValue;
                break;
            case 'regex':
                $passed = preg_match($validationValue, $value);
                break;
        }

        if (!$passed) {
            $message = $validation->message ?? "Validation failed for operator: {$operator}";
            throw new \Exception($message);
        }
    }
}
