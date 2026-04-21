<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\EstadoOfertaController;
use App\Http\Controllers\OfertesController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UsuarisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint mínimo para leer el usuario autenticado desde Sanctum.
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']); // Inicia sesión y devuelve token + datos del usuario.

// El resto de rutas necesita sesión activa.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'usuarioAutenticado']); // Devuelve el usuario autenticado actual.
    Route::put('/profile', [AuthController::class, 'actualizarPerfil']); // Actualiza nombre, apellidos, email y/o contraseña.
    Route::post('/profile/verify-password', [AuthController::class, 'verificarContrasenaPerfil']); // Verifica la contraseña actual del perfil.

    // Solo administradores gestionan usuarios.
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UsuarisController::class, 'index']); // Lista usuarios.
        Route::post('/users', [UsuarisController::class, 'store']); // Crea un usuario.
        Route::put('/users/{user}', [UsuarisController::class, 'update']); // Actualiza un usuario existente.
        Route::delete('/users/{user}', [UsuarisController::class, 'destroy']); // Elimina un usuario.

    });

    // Administrador y operador gestionan clientes.
    Route::middleware('role:admin,operador')->group(function () {
        Route::get('/clients', [ClientesController::class, 'index']); // Lista clientes.
        Route::post('/clients', [ClientesController::class, 'store']); // Crea un cliente.
        Route::put('/clients/{client}', [ClientesController::class, 'update']); // Actualiza un cliente.
        Route::delete('/clients/{client}', [ClientesController::class, 'destroy']); // Elimina un cliente.
    });

    // Estos roles solo consultan ofertas y tracking.
    Route::middleware('role:admin,operador,cliente')->group(function () {
        Route::get('/offers', [OfertesController::class, 'index']); // Lista ofertas visibles según rol.
        Route::get('/offers/{offer}', [OfertesController::class, 'show'])->whereNumber('offer'); // Muestra el detalle de una oferta.
        Route::get('/tracking', [TrackingController::class, 'listarTracking']); // Lista ofertas aceptadas en tracking.
    });

    // Administrador y operador pueden crear y modificar ofertas.
    Route::middleware('role:admin,operador')->group(function () {
        Route::get('/offers/form-options', [OfertesController::class, 'opcionesFormulario']); // Carga catálogos para el formulario de ofertas.
        Route::post('/offers', [OfertesController::class, 'store']); // Crea una oferta.
        Route::put('/offers/{offer}', [OfertesController::class, 'update'])->whereNumber('offer'); // Actualiza una oferta.
        Route::delete('/offers/{offer}', [OfertesController::class, 'destroy'])->whereNumber('offer'); // Elimina una oferta.
    });

    // Cliente y administrador pueden aceptar o rechazar la oferta.
    Route::middleware('role:admin,cliente')->group(function () {
        Route::patch('/offers/{offer}/status', [EstadoOfertaController::class, 'actualizarEstado'])->whereNumber('offer'); // Acepta o rechaza una oferta.
    });

    Route::post('/logout', [AuthController::class, 'cerrarSesion']); // Cierra sesión y revoca el token actual.

});
