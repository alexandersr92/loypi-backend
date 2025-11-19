<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Hash password
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Asignar rol "owner" automáticamente para registros por API
        $validated['role'] = 'owner';
        
        // Marcar email como verificado automáticamente
        $validated['email_verified_at'] = now();

        $user = User::create($validated);
        $user->load('business');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::with('business')->findOrFail($id);

        // Verificar autorización
        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Verificar autorización
        $this->authorize('update', $user);

        $validated = $request->validated();

        // Hash password if provided
        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($user->fresh()->load('business')),
        ], 200);
    }
}
