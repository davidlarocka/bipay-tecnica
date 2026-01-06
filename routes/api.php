<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;

//Públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//Protegidas middleware
Route::middleware('auth:api')->group(function () {
    Route::get('/me', function (Request $request) {
        return $request->user();
    });
});
Route::middleware('auth:api')->get('/me', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:api')->post('/transfer', [WalletController::class, 'transfer']);


//reportes
Route::get('/users/balances/csv', [WalletController::class, 'exportUsersCsv']);
//total transferred per user
Route::get('/users/total-transferred', [WalletController::class, 'getTotalTransferredPerUser']);
//average transferred per user
Route::get('/users/average-transferred', [WalletController::class, 'getAverageTransferredPerUser']);

