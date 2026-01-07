<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Passport)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    
    // Gestión de Usuario y Sesión
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/update', [AuthController::class, 'update']);
    Route::delete('/delete', [AuthController::class, 'destroy']);

    // Operaciones de Billetera
    Route::post('/transfer', [WalletController::class, 'transfer']);

    // Reportes y Estadísticas
    Route::prefix('users')->group(function () {
        Route::get('/balances/csv', [WalletController::class, 'exportUsersCsv']);
        Route::get('/total-transferred', [WalletController::class, 'getTotalTransferredPerUser']);
        Route::get('/average-transferred', [WalletController::class, 'getAverageTransferredPerUser']);
    });
    
});