<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
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
     * Solo disponible para owners/admins.
     * 
     * @authenticated
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

        $customers = Customer::where('business_id', $user->business->id)
            ->with('business')
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
     * Obtener customer por ID
     * 
     * Obtiene la información detallada de un customer específico por su UUID.
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
