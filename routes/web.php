<?php

use Illuminate\Support\Facades\Route;

// ⬇️ Si tu panel Filament está en /admin (default), redirigí la raíz ahí:
Route::redirect('/', '/dashboard')->name('home');

// (opcional) cualquier otra ruta web propia
require __DIR__.'/settings.php';

// (opcional) 404 limpio para rutas no definidas
Route::fallback(fn () => abort(404));