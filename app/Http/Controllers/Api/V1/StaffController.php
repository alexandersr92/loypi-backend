<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStaffRequest;
use App\Http\Requests\Api\V1\UpdateStaffRequest;
use App\Http\Resources\Api\V1\StaffResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group 👨‍💼 Staff
 * 
 * CRUD de staff (empleados). Requiere token de usuario (owner/admin)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        // Verificar que el usuario tenga un negocio
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view staff.',
            ], 403);
        }

        // Obtener staff del negocio del usuario
        $staff = Staff::where('business_id', $user->business->id)
            ->with('business')
            ->get();

        return response()->json([
            'success' => true,
            'data' => StaffResource::collection($staff),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        //$user = $request->user();
        $user = $request->user()->load('business');
        
       // dd($user->business->id); 
        // Verificar autorización
        $this->authorize('create', Staff::class);


        // Verificar que el usuario tenga un negocio
        if (! $user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to create staff.',
            ], 403);
        }

        // Verificar que el business_id pertenezca al usuario
        $validated = $request->validated();
        $validated['business_id'] = $user->business->id; // Asignar automáticamente
        if ($validated['business_id'] !== $user->business->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create staff for your own business.',
            ], 403);
        }

        //validate if code is unique
        if (Staff::where('business_id', $user->business->id)->where('code', $validated['code'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Code already exists.',
            ], 400);
        }

        // Hash del PIN
        $validated['passcode_hash'] = Hash::make($validated['pin']);
        unset($validated['pin']);

        // Valores por defecto
        $validated['active'] = $validated['active'] ?? true;
        $validated['failed_login_attempts'] = 0;
        $validated['locked_until'] = null;

        $staff = Staff::create($validated);
        $staff->load('business');

        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully.',
            'data' => new StaffResource($staff),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $staff = Staff::with('business')->findOrFail($id);

        // Verificar autorización
        $this->authorize('view', $staff);

        return response()->json([
            'success' => true,
            'data' => new StaffResource($staff),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStaffRequest $request, string $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        // Verificar autorización
        $this->authorize('update', $staff);

        $validated = $request->validated();

        // Si se actualiza el PIN, hashearlo
        if (isset($validated['pin'])) {
            $validated['passcode_hash'] = Hash::make($validated['pin']);
            unset($validated['pin']);
        }

        $staff->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully.',
            'data' => new StaffResource($staff->fresh()->load('business')),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        // Verificar autorización
        $this->authorize('delete', $staff);

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully.',
        ], 200);
    }

    /**
     * Desbloquear un staff (solo owner)
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        // Verificar autorización (solo owner del negocio puede desbloquear)
        $this->authorize('update', $staff);

        $staff->unlock();

        return response()->json([
            'success' => true,
            'message' => 'Staff unlocked successfully.',
            'data' => new StaffResource($staff->fresh()->load('business')),
        ], 200);
    }
}
