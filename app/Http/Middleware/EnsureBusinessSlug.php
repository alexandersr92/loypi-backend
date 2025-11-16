<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug') ?? $request->input('slug');
        
        if (!$slug) {
            return response()->json(['message' => 'Slug de negocio requerido'], 400);
        }

        $business = Business::where('slug', $slug)->first();
        
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $request->merge(['business' => $business]);
        $request->setUserResolver(fn () => $business);

        return $next($request);
    }
}

