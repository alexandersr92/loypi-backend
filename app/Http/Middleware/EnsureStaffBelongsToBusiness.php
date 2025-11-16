<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffBelongsToBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->get('business');
        $staff = $request->user('staff');

        if (!$staff || $staff->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}

