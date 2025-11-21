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


/**
 * @group 👤 Usuarios
 * 
 * CRUD de usuarios del sistema
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin) - excepto POST /users (registro público)
 */
class UserController extends Controller
{
    /**
     * Registrar nuevo usuario (owner)
     * 
     * Crea un nuevo usuario con rol "owner". El email se marca como verificado automáticamente.
     * 
     * **Requisitos:**
     * - `name`: Nombre del usuario (requerido)
     * - `email`: Email único del usuario (requerido)
     * - `password`: Contraseña (mínimo 8 caracteres, requerido)
     * - `password_confirmation`: Confirmación de la contraseña (debe coincidir con password, requerido)
     * - `phone`: Teléfono opcional (formato: +521234567890)
     * - `avatar`: URL del avatar opcional
     * - `status`: Estado opcional (active, inactive, suspended)
     * - `timezone`: Zona horaria opcional
     * - `locale`: Locale opcional (2 caracteres, ej: es, en)
     * 
     * @unauthenticated
     * @bodyParam name string required Nombre del usuario. Example: John Doe
     * @bodyParam email string required Email único del usuario. Example: john@example.com
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Example: password123
     * @bodyParam password_confirmation string required Confirmación de la contraseña (debe coincidir con password). Example: password123
     * @bodyParam phone string optional Teléfono con código de país. Example: +521234567890
     * @bodyParam avatar string optional URL del avatar. Example: https://example.com/avatar.jpg
     * @bodyParam status string optional Estado del usuario (active, inactive, suspended). Example: active
     * @bodyParam timezone string optional Zona horaria. Example: America/Mexico_City
     * @bodyParam locale string optional Código de idioma (2 caracteres). Example: es
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "User created successfully.",
     *   "data": {
     *     "id": "019aa4c4-bd37-7229-8869-16dd62f2b724",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "owner",
     *     "status": "active",
     *     "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["This email is already registered."],
     *     "password": ["The password confirmation does not match."]
     *   }
     * }
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
