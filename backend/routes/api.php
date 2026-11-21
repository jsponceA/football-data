<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// 10 intentos por minuto contra la fuerza bruta
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('permission:competitions.view')->group(function () {
        Route::get('/competitions', [CompetitionController::class, 'index']);
        Route::get('/competitions/{competition_id}', [CompetitionController::class, 'show'])
            ->whereNumber('competition_id');
    });

    // las rutas de equipos van en singular (/team), tal como las pide la prueba
    Route::middleware('permission:teams.view')->group(function () {
        Route::get('/team', [TeamController::class, 'index']);
        Route::get('/team/{team}', [TeamController::class, 'show'])->whereNumber('team');
    });

    Route::get('/players', [PlayerController::class, 'index'])->middleware('permission:players.view');
});
