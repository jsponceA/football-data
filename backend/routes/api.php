<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// 10 intentos por minuto contra la fuerza bruta
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::post('/me/photo', [ProfileController::class, 'updatePhoto']);
    Route::delete('/me/photo', [ProfileController::class, 'destroyPhoto']);

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

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user');
        Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->whereNumber('user');
        Route::post('/users/{user}/photo', [UserController::class, 'updatePhoto'])->whereNumber('user');
        Route::delete('/users/{user}/photo', [UserController::class, 'destroyPhoto'])->whereNumber('user');
    });

    // quien administra usuarios necesita ver los roles para asignarlos
    Route::middleware('permission:users.manage|roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show'])->whereNumber('role');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update'])->whereNumber('role');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role');
        Route::get('/permissions', [PermissionController::class, 'index']);
    });
});
