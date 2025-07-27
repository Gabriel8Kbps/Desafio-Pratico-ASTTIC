<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropostaCursoController;

// Rotas de autenticação
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rotas protegidas por autenticação (requer token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Futuras rotas protegidas (ex: propostas, disciplinas)
    Route::apiResource('propostas', PropostaCursoController::class);
    Route::put('propostas/{proposta}/avaliar', [PropostaCursoController::class, 'avaliar']);
    Route::put('propostas/{proposta}/decidir', [PropostaCursoController::class, 'decidir']);
});