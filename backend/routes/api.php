<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// 10 intentos por minuto contra la fuerza bruta
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
