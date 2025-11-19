<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('dashboard*')) {
            return $next($request);
        }
        
        // Este middleware maneja la cookie de appearance (tema dark/light)
        // La cookie se establece desde el frontend y se lee aquí si es necesario
        
        return $next($request);
    }
}

