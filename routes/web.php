<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ⬇️ Si tu panel Filament está en /admin (default), redirigí la raíz ahí:
Route::redirect('/', '/dashboard')->name('home');

// Ruta explícita para dashboard (necesaria para wayfinder)
Route::get('/dashboard', function () {
    return Inertia::render('dashboard');
})->middleware('auth')->name('dashboard');

// (opcional) cualquier otra ruta web propia
require __DIR__.'/settings.php';

// (opcional) 404 limpio para rutas no definidas
Route::fallback(fn () => abort(404));