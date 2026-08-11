<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\SuratJalanController;

// Public
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/parts', [PartController::class, 'index']);
    Route::post('/parts', [PartController::class, 'store']);
    Route::delete('/parts/{id}', [PartController::class, 'destroy']);
    Route::post('/parts/import', [PartController::class, 'import']);
    Route::post('/transactions/{id}/approve-qc', [TransactionController::class, 'approveQc']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    Route::get('/transactions/chart', [TransactionController::class, 'chart']);
    Route::post('/surat-jalan', [SuratJalanController::class, 'generate']);
});
