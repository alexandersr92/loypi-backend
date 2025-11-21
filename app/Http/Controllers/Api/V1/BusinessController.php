<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBusinessRequest;
use App\Http\Requests\Api\V1\UpdateBusinessRequest;
use App\Http\Resources\Api\V1\BusinessResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 🏢 Negocios
 * 
 * CRUD de negocios (businesses)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class BusinessController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessRequest $request): JsonResponse
    {
        // Verificar autorización (solo owners sin negocio pueden crear)
        $this->authorize('create', Business::class);

        $validated = $request->validated();

        // El slug se genera automáticamente en el modelo si no se proporciona
        $business = Business::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business created successfully.',
            'data' => new BusinessResource($business),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $business = Business::findOrFail($id);

        // Verificar autorización
        $this->authorize('view', $business);

        return response()->json([
            'success' => true,
            'data' => new BusinessResource($business),
        ], 200);
    }

    /**
     * Display the specified resource by slug.
     */
    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        // Verificar autorización
        $this->authorize('view', $business);

        return response()->json([
            'success' => true,
            'data' => new BusinessResource($business),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessRequest $request, string $id): JsonResponse
    {
        $business = Business::findOrFail($id);

        // Verificar autorización
        $this->authorize('update', $business);

        $validated = $request->validated();

        $business->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business updated successfully.',
            'data' => new BusinessResource($business->fresh()),
        ], 200);
    }
}
