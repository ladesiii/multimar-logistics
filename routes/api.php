<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\OfertesController;
use App\Http\Controllers\UsuarisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'usuarioAutenticado']);
    Route::put('/profile', [AuthController::class, 'actualizarPerfil']);
    Route::post('/profile/verify-password', [AuthController::class, 'verificarContrasenaPerfil']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UsuarisController::class, 'index']);
        Route::post('/users', [UsuarisController::class, 'store']);
        Route::put('/users/{user}', [UsuarisController::class, 'update']);
        Route::delete('/users/{user}', [UsuarisController::class, 'destroy']);

        Route::get('/clients', [ClientesController::class, 'index']);
        Route::post('/clients', [ClientesController::class, 'store']);
        Route::put('/clients/{client}', [ClientesController::class, 'update']);
        Route::delete('/clients/{client}', [ClientesController::class, 'destroy']);
    });

    Route::middleware('role:admin,operador,cliente')->group(function () {
        Route::get('/offers', [OfertesController::class, 'index']);
        Route::get('/offers/{offer}', [OfertesController::class, 'show'])->whereNumber('offer');
        Route::get('/tracking', [OfertesController::class, 'listarTracking']);
    });

    Route::middleware('role:admin,operador')->group(function () {
        Route::get('/offers/form-options', [OfertesController::class, 'opcionesFormulario']);
        Route::post('/offers', [OfertesController::class, 'store']);
        Route::put('/offers/{offer}', [OfertesController::class, 'update'])->whereNumber('offer');
        Route::delete('/offers/{offer}', [OfertesController::class, 'destroy'])->whereNumber('offer');
    });

    Route::middleware('role:admin,cliente')->group(function () {
        Route::patch('/offers/{offer}/status', [OfertesController::class, 'actualizarEstado'])->whereNumber('offer');
    });

    Route::post('/logout', [AuthController::class, 'cerrarSesion']);

});
