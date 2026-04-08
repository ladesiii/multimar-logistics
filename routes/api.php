<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\UsuarisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/users', [UsuarisController::class, 'index']);
    Route::post('/users', [UsuarisController::class, 'store']);
    Route::put('/users/{user}', [UsuarisController::class, 'update']);
    Route::delete('/users/{user}', [UsuarisController::class, 'destroy']);
    Route::get('/clients', [ClientesController::class, 'index']);
    Route::post('/clients', [ClientesController::class, 'store']);
    Route::put('/clients/{client}', [ClientesController::class, 'update']);
    Route::delete('/clients/{client}', [ClientesController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

});
