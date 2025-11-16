<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerBelongsToBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $slug = $request->route('slug') ?? $request->input('slug');
        
        if (!$slug) {
            return response()->json(['message' => 'Slug de negocio requerido'], 400);
        }

        $business = Business::where('slug', $slug)->first();
        
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        // Verificar que el usuario sea el owner del negocio o admin
        if ($business->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'No autorizado para acceder a este negocio'], 403);
        }

        $request->merge(['business' => $business]);

        return $next($request);
    }
}

