<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerBelongsToBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->get('business');
        $customer = $request->user('customer');

        if (!$customer) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar que el cliente tenga un token válido para este negocio
        $token = $request->user('customer_token');
        if (!$token || $token->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado para este negocio'], 403);
        }

        return $next($request);
    }
}

