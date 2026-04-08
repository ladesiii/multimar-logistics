<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

<<<<<<< Updated upstream
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
=======
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/users', [UsuarisController::class, 'index']);
    Route::post('/users', [UsuarisController::class, 'store']);
    Route::put('/users/{user}', [UsuarisController::class, 'update']);
    Route::delete('/users/{user}', [UsuarisController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);
>>>>>>> Stashed changes
});

