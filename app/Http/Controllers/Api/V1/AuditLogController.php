<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Http\Resources\Api\V1\AuditLogResourceCollection;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 📊 Audit Logs
 * 
 * Endpoints para ver logs de auditoría (solo para owners/admins)
 * 
 * @authenticated
 * @header Authorization Bearer {user_token} Requiere token de usuario (owner/admin)
 */
class AuditLogController extends Controller
{
    /**
     * Listar audit logs (Owner/Admin)
     * 
     * Obtiene todos los logs de auditoría con filtros opcionales.
     * 
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view audit logs.',
            ], 403);
        }

        $query = AuditLog::query();

        // Filtros opcionales
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('actor_type')) {
            $query->where('actor_type', $request->actor_type);
        }

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ordenar por fecha más reciente
        $query->orderBy('created_at', 'desc');

        // Paginación
        $perPage = $request->get('per_page', 50);
        $logs = $query->with(['actor', 'auditable'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AuditLogResourceCollection::make($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }

    /**
     * Obtener audit log específico (Owner/Admin)
     * 
     * Obtiene la información detallada de un log de auditoría específico.
     * 
     * @authenticated
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->load('business');
        
        if (!$user->business) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a business to view audit logs.',
            ], 403);
        }

        $log = AuditLog::with(['actor', 'auditable'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($log),
        ], 200);
    }
}
