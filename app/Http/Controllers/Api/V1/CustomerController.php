<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\ExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 👥 Customers
 * 
 * CRUD de customers (clientes). Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class CustomerController extends Controller
{
    /**
     * Listar customers del negocio
     * 
     * Obtiene todos los customers registrados en el negocio del usuario autenticado.
     * Permite filtrar por campaña y buscar por nombre.
     * Solo disponible para owners/admins.
     * 
     * @authenticated
     * @queryParam campaign_id string optional Filtrar por ID de campaña. Example: uuid-de-campaign
     * @queryParam search string optional Buscar por nombre del cliente. Example: Juan
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view customers.',
            ], 403);
        }

        $query = Customer::where('business_id', $user->business->id);

        // Filtro por campaña
        if ($request->has('campaign_id') && $request->campaign_id) {
            $campaignId = $request->campaign_id;
            
            // Verificar que la campaña pertenezca al negocio
            $campaignExists = \App\Models\Campaign::where('id', $campaignId)
                ->where('business_id', $user->business->id)
                ->exists();
            
            if (!$campaignExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found or does not belong to your business.',
                ], 404);
            }

            // Filtrar customers que estén inscritos en esta campaña
            $query->whereHas('customerCampaigns', function ($q) use ($campaignId) {
                $q->where('campaign_id', $campaignId);
            });
        }

        // Búsqueda por nombre
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $customers = $query->with('business')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'short_code' => $customer->short_code,
                    'phone' => $customer->phone,
                    'name' => $customer->name,
                    'business' => [
                        'id' => $customer->business->id,
                        'slug' => $customer->business->slug,
                        'name' => $customer->business->name,
                    ],
                    'created_at' => $customer->created_at->toIso8601String(),
                    'updated_at' => $customer->updated_at->toIso8601String(),
                ];
            }),
        ], 200);
    }

    /**
     * Generar Excel de clientes
     * 
     * Genera un archivo Excel con todos los clientes del negocio.
     * Aplica los mismos filtros que el endpoint index (campaign_id, search).
     * 
     * @authenticated
     * @queryParam campaign_id string optional Filtrar por ID de campaña. Example: uuid-de-campaign
     * @queryParam search string optional Buscar por nombre del cliente. Example: Juan
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "url": "https://app.com/storage/exports/clientes_2024-01-15_143022.xlsx",
     *     "path": "exports/clientes_2024-01-15_143022.xlsx",
     *     "filename": "clientes_2024-01-15_143022.xlsx"
     *   }
     * }
     */
    public function generateExcel(Request $request, ExcelService $excelService): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to export customers.',
            ], 403);
        }

        $query = Customer::where('business_id', $user->business->id);

        // Aplicar los mismos filtros que el index
        if ($request->has('campaign_id') && $request->campaign_id) {
            $campaignId = $request->campaign_id;
            
            $campaignExists = \App\Models\Campaign::where('id', $campaignId)
                ->where('business_id', $user->business->id)
                ->exists();
            
            if (!$campaignExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found or does not belong to your business.',
                ], 404);
            }

            $query->whereHas('customerCampaigns', function ($q) use ($campaignId) {
                $q->where('campaign_id', $campaignId);
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $customers = $query->with('business')
            ->orderBy('name')
            ->get();

        // Preparar datos para el Excel
        $data = $customers->map(function ($customer) {
            return [
                'ID' => $customer->id,
                'Código' => $customer->short_code,
                'Nombre' => $customer->name,
                'Teléfono' => $customer->phone,
                'Negocio' => $customer->business->name,
                'Fecha de Registro' => $customer->created_at->format('d/m/Y H:i'),
                'Última Actualización' => $customer->updated_at->format('d/m/Y H:i'),
            ];
        })->toArray();

        try {
            $result = $excelService->generate(
                data: $data,
                headers: ['ID', 'Código', 'Nombre', 'Teléfono', 'Negocio', 'Fecha de Registro', 'Última Actualización'],
                filename: 'clientes',
                sheetName: 'Clientes'
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el archivo Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener customer por ID
     * 
     * Obtiene la información detallada de un customer específico por su UUID.
     * Incluye las campañas en las que está inscrito, sus stamps (punch/racha) y los valores de campos personalizados.
     * Solo disponible para owners/admins del mismo negocio.
     * 
     * @authenticated
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view customers.',
            ], 403);
        }

        $customer = Customer::where('business_id', $user->business->id)
            ->where('id', $id)
            ->with([
                'business',
                'customerCampaigns.campaign',
                'customerCampaigns.stamps.staff',
                'fieldValues.customField'
            ])
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $this->authorize('view', $customer);

        // Obtener las campañas con sus datos
        $campaigns = $customer->customerCampaigns->map(function ($customerCampaign) use ($customer) {
            $campaign = $customerCampaign->campaign;
            
            // Obtener los stamps (historial de punches/rachas)
            $stamps = $customerCampaign->stamps->map(function ($stamp) {
                return [
                    'id' => $stamp->id,
                    'type' => $stamp->type, // 'punch' o 'streak'
                    'meta' => $stamp->meta,
                    'staff' => $stamp->staff ? [
                        'id' => $stamp->staff->id,
                        'name' => $stamp->staff->name,
                    ] : null,
                    'created_at' => $stamp->created_at->toIso8601String(),
                ];
            });

            // Obtener los valores de campos personalizados para esta campaña
            $fieldValues = $customer->fieldValues()
                ->where('campaign_id', $campaign->id)
                ->with('customField')
                ->get()
                ->map(function ($fieldValue) {
                    return [
                        'id' => $fieldValue->id,
                        'custom_field' => [
                            'id' => $fieldValue->customField->id,
                            'key' => $fieldValue->customField->key,
                            'label' => $fieldValue->customField->label,
                            'type' => $fieldValue->customField->type,
                        ],
                        'value' => $this->getFieldValue($fieldValue),
                        'created_at' => $fieldValue->created_at->toIso8601String(),
                        'updated_at' => $fieldValue->updated_at->toIso8601String(),
                    ];
                });

            return [
                'id' => $campaign->id,
                'code' => $campaign->code,
                'type' => $campaign->type,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'required_stamps' => $campaign->required_stamps,
                'active' => $campaign->active,
                'cover_image' => $campaign->cover_image,
                'logo_url' => $campaign->logo_url,
                // Datos del pivot (customer_campaigns)
                'stamps' => $customerCampaign->stamps ?? 0, // Contador total de stamps
                'redeemed_at' => $customerCampaign->redeemed_at?->toIso8601String(),
                'registered_at' => $customerCampaign->created_at->toIso8601String(),
                // Historial de stamps (punch/racha)
                'stamps_history' => $stamps,
                // Valores del formulario (custom fields)
                'form_data' => $fieldValues,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'short_code' => $customer->short_code,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'business' => [
                    'id' => $customer->business->id,
                    'slug' => $customer->business->slug,
                    'name' => $customer->business->name,
                ],
                'campaigns' => $campaigns,
                'created_at' => $customer->created_at->toIso8601String(),
                'updated_at' => $customer->updated_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Obtiene el valor formateado de un campo personalizado
     */
    private function getFieldValue($fieldValue): mixed
    {
        return match ($fieldValue->customField->type) {
            'text' => $fieldValue->string_value,
            'number' => $fieldValue->number_value,
            'date' => $fieldValue->date_value?->toIso8601String(),
            'boolean' => $fieldValue->boolean_value,
            'select' => $fieldValue->string_value, // Para select también se guarda en string_value
            default => $fieldValue->string_value,
        };
    }

    /**
     * Obtener customer por short_code
     * 
     * Obtiene la información de un customer usando su short_code único de 6 caracteres.
     * Útil para búsquedas rápidas en el punto de venta.
     * Solo disponible para owners/admins del mismo negocio.
     * 
     * @authenticated
     */
    public function findByCode(Request $request, string $code): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view customers.',
            ], 403);
        }

        $customer = Customer::where('business_id', $user->business->id)
            ->where('short_code', strtoupper($code))
            ->with('business')
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $this->authorize('view', $customer);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'short_code' => $customer->short_code,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'business' => [
                    'id' => $customer->business->id,
                    'slug' => $customer->business->slug,
                    'name' => $customer->business->name,
                ],
                'created_at' => $customer->created_at->toIso8601String(),
                'updated_at' => $customer->updated_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Actualizar customer
     * 
     * Actualiza la información de un customer (nombre y/o teléfono).
     * Solo disponible para owners/admins del mismo negocio.
     * 
     * @authenticated
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to update customers.',
            ], 403);
        }

        $customer = Customer::where('business_id', $user->business->id)
            ->where('id', $id)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $this->authorize('update', $customer);

        $validated = $request->validated();
        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => [
                'id' => $customer->id,
                'short_code' => $customer->short_code,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'updated_at' => $customer->updated_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Eliminar customer
     * 
     * Elimina permanentemente un customer del sistema.
     * Solo disponible para owners/admins del mismo negocio.
     * 
     * @authenticated
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to delete customers.',
            ], 403);
        }

        $customer = Customer::where('business_id', $user->business->id)
            ->where('id', $id)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
            ], 404);
        }

        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ], 200);
    }
}
