<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarisController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/users', [UsuarisController::class, 'index']);
    Route::post('/users', [UsuarisController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

